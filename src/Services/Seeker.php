<?php

namespace ReliQArts\Scavenger\Services;

use Exception;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use ReliQArts\Scavenger\Contracts\Seeker as SeekerInterface;
use ReliQArts\Scavenger\DTOs\OptionSet;
use ReliQArts\Scavenger\Helpers\Config;
use ReliQArts\Scavenger\Helpers\FormattedMessage;
use ReliQArts\Scavenger\Services\Paraphraser as Paraphraser;
use ReliQArts\Scavenger\Traits\Timed;
use ReliQArts\Scavenger\DTOs\Result;
use ReliQArts\Scavenger\Traits\VariableOutput;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

class Seeker implements SeekerInterface
{
    private const DEFAULT_HASH_ALGORITHM = 'sha512';
    private const DEFAULT_VERBOSITY = 0;
    private const INITIAL_PAGE = 0;
    private const LOG_FILE_PREFIX = 'scavenger-';
    private const LOGGER_NAME = 'Scavenger.Seeker';

    use Timed, VariableOutput;

    /**
     * Result of operation.
     *
     * @var Result
     */
    public $result;

    /**
     * @var OptionSet
     */
    protected $optionSet;

    /**
     * HTTP Client
     *
     * @var Client
     */
    protected $client;

    /**
     * Current target.
     *
     * @var array
     */
    protected $currentTarget;

    /**
     * Hashing algorithm in use.
     *
     * @var string
     */
    protected $hashAlgorithm;

    /**
     * Paraphrase Service instance.
     *
     * @var Paraphraser
     */
    protected $paraphraser;

    /**
     * Current loaded configuration.
     *
     * @var array
     */
    private $config;

    /**
     * Current page.
     *
     * @var int
     */
    private $page;

    /**
     * Current loaded target configurations.
     *
     * @var array
     */
    private $targetDefinitions;

    /**
     * Level of detail.
     *
     * @var integer
     */
    private $verbosity;

    /**
     * Event logger.
     *
     * @var Logger
     */
    private $log;

    /**
     * Log file name.
     *
     * @var string
     */
    private $logFilename;

    /**
     * @var int
     */
    private $pageLimit;

