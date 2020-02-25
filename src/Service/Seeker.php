<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Service;

use Exception;
use Goutte\Client as GoutteClient;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use ReliqArts\Scavenger\Concern\Timed;
use ReliqArts\Scavenger\Contract\ConfigProvider as ConfigProviderContract;
use ReliqArts\Scavenger\Contract\Seeker as SeekerInterface;
use ReliqArts\Scavenger\Exception\InvalidTargetDefinition;
use ReliqArts\Scavenger\Factory\TargetBuilder;
use ReliqArts\Scavenger\Helper\FormattedMessage;
use ReliqArts\Scavenger\Helper\NodeProximityAssistant;
use ReliqArts\Scavenger\Helper\TargetKey;
use ReliqArts\Scavenger\Model\Target;
use ReliqArts\Scavenger\OptionSet;
use ReliqArts\Scavenger\Result;
use ReliqArts\Scavenger\TitleLink;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

final class Seeker extends Communicator implements SeekerInterface
{
    use Timed;

    private const INITIAL_PAGE = 1;

    /**
     * @var OptionSet
     */
    protected OptionSet $optionSet;

    /**
     * HTTP Client.
     *
     * @var GoutteClient
     */
    protected GoutteClient $client;

    /**
     * Current page.
     *
     * @var int
     */
    private int $page = self::INITIAL_PAGE;

    /**
     * Current loaded target configurations.
     *
     * @var array
     */
    private array $targetDefinitions;

    /**
     * Event logger.
     *
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var NodeProximityAssistant
     */
    private NodeProximityAssistant $nodeProximityAssistant;

    /**
     * @var int
     */
    private int $pageLimit;

    /**
     * @var Scrapper
     */
    private Scrapper $scrapper;

    /**
     * Create a new seeker.
     *
     * @throws Exception
     */
    public function __construct(
        LoggerInterface $logger,
        GoutteClient $client,
        ConfigProviderContract $config,
        NodeProximityAssistant $nodeProximityAssistant
    ) {
        parent::__construct();

        $this->logger = $logger;
        $this->client = $client;
        $this->targetDefinitions = $config->getTargets();
        $this->nodeProximityAssistant = $nodeProximityAssistant;
        $this->verbosity = $config->getVerbosity();
        $this->hashAlgorithm = $config->getHashAlgorithm();
        $this->scrapper = new Scrapper(
            $this->logger,
            $this->callingCommand,
            $this->hashAlgorithm,
            $this->verbosity
        );
    }

    /**
     * {@inheritdoc}
     */
    public function seek(
        OptionSet $options,
        ?string $targetName = null,
        ?Command $callingCommand = null
    ): Result {
        $this->optionSet = $options;
        $this->pageLimit = $options->getPages();

        $targetDefinitions = $this->targetDefinitions;
        $targetBuilder = $this->getTargetBuilder();
        $result = new Result();

        $this->startTimer();

        if ($targetName && array_key_exists($targetName, $targetDefinitions)) {
            $targetDefinitions = [$targetName => $targetDefinitions[$targetName]];
        } elseif ($targetName) {
            return $result->addError(FormattedMessage::get(FormattedMessage::TARGET_UNKNOWN, $targetName));
        }

        foreach ($targetDefinitions as $currentTargetName => $targetDefinition) {
            try {
                $targetDefinition[TargetKey::NAME] = $currentTargetName;
                $target = $targetBuilder->createFromDefinition($targetDefinition);
                // check for page limit override
                if ($target->hasPages()) {
                    $this->pageLimit = $target->getPages();
                }
                $this->crawlTarget($target);
                // reset page limit
                $this->pageLimit = $this->optionSet->getPages();
            } catch (InvalidTargetDefinition $exception) {
                $this->tell($exception->getMessage());
            } catch (Exception $exception) {
                $message = FormattedMessage::get(
                    FormattedMessage::TARGET_UNEXPECTED_EXCEPTION,
                    $currentTargetName,
                    $exception->getMessage()
                );

                $result->addError($message);
                $this->tell($message);
            }
        }

        if ($this->optionSet->isSaveScraps()) {
            $this->scrapper->saveScraps();
        }

        if ($this->optionSet->isConvertScraps()) {
            $this->scrapper->convertScraps(false, true);
        }

        $extra = (object)[
            'total' => $this->scrapper->getScraps()->count(),
            'executionTime' => $this->elapsedTime(),
            'new' => $this->scrapper->getNewScrapsCount(),
            'converted' => $this->scrapper->getRelatedObjects()->count(),
            'unconverted' => $this->scrapper->getScraps()->count() - $this->scrapper->getRelatedObjects()->count(),
            'scrapsSaved' => $this->optionSet->isSaveScraps(),
            'scrapsConverted' => $this->optionSet->isConvertScraps(),
        ];

        if (!$result->hasErrors()) {
            return $result
                ->setSuccess(true)
                ->setExtra($extra);
        }

        return $result;
    }

