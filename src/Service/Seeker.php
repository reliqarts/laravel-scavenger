<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Service;

use Exception;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use ReliqArts\Scavenger\Concern\Timed;
use ReliqArts\Scavenger\Contract\Seeker as SeekerInterface;
use ReliqArts\Scavenger\Exception\InvalidTargetDefinition;
use ReliqArts\Scavenger\Factory\TargetBuilder;
use ReliqArts\Scavenger\Helper\Config;
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

    private const DEFAULT_VERBOSITY = 0;
    private const INITIAL_PAGE = 1;
    private const LOG_FILE_PREFIX = 'scavenger-';
    private const LOGGER_NAME = 'Scavenger.Seeker';

    /**
     * Result of operation.
     *
     * @var Result
     */
    public Result $result;

    /**
     * @var OptionSet
     */
    protected OptionSet $optionSet;

    /**
     * HTTP Client.
     *
     * @var Client
     */
    protected Client $client;

    /**
     * Paraphrase Service instance.
     *
     * @var Paraphraser
     */
    protected Paraphraser $paraphraser;

    /**
     * Current loaded configuration.
     *
     * @var array
     */
    private array $config;

    /**
     * Current page.
     *
     * @var int
     */
    private int $page;

    /**
     * Current loaded target configurations.
     *
     * @var array
     */
    private array $targetDefinitions;

    /**
     * Event logger.
     *
     * @var Logger
     */
    private Logger $log;

    /**
     * @var NodeProximityAssistant
     */
    private NodeProximityAssistant $nodeProximityAssistant;

    /**
     * @var int
     */
    private int $pageLimit;

    /**
     * @var Scanner
     */
    private Scanner $scanner;

    /**
     * @var Scrapper
     */
    private Scrapper $scrapper;

    /**
     * @var TargetBuilder
     */
    private TargetBuilder $targetBuilder;

    /**
     * Create a new seeker.
     *
     * @throws Exception
     */
    public function __construct(OptionSet $options, ?Command $callingCommand)
    {
        parent::__construct($callingCommand);

        $this->config = Config::get();
        $this->client = new Client();
        $this->client->setClient(new GuzzleClient(Config::getGuzzleSettings()));
        $this->optionSet = $options;
        $this->page = self::INITIAL_PAGE;
        $this->pageLimit = $options->getPages();
        $this->scanner = new Scanner();
        $this->targetDefinitions = Config::getTargets();
        $this->targetBuilder = new TargetBuilder(
            array_filter(array_map(
                'trim',
                explode(',', $this->optionSet->getKeywords() ?? '')
            )),
            $this->scanner
        );
        $this->nodeProximityAssistant = new NodeProximityAssistant();
        $this->paraphraser = new Paraphraser();
        $this->result = new Result();
        $this->verbosity = !empty($this->config['verbosity']) ? $this->config['verbosity'] : self::DEFAULT_VERBOSITY;
        $this->hashAlgorithm = !empty($this->config['hash_algorithm'])
            ? $this->config['hash_algorithm']
            : self::HASH_ALGORITHM;

        // logger
        $this->log = new Logger(self::LOGGER_NAME);
        $logFilename = self::LOG_FILE_PREFIX . microtime(true);
        $this->log->pushHandler(new StreamHandler(
            storage_path('logs/' . Config::getLogDir() . "/{$logFilename}.log"),
            // critical info. or higher will always be logged regardless of log config
            $this->config['log'] ? Logger::DEBUG : Logger::CRITICAL
        ));

        // scrapper
        $this->scrapper = new Scrapper(
            $this->log,
            $this->scanner,
            $this->callingCommand,
            $this->hashAlgorithm,
            $this->verbosity
        );
    }

    /**
     * {@inheritdoc}
     */
    public function seek(?string $targetName = null): Result
    {
        $this->startTimer();

        $targetDefinitions = $this->targetDefinitions;
        if ($targetName && array_key_exists($targetName, $targetDefinitions)) {
            $targetDefinitions = [$targetName => $targetDefinitions[$targetName]];
        } elseif ($targetName) {
            return $this->result->addError(FormattedMessage::get(FormattedMessage::TARGET_UNKNOWN, $targetName));
        }

        foreach ($targetDefinitions as $targetName => $targetDefinition) {
            try {
                $targetDefinition[TargetKey::NAME] = $targetName;
                $target = $this->targetBuilder->createFromDefinition($targetDefinition);
                // check for page limit override
                if ($target->hasPages()) {
                    $this->pageLimit = $target->getPages();
                }
                $this->crawlTarget($target);
                // reset page limit
                $this->pageLimit = $this->optionSet->getPages();
            } catch (InvalidTargetDefinition $e) {
                $this->tell($e->getMessage());
            } catch (Exception $e) {
                $this->tell(
                    FormattedMessage::get(
                        FormattedMessage::TARGET_UNEXPECTED_EXCEPTION,
                        $targetName,
                        $e->getMessage()
                    )
                );
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
        ];

        return $this->result
            ->setSuccess(true)
            ->setExtra($extra);
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
            $this->log->info('Landing Document', [$crawler->html()]);
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
            $this->log->error($e);
        }

        if ($form instanceof Form) {
            if ($this->verbosity >= self::VERBOSITY_MEDIUM) {
                $this->log->info('FORM', [$form]);
            }

            // Search each phrase/keyword one at a time.
            foreach ($target->getSearch()[TargetKey::SEARCH_KEYWORDS] as $keyword) {
                // set keyword
                $form[$searchFormConfig[TargetKey::SEARCH_FORM_INPUT]] = $keyword;

                $this->tell(FormattedMessage::get(FormattedMessage::SEARCH_KEYWORD, $keyword), self::COMM_DIRECTION_IN);
                // submit search form
                $resultCrawler = $this->client->submit($form);

                if ($this->verbosity >= self::VERBOSITY_MEDIUM) {
                    $this->log->info(
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

    private function scrape(Target $target, Crawler $crawler): void
    {
        $markup = $target->getMarkup();
        $titleLinkSelector = $markup[TargetKey::MARKUP_TITLE];
        $markupInside = $markup[TargetKey::special(TargetKey::MARKUP_INSIDE)] ?? [];
        $markupHasInside = !empty($markupInside);
        $markupHasItemWrapper = false;

        // choose link as title link selector if it is not empty and it is not set to a special key
        if (!(empty($markup[TargetKey::MARKUP_LINK]) || Config::isSpecialKey($markup[TargetKey::MARKUP_LINK]))) {
            $titleLinkSelector = $markup[TargetKey::MARKUP_LINK];
        }

        // determine what item wrapper is
        $markup[TargetKey::special(TargetKey::ITEM_WRAPPER)] = Scanner::firstNonEmpty($markup, [
            TargetKey::special(TargetKey::ITEM_WRAPPER),
            TargetKey::special(TargetKey::RESULT),
            TargetKey::special(TargetKey::ITEM),
            TargetKey::special(TargetKey::WRAPPER),
        ]);
        if (!empty($markup[Config::specialKey(TargetKey::ITEM_WRAPPER)])) {
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
                function ($itemCrawler) use (
                    $markupHasInside,
                    $markupInside,
                    $target,
                    $titleLinkSelector
                ) {
                    $cursor = $target->getCursor();

                    try {
                        /** @noinspection PhpUndefinedMethodInspection */
                        $titleLinkCrawler = $markupHasInside ? $itemCrawler : $itemCrawler->filter($titleLinkSelector);
                        /** @noinspection PhpUndefinedMethodInspection */
                        $titleLinkText = Scanner::cleanText($titleLinkCrawler->text());
                        /** @noinspection PhpUndefinedMethodInspection */
                        $linkCrawler = $titleLinkCrawler->selectLink($titleLinkText);

                        // link crawler is empty in this case the link may be a parent element
                        // so we must find closest link:
                        if (!count($linkCrawler)) {
                            $linkCrawler = $this->nodeProximityAssistant->closest('a[href]', $titleLinkCrawler);
                        }

                        /** @noinspection PhpUndefinedMethodInspection */
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
                        $this->log->error($e);

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
                        $this->log->error($errorMessage);
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
}