    /**
     * Create a new seeker.
     *
     * @param OptionSet $options
     *
     * @throws Exception
     */
    public function __construct(OptionSet $options)
    {
        $this->config = Config::get();
        $this->client = new Client();
        $this->client->setClient(new GuzzleClient(Config::getGuzzleSettings()));
        $this->optionSet = $options;
        $this->page = self::INITIAL_PAGE;
        $this->pageLimit = $options->getPages();
        $this->targetDefinitions = $this->config['targets'];
        $this->paraphraser = new Paraphraser();
        $this->result = new Result();
        $this->verbosity = !empty($this->config['verbosity']) ? $this->config['verbosity'] : self::DEFAULT_VERBOSITY;
        $this->hashAlgorithm = !empty($this->config['hash_algorithm'])
            ? $this->config['hash_algorithm']
            : self::DEFAULT_HASH_ALGORITHM;

        // logger
        $this->log = new Logger(self::LOGGER_NAME);
        $this->logFilename = self::LOG_FILE_PREFIX . microtime(true);
        $this->log->pushHandler(new StreamHandler(
            storage_path($this->config['storage']['dir'] . "/logs/{$this->logFilename}.log"),
            // critical info. or higher will always be logged regardless of log config
            $this->config['log'] ? Logger::DEBUG : Logger::CRITICAL
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function seek(?string $targetName = null, ?Command &$callingCommand = null): Result
    {
        $this->callingCommand = $callingCommand;
        $result = &$this->result;
        $startTime = microtime(true);
        $targetDefinitions = $this->targetDefinitions;
        $scraps = collect([]);
        $conversionFailed = [];
        $related = [];
        $data = [];
        $new = 0;

        // assert keywords
        $keywords = $this->optionSet->getKeywords()
            ? array_map('trim', explode(',', $this->optionSet->getKeywords()))
            : [];

        if ($targetName && array_key_exists($targetName, $targetDefinitions)) {
            $targetDefinitions = [$targetName => $targetDefinitions[$targetName]];
        } elseif ($targetName) {
            $result = $result->addError(FormattedMessage::get(FormattedMessage::TARGET_UNKNOWN, $targetName));
        }

        if (!$result->hasErrors()) {
            foreach ($targetDefinitions as $targetName => $targetDefinition) {
                $targetDefinition['name'] = $targetName;

                if (!$this->validateTargetDefinition($targetDefinition)) {
                    continue;
                }

                // finalize page limit
                if (empty($pageLimit) && is_numeric($targetDefinition['pages'])) {
                    $this->pageLimit = (int)$targetDefinition['pages'];
                }

                try {
                    $targetScraps = $this->gatherScraps($targetDefinition, $keywords, $this->optionSet->getBackOff());
                    $scraps = $scraps->merge($targetScraps);
                } catch (Exception $e) {
                    $this->tell(
                        "[!] exception - Target `${targetName}` resulted in exception: " . $e->getMessage(),
                        'out'
                    );
                    $this->tell('Please check target configuration.');
                }

                // reset page limit
                $this->pageLimit = $this->optionSet->getPages();
            }
        }

/*        // If keep, save scraps
        if ($scraps->count() && $this->optionSet->isSaveScraps()) {
            $scraps->each(function ($pseudoScrap) use (&$new, &$related, &$conversionFailed) {
                if ($scrap = Scrap::whereHash($pseudoScrap[Config::specialKey('id')])->first()) {
                    // scrap found
                } else {
                    ++$new;
                    $scrap = new Scrap();
                    $scrapData = $pseudoScrap;
                    // build scrap info
                    $scrap->hash = $pseudoScrap[Config::specialKey('id')];
                    $scrap->model = $pseudoScrap[Config::specialKey('model')];
                    $scrap->source = $pseudoScrap[Config::specialKey('source')];
                    $scrap->data = json_encode($scrapData);
                    // attempt to guess a title
                    $scrap->title = !empty($scrapData['title'])
                        ? $scrapData['title'] : (!empty($scrapData['name']) ? $scrapData['name'] : null);
                    $scrap->save();
                }

                // Convert
                if ($this->optionSet->isConvertScraps() && $scrap) {
                    $convertDuplicates = $pseudoScrap[Config::specialKey('serp_result')];

                    try {
                        // if converted, aggregate
                        if ($relModelObject = $scrap->convert($convertDuplicates)) {
                            $related[$scrap->id] = $relModelObject;
                        }
                    } catch (QueryException $e) {
                        $this->log->warning($e, ['scrap' => $scrap]);
                        $conversionFailed[] = $scrap;
                    }
                }
            });

            // aggregate unconverted
            if ($conversionFailedCount = count($conversionFailed)) {
                $this->tell("\n", 'none');
                $this->tell("Failed Conversion: ${conversionFailedCount}", 'none');
                if ($this->verbosity >= 1) {
                    foreach ($conversionFailed as $scrap) {
                        $this->tell(get_class($scrap) . ' ::: ' . $scrap->title, 'out');
                    }
                }
                $this->tell("\n", 'none');
            }

            // aggregate converted
            if ($relatedCount = count($related)) {
                if (empty($conversionFailedCount)) {
                    $this->tell("\n", 'none');
                }
                $this->tell("Related objects: ${relatedCount}", 'none');
                if ($this->verbosity >= 1) {
                    foreach ($related as $r) {
                        $this->tell(get_class($r) . ' ::: ' . $r->getKey(), 'out');
                    }
                }
                $this->tell("\n", 'none');
            }
        }

        $data['scraps'] = $scraps;
        if ($this->verbosity >= 1) {
            $result->data = $data;
        }
        $result->extra = (object)[
            'total' => $scraps->count(),
            'executionTime' => $this->secondsSince($startTime) . 's',
            'new' => $new,
            'converted' => count($related),
            'unconverted' => count($conversionFailed),
        ];
        $result->success = !$result->error;*/

        return $result;
    }

    /**
     * @param array $targetDefinition
     *
     * @return bool
     */
    private function validateTargetDefinition(array $targetDefinition): bool
    {
        $targetName = $targetDefinition['name'];

        if (!empty($targetDefinition['example']) && $targetDefinition['example']) {
            $this->result = $this->result->addError(
                FormattedMessage::get(FormattedMessage::EXAMPLE_TARGET, $targetName)
            );

            return false;
        }

        // ensure all (else) is well with target definition
        try {
            $targetDefinition['model'] = resolve($targetDefinition['model']);
        } catch (Exception $e) {
            $this->result = $this->result->addError(
                FormattedMessage::get(FormattedMessage::TARGET_MODEL_RESOLUTION_FAILED, $targetName, $e->getMessage())
            );

            return false;
        }

        if (empty($targetDefinition['source'])) {
            $this->result = $this->result->addError(
                FormattedMessage::get(FormattedMessage::TARGET_SOURCE_MISSING, $targetName)
            );

            return false;
        }

        return true;
    }

    /**
     * @param array $targetDefinition
     * @param array $keywords keywords to search for
     * @param int   $backOff  wait time between each scrape
     *
     * @return array
     */
    private function gatherScraps(array $targetDefinition, array $keywords = null, int $backOff = 3): array
    {
        $log = &$this->log;
        $crawler = $this->client->request('GET', $targetDefinition['source']);
        $this->currentTarget = &$targetDefinition;
        $this->backOff = $backOff;

        $this->tell("Target: {$targetDefinition['name']}", 'in');

        // assert target cursor
        if (!(isset($targetDefinition['cursor']) && is_numeric($targetDefinition['cursor']))) {
            $targetDefinition['cursor'] = 0;
        }

        // do search if search is enabled for target
        if (!empty($targetDefinition['search'])) {
            // ensure form requirements are set
            if (empty($targetDefinition['search']['form']['selector'])) {
                // form selector
                $this->tell(
                    sprintf(
                        FormattedMessage::MISSING_SEARCH_KEY,
                        'form selector config',
                        '[search][form][selector]',
                        $targetDefinition['name']
                    )
                );
            } elseif (empty($targetDefinition['search']['form']['keyword_input_name'])) {
                // form keyword input name
                $this->tell(
                    sprintf(
                        FormattedMessage::MISSING_SEARCH_KEY,
                        'keyword input name',
                        '[search][form][keyword_input_name]',
                        $targetDefinition['name']
                    )
                );
            } elseif (empty($keywords) && empty($targetDefinition['search']['keywords'])) {
                // search keyword list empty
                $this->tell(
                    sprintf(
                        FormattedMessage::MISSING_SEARCH_KEY,
                        'keywords',
                        '[search][keywords]',
                        $targetDefinition['name']
                    )
                );
            } else {
                $searchSetup = &$targetDefinition['search'];
                // determine keywords
                $keywords = !empty($keywords) ? $keywords : $searchSetup['keywords'];

                if ($this->verbosity >= 2) {
                    $log->info('Landing Document', [$crawler->html()]);
                }

                // Grab search form to search for key phrase.
                $form = $crawler->filter($searchSetup['form']['selector']);
                $submitBtn = null;

                // check if submit button was defined
                if (!empty($searchSetup['form']['submit_button']['id'])) {
                    $submitBtn = $searchSetup['form']['submit_button']['id'];
                } elseif (!empty($searchSetup['form']['submit_button']['text'])) {
                    $submitBtn = $searchSetup['form']['submit_button']['text'];
                }

                try {
                    // grab form
                    if (empty($submitBtn)) {
                        $form = $form->form();
                    } else {
                        $form = $form->selectButton($submitBtn)->form();
                    }
                } catch (InvalidArgumentException $e) {
                    $log->error($e);
                }

                if ($form instanceof Form) {
                    if ($this->verbosity >= 2) {
                        $log->info('FORM', [$form]);
                    }

                    // Search each phrase/keyword one at a time.
                    foreach ($keywords as $word) {
                        // set keyword
                        $form[$searchSetup['form']['keyword_input_name']] = $word;

                        $this->tell("Search keyword: ${word}", 'in');
                        // submit search form
                        $resultCrawler = $this->client->submit($form);

                        if ($this->verbosity >= 2) {
                            $log->info(
                                "1st Result Page for '${word}' on target '{$targetDefinition['name']}'",
                                [$resultCrawler->html()]
                            );
                        }

                        // crawl
                        $this->crawl($resultCrawler, $targetDefinition, $word);
                    }
                } else {
                    $this->tell('Could not retrieve form. Please check target configuration.');
                }

                $this->tell("\n", 'none');
            }
        } else {
            // crawl
            $this->crawl($crawler, $targetDefinition);
            $this->tell("\n", 'none');
        }

        return $this->scrapsGathered;
    }

    /**
     * Crawl target pages.
     *
     * @param Crawler $crawler
     * @param array   $targetDefinition target resource
     * @param mixed   $word             current keyword, if any
     */
    private function crawl(&$crawler, &$targetDefinition, $word = false)
    {
        $log = &$this->log;
        $page = &$this->page;
        $client = &$this->client;
        $result = &$this->result;
        $backOff = &$this->backOff;
        $pageLimit = &$this->pageLimit;
        $totalFound = &$this->totalFound;
        $scrapsGathered = &$this->scrapsGathered;

        // finalize scraping markup
        $markup = $targetDefinition['markup'];
        // determine title-link
        $markup['title_link'] = (!(empty($markup['link']) || Config::isSpecialKey($markup['link'])))
            ? $markup['link']
            : $markup['title'];

        // determine what item wrapper is
        if (empty($markup[Config::specialKey('item_wrapper')])) {
            // find first non-empty value from possible keys
            $markup[Config::specialKey('item_wrapper')] = $this->firstNonEmpty($markup, [
                Config::specialKey('result'),
                Config::specialKey('item'),
                Config::specialKey('wrapper'),
            ]);
        }

        if (!empty($markup['title_link'])) {
            // Page by page we go...
            do {
                $this->tell("\nProcessing page ${page}" . ($word ? " in ${word}:" : ':'), 'none');

                // Get scraps.
                if (!empty($markup[Config::specialKey('inside')])) {
                    // scrape each by carving the insides
                    $crawler
                        ->filter($markup['title_link'])
                        ->each(
                            /**
                             * @param Crawler $titleLinkCrawler
                             */
                            function ($titleLinkCrawler) use (
                                &$client,
                                &$totalFound,
                                &$scrapsGathered,
                                &$log,
                                &$targetDefinition,
                                $backOff,
                                $markup
                            ) {
                                try {
                                    $titleLinkText = $this->removeReturnsAndTabs($titleLinkCrawler->text());
                                    // Print to output
                                    $this->tell("{$titleLinkText}", 'flat');
                                    $scrapLink = $titleLinkCrawler->selectLink($titleLinkText)->link();
                                } catch (InvalidArgumentException $e) {
                                    $this->tell(
                                        'Unable to retrieve scrap, skipping scrap which would be: '
                                        . ($targetDefinition['cursor'] + 1)
                                    );
                                    $log->error($e);
                                    // escape 'each' block
                                    return;
                                }

                                // increment target cursor
                                ++$targetDefinition['cursor'];

                                // increment scraps found
                                ++$totalFound;

                                // grab handle on detail
                                $detailCrawler = $client->click($scrapLink);

                                // focus detail crawler on section if specified
                                if (!empty($markup[Config::specialKey('inside')][Config::specialKey('focus')])) {
                                    $detailCrawler = $detailCrawler->filter(
                                        $markup[Config::specialKey('inside')][Config::specialKey('focus')]
                                    );
                                }

                                // build the scrap...
                                $scrap = [];
                                $scrap['title'] = $titleLinkText;
                                $scrap[Config::specialKey('source')] = $scrapLink->getUri();
                                $scrap = $this->buildScrap(
                                    $detailCrawler,
                                    $markup[Config::specialKey('inside')],
                                    $scrap
                                );
                            }
                        );
                } elseif (!empty($markup[Config::specialKey('item_wrapper')])) {
                    // grab items
                    $items = $crawler->filter($markup[Config::specialKey('item_wrapper')]);
                    // scrape each by item, ideal for scraping a single list, e.g. SERP
                    $items->each(function ($resultCrawler) use (
                        &$client,
                        &$totalFound,
                        &$scrapsGathered,
                        &$log,
                        &$targetDefinition,
                        $backOff,
                        $markup
                    ) {
                        try {
                            $titleLinkCrawler = $resultCrawler->filter($markup['title_link']);
                            $titleLinkText = $this->removeReturnsAndTabs($titleLinkCrawler->text());
                            // Print to output
                            $this->tell("{$titleLinkText}", 'flat');
                            // grab link
                            $resultLink = $titleLinkCrawler->selectLink($titleLinkText)->link();
                        } catch (InvalidArgumentException $e) {
                            $this->tell('No title/link found for result which would be at: ' . ($targetDefinition['cursor'] + 1));
                            $log->error($e);
                            // escape 'each' block
                            return;
                        }

                        // increment target cursor
                        ++$targetDefinition['cursor'];

                        // increment scraps found
                        ++$totalFound;

                        // build the scrap...
                        $scrap = [];
                        $scrap['title'] = $titleLinkText;
                        $scrap[Config::specialKey('link')] = $resultLink->getUri();
                        $scrap = $this->buildScrap($resultCrawler, $markup, $scrap);
                    });
                }

                if ($page >= $pageLimit || empty($targetDefinition['pager']['selector']) || empty($targetDefinition['pager']['text'])) {
                    $crawler = false;
                } else {
                    // Look for next page.
                    // An InvalidArgumentException may be thrown if a 'next' link does not exist.
                    try {
                        // Select pager
                        $pager = $crawler->filter($targetDefinition['pager']['selector']);
                        // Grab pager/next link
                        $nextLink = $pager->selectLink($targetDefinition['pager']['text'])->Link();
                        // Click it!
                        $crawler = $client->click($nextLink);
                    } catch (InvalidArgumentException $e) {
                        // Next link doesn't exist
                        $crawler = false;
                    }

                    ++$page;
                }

                // back-off
                sleep($backOff);
            } while ($crawler); // unless Crawler died...
        } else {
            $result->error = "Missing title link in configuration for target `{$targetDefinition['name']}`.";
        }
    }
}