    private function crawlTarget(Target $target): void
    {
        $crawler = $this->client->request('GET', $target->getSource());

        $this->tell(FormattedMessage::get(FormattedMessage::TARGET, $target->getName()), self::COMM_DIRECTION_IN);

        // do search if search is enabled for target
        if ($target->hasSearch()) {
            $this->searchAndScrape($target, $crawler);
        } else {
            // crawl for details
            $this->scrape($target, $crawler);
        }

        $this->printBlankLine();
    }

    private function searchAndScrape(Target $target, Crawler $crawler): void
    {
        if ($this->verbosity >= self::VERBOSITY_MEDIUM) {
            $this->logger->info('Landing Document', [$crawler->html()]);
        }

        $searchFormConfig = $target->getSearch()[TargetKey::SEARCH_FORM];
        $searchFormSubmitButtonConfig = $searchFormConfig[TargetKey::SEARCH_FORM_SUBMIT_BUTTON] ?? [];
        $formCrawler = $crawler->filter($searchFormConfig[TargetKey::SEARCH_FORM_SELECTOR]);
        $submitButtonIdentifier = null;
        $form = null;

        // check if submit button was defined
        if (!empty($searchFormSubmitButtonConfig[TargetKey::SEARCH_FORM_SUBMIT_BUTTON_ID])) {
            $submitButtonIdentifier = $searchFormSubmitButtonConfig[TargetKey::SEARCH_FORM_SUBMIT_BUTTON_ID];
        } elseif (!empty($searchFormSubmitButtonConfig[TargetKey::SEARCH_FORM_SUBMIT_BUTTON_TEXT])) {
            $submitButtonIdentifier = $searchFormSubmitButtonConfig[TargetKey::SEARCH_FORM_SUBMIT_BUTTON_TEXT];
        }

        try {
            $form = empty($submitButtonIdentifier)
                ? $formCrawler->form()
                : $formCrawler->selectButton((string)$submitButtonIdentifier)->form();
        } catch (InvalidArgumentException $e) {
            $this->logger->error($e);
        }

        if ($form instanceof Form) {
            if ($this->verbosity >= self::VERBOSITY_MEDIUM) {
                $this->logger->info('FORM', [$form]);
            }

            // Search each phrase/keyword one at a time.
            foreach ($target->getSearch()[TargetKey::SEARCH_KEYWORDS] as $keyword) {
                // set keyword
                $form[$searchFormConfig[TargetKey::SEARCH_FORM_INPUT]] = $keyword;

                $this->tell(FormattedMessage::get(FormattedMessage::SEARCH_KEYWORD, $keyword), self::COMM_DIRECTION_IN);
                // submit search form
                $resultCrawler = $this->client->submit($form);

                if ($this->verbosity >= self::VERBOSITY_MEDIUM) {
                    $this->logger->info(
                        FormattedMessage::get(
                            FormattedMessage::FIRST_RESULT_PAGE_FOR_KEYWORD_ON_TARGET,
                            $keyword,
                            $target->getName()
                        ),
                        [$resultCrawler->html()]
                    );
                }

                // crawl
                $this->scrape($target, $resultCrawler);
            }
        } else {
            $this->tell(FormattedMessage::get(FormattedMessage::TARGET_INVALID_SEARCH_FORM, $target->getName()));
        }
    }

    private function scrape(Target $target, ?Crawler $crawler): void
    {
        if ($crawler === null) {
            return;
        }

        $markup = $target->getMarkup();
        $titleLinkSelector = $markup[TargetKey::MARKUP_TITLE];
        $markupInside = $markup[TargetKey::special(TargetKey::MARKUP_INSIDE)] ?? [];
        $markupHasInside = !empty($markupInside);
        $markupHasItemWrapper = false;

        // choose link as title link selector if it is not empty and it is not set to a special key
        if (!(empty($markup[TargetKey::MARKUP_LINK]) || ConfigProvider::isSpecialKey($markup[TargetKey::MARKUP_LINK]))) {
            $titleLinkSelector = $markup[TargetKey::MARKUP_LINK];
        }

        // determine what item wrapper is
        $markup[TargetKey::special(TargetKey::ITEM_WRAPPER)] = Scanner::firstNonEmpty($markup, [
            TargetKey::special(TargetKey::ITEM_WRAPPER),
            TargetKey::special(TargetKey::RESULT),
            TargetKey::special(TargetKey::ITEM),
            TargetKey::special(TargetKey::WRAPPER),
        ]);
        if (!empty($markup[TargetKey::special(TargetKey::ITEM_WRAPPER)])) {
            $markupHasItemWrapper = true;
        }

        // Page by page we go...
        $this->page = self::INITIAL_PAGE;

        do {
            $this->tell(
                FormattedMessage::get(FormattedMessage::PROCESSING_PAGE_N, $this->page),
                self::COMM_DIRECTION_NONE
            );

            // scrape each by carving the insides
            $items = $crawler->filter($titleLinkSelector);

            if ($markupHasItemWrapper) {
                $items = $crawler->filter($markup[TargetKey::special(TargetKey::ITEM_WRAPPER)]);
            }

            if (!$items->count()) {
                $this->tell(
                    FormattedMessage::get(FormattedMessage::NO_ITEMS_FOUND_ON_PAGE_N, $this->page)
                );
            }

            $items->each(
                function (Crawler $itemCrawler) use (
                    $markupHasInside,
                    $markupInside,
                    $target,
                    $titleLinkSelector
                ) {
                    $cursor = $target->getCursor();

                    try {
                        $titleLinkCrawler = $markupHasInside ? $itemCrawler : $itemCrawler->filter($titleLinkSelector);
                        $titleLinkText = Scanner::cleanText($titleLinkCrawler->text());
                        $linkCrawler = $titleLinkCrawler->selectLink($titleLinkText);

                        // link crawler is empty in this case the link may be a parent element
                        // so we must find closest link:
                        if (!count($linkCrawler)) {
                            $linkCrawler = $this->nodeProximityAssistant->closest('a[href]', $titleLinkCrawler);
                        }

                        // simply get link from crawler
                        $link = $linkCrawler->link();

                        $this->tell($titleLinkText, self::COMM_DIRECTION_FLAT);
                    } catch (InvalidArgumentException $e) {
                        $this->tell(
                            FormattedMessage::get(
                                ($markupHasInside
                                    ? FormattedMessage::UNABLE_TO_RETRIEVE_SCRAP_FOR_X
                                    : FormattedMessage::NO_TITLE_LINK_FOUND_FOR_X),
                                $cursor + 1
                            )
                        );
                        $this->logger->error($e);

                        // escape 'each' block
                        return;
                    }

                    $target->incrementCursor();

                    if ($markupHasInside) {
                        // grab handle on detail
                        $itemCrawler = $this->client->click($link);
                        $markupHasFocus = !empty($markupInside[TargetKey::special(TargetKey::MARKUP_FOCUS)]);

                        // focus detail crawler on section if specified
                        if ($markupHasFocus) {
                            $itemCrawler = $itemCrawler->filter(
                                $markupInside[TargetKey::special(TargetKey::MARKUP_FOCUS)]
                            );
                        }
                    }

                    // collect the scrap...
                    $this->scrapper->collect(
                        new TitleLink($titleLinkText, $link->getUri()),
                        $target,
                        $itemCrawler
                    );
                }
            );

            if ($this->page < $this->pageLimit && $target->hasPager()) {
                // Look for next page.
                // An InvalidArgumentException may be thrown if a 'next' link does not exist.
                try {
                    // Select pager
                    $pager = $crawler->filter($target->getPager()[TargetKey::PAGER_SELECTOR]);
                    // Grab pager/next link
                    $nextLink = $pager->link();
                    // Click it!
                    $crawler = $this->client->click($nextLink);
                } catch (InvalidArgumentException $e) {
                    // Next link doesn't exist
                    $crawler = null;

                    if ($this->verbosity >= self::VERBOSITY_HIGH) {
                        $errorMessage = FormattedMessage::get(
                            FormattedMessage::TARGET_PAGER_NEXT_NOT_FOUND,
                            $target->getName(),
                            $target->getPager()[TargetKey::PAGER_SELECTOR],
                            $target->getPager()[TargetKey::PAGER_TEXT]
                        );

                        $this->tell($errorMessage);
                        $this->logger->error($errorMessage);
                    }
                }

                ++$this->page;
            } else {
                $crawler = null;
            }

            // back-off
            sleep($this->optionSet->getBackOff());
        } while ($crawler !== null); // unless Crawler died...
    }

    private function getTargetBuilder(): TargetBuilder
    {
        return new TargetBuilder(
            array_filter(array_map(
                'trim',
                explode(',', $this->optionSet->getKeywords() ?? '')
            ))
        );
    }
}
