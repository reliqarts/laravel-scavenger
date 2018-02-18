<?php

namespace ReliQArts\Scavenger\Services;

use Log;
use Exception;
use Carbon\Carbon;
use Goutte\Client;
use Monolog\Logger;
use ReflectionException;
use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use ReliQArts\Scavenger\Models\Scrap;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Database\QueryException;
use ReliQArts\Scavenger\Traits\Timeable;
use ReliQArts\Scavenger\ViewModels\Result;
use Symfony\Component\DomCrawler\Form as Form;
use ReliQArts\Scavenger\Helpers\CoreHelper as H;
use ReliQArts\Scavenger\Traits\Scavenger as ScavengerTrait;
use ReliQArts\Scavenger\Contracts\Seeker as SeekerInterface;
use ReliQArts\Scavenger\Services\Paraphraser as ParaphraserService;

class Scavenger implements SeekerInterface
{
    use ScavengerTrait, Timeable;

    /**
     * Current loaded configuration.
     *
     * @var array
     */
    private $config = null;

    /**
     * Current page.
     *
     * @var int
     */
    private $page = 1;

    /**
     * Current loaded target configurations.
     *
     * @var array
     */
    private $targets = null;

    /**
     * Guzzle settings.
     *
     * @var array
     */
    private $guzzleSettings = [
        'timeout' => 60,
    ];

    /**
     * Event logger.
     *
     * @var \Monolog\Logger
     */
    private $log = null;

    /**
     * Log file name.
     *
     * @var string
     */
    private $logFileName = null;

    /**
     * Wait time between each scrape.
     *
     * @var int
     */
    protected $backOff = 0;

    /**
     * Max. number of pages to scrape.
     *
     * @var int
     */
    protected $pageLimit = 0;

    /**
     * Total amount of scraps found.
     *
     * @var int
     */
    protected $totalFound = 0;

    /**
     * Scraps found so far.
     *
     * @var array
     */
    protected $scrapsGathered = [];

    /**
     * HTTP Client instance.
     *
     * @var \Goute\Client
     */
    protected $client = null;

    /**
     * Current target.
     *
     * @var array
     */
    protected $currentTarget = null;

    /**
     * Paraphrase Service instance.
     *
     * @var \App\Contracts\ParaphraseInterface
     */
    protected $paraphraserService;

    /**
     * Result of operation.
     *
     * @var \ReliQArts\Scavenger\ViewModels\Result
     */
    public $result = null;

    /**
     * Create a new seeker.
     *
     * @return void
     */
    public function __construct()
    {
        $this->config = H::getConfig();
        $this->targets = $this->config['targets'];
        $this->client = new Client();
        $this->client->setClient(new GuzzleClient($this->guzzleSettings));
        $this->paraphraserService = new ParaphraserService();
        $this->result = new Result;

        if (!empty($this->config['hash_algorithm'])) {
            $this->hashAlgo = $this->config['hash_algorithm'];
        }

        if (!empty($this->config['verbosity'])) {
            $this->verbosity = $this->config['verbosity'];
        }
        
        // logger config
        $this->log = new Logger('Scavenger.Seeker');
        $this->logFileName = 'scavlog-' . microtime(true);
        $this->log->pushHandler(new StreamHandler(
            storage_path($this->config['storage']['dir'] . "/logs/{$this->logFileName}.log"), 
            // critical info. or higher will always be logged regardless of log config
            $this->config['log'] ? Logger::DEBUG : Logger::CRITICAL
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function seek($target = null, $keep = true, $keywords = null, $convert = true, $backOff = 3, $pageLimit = 0, &$callingCommand = null)
    {
        $this->callingCommand = $callingCommand;
        $this->pageLimit = $pageLimit;
        $result = &$this->result;
        $startTime = microtime(true);
        $config = $this->config;
        $targets = $this->targets;
        $client = $this->client;
        $scraps = collect([]);
        $convFailed = [];
        $related = [];
        $data = [];
        $new = 0;

        // assert keywords
        $keywords = $keywords ? array_map('trim', explode(',', $keywords)) : [];

        if ($target && array_key_exists($target, $targets)) {
            $targets = [$target => $targets[$target]];
        } elseif ($target) {
            $result->error = 'Unknown target.';
        }

        if (!$result->error) {
            foreach ($targets as $targetName => $currentTarget) {
                if (!empty($currentTarget['example']) && $currentTarget['example']) {
                    $this->tell("Target `$targetName` is for example purposes. Skipped.");
                    continue;
                }

                // ensure all is well with target
                try {
                    $currentTarget['model'] = resolve($currentTarget['model']);
                    if (empty($currentTarget['source'])) {
                        $result->error = "Missing source for target `$targetName`.";
                        break;
                    }
                } catch (ReflectionException $e) {
                    $result->error = "Could not find model for target `$targetName`. {$e->getMessage()}";
                    break;
                }
                
                // finalize page limit
                if (empty($pageLimit) && is_numeric($currentTarget['pages'])) {
                    $this->pageLimit = (int)$currentTarget['pages'];
                }

                // all is well, proceed...
                $currentTarget['name'] = $targetName;

                try {
                    $targetScraps = $this->crawl($currentTarget, $keywords, $backOff);
                    $scraps = $scraps->merge($targetScraps);
                } catch (Exception $e) {
                    $this->tell("[!] exception - Target `$targetName` resulted in exception: " . $e->getMessage(), 'out');
                    $this->tell('Please check target configuration.', 'out');
                }

                // reset page limit
                $this->pageLimit = $pageLimit;
            }
        }

        // If keep, save scraps
        if ($scraps->count() && $keep) {
            $scraps->each(function ($psuedoScrap, $key) use (&$new, $convert, &$related, &$convFailed) {
                if ($scrap = Scrap::whereHash($psuedoScrap[H::specialKey('id')])->first()) {
                    // scrap found
                } else {
                    $new++;
                    $scrap = new Scrap();
                    $scrapData = $psuedoScrap;
                    // build scrap info
                    $scrap->hash = $psuedoScrap[H::specialKey('id')];
                    $scrap->model = $psuedoScrap[H::specialKey('model')];
                    $scrap->source = $psuedoScrap[H::specialKey('source')];
                    $scrap->data = json_encode($scrapData);
                    // attempt to guess a title
                    $scrap->title = !empty($scrapData['title']) ? $scrapData['title'] : (!empty($scrapData['name']) ? $scrapData['name'] : null);
                    $scrap->save();
                }

                // Convert 
                if ($convert && $scrap) {
                    $convertDuplicates = $psuedoScrap[H::specialKey('serp_result')];
                    
                    try {
                        // if converted, aggregate
                        if ($relModelObject = $scrap->convert($convertDuplicates)) {
                            $related[$scrap->id] = $relModelObject;
                        }
                    } catch (QueryException $e) {
                        $this->log->warning($e, ['scrap' => $scrap]);
                        $convFailed[] = $scrap;
                    }
                }
            });

            // aggregate unconverted
            if ($convFailedCount = count($convFailed)) {
                $this->tell("\n", 'none');
                $this->tell("Failed Conversion: $convFailedCount", 'none');
                if ($this->verbosity >= 1) {
                    foreach ($convFailed as $scrap) {
                        $this->tell(get_class($scrap) . ' ::: ' . $scrap->title, 'out');
                    }
                }
                $this->tell("\n", 'none');
            }

            // aggregate converted
            if ($relatedCount = count($related)) {
                if (empty($convFailedCount)) $this->tell("\n", 'none');
                $this->tell("Related objects: $relatedCount", 'none');
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
            'unconverted' => count($convFailed),
        ];
        $result->success = !$result->error;

        return $result;
    }

    /**
     * Crawl target(s).
     *
     * @param array $target
     * @param string $keywords Keywords to search for.
     * @param int $backOff Wait time between each scrape.
     * @return array Scraps gathered.
     */
    private function crawl(array $target, $keywords = null, $backOff = 3)
    {
        $log = &$this->log;
        $crawler = $this->client->request('GET', $target['source']);
        $this->currentTarget = &$target;
        $this->backOff = $backOff;

        $this->tell("Target: {$target['name']}", 'in');

        // assert target cursor
        if (!(isset($target['cursor']) && is_numeric($target['cursor']))) $target['cursor'] = 0;

        // do search if search is enabled for target
        if (!empty($target['search'])) {
            // ensure form requirements are set
            if (empty($target['search']['form']['selector'])) {
                // form selector
                $this->tell("[!] aborted - Search is enabled however form selector config is not set! Please set [search][form][selector] for target ({$target['name']}).", 'out');
            } elseif (empty($target['search']['form']['keyword_input_name'])) {
                // form keyword input name
                $this->tell("[!] aborted - Search is enabled however keyword input name is not set! Please set [search][form][keyword_input_name] for target ({$target['name']}).", 'out');
            } elseif (empty($keywords) && empty($target['search']['keywords'])) {
                // search keyword list empty
                $this->tell("[!] aborted - Search keywords not set! Please set [search][keywords] for target ({$target['name']}).", 'out');
            } else {
                $searchSetup = &$target['search'];
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

                        $this->tell("Search keyword: $word", 'in');
                        // submit search form
                        $resultCrawler = $this->client->submit($form);

                        if ($this->verbosity >= 2) {
                            $log->info("1st Result Page for '$word' on target '{$target['name']}'", [$resultCrawler->html()]);
                        }

                        // scav
                        $this->scav($resultCrawler, $target, $word);
                    }
                } else {
                    $this->tell("Could not retrieve form. Please check target configuration.");
                }

                $this->tell("\n", 'none');
            }
        } else {
            // scav
            $this->scav($crawler, $target);
            $this->tell("\n", 'none');
        }

        return $this->scrapsGathered;
    }

    /**
     * Crawl target pages.
     *
     * @param Symfony\Component\DomCrawler\Crawler $crawler
     * @param array $target Target resource.
     * @param mixed $word Current keyword, if any.
     * @return void
     */
    private function scav(&$crawler, &$target, $word = false)
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
        $markup = $target['markup'];
        // determine title link
        $markup['title_link'] = (!(empty($markup['link']) || H::isSpecialKey($markup['link']))) ? $markup['link'] : $markup['title'];

        // determine what item wrapper is
        if (empty($markup[H::specialKey('item_wrapper')])) {
            // find first non-empty value from possible keys
            $markup[H::specialKey('item_wrapper')] = $this->firstNonEmpty($markup, [
                H::specialKey('result'),
                H::specialKey('item'),
                H::specialKey('wrapper'),
            ]);
        }

        if (!empty($markup['title_link'])) {
            // Page by page we go...
            do {
                $this->tell("\nProcessing page $page" . ($word ? " in $word:" : ":"), 'none');

                // Get scraps.
                if (!empty($markup[H::specialKey('inside')])) {
                    // scrape each by carving the insides
                    $crawler
                        ->filter($markup['title_link'])
                        ->each(function ($titleLinkCrawler) use (&$client, &$totalFound, &$scrapsGathered, &$log, &$target, $backOff, $markup) {
                            try {
                                $titleLinkText = $this->cleanText($titleLinkCrawler->text());
                                // Print to output
                                $this->tell("{$titleLinkText}", 'flat');
                                $scrapLink = $titleLinkCrawler->selectLink($titleLinkText)->link();
                            } catch (InvalidArgumentException $e) {
                                $this->tell("Unable to retrieve scrap, skipping scrap which would be: " . ($target['cursor'] + 1));
                                $log->error($e);
                                // escape 'each' block
                                return;
                            }

                            // increment target cursor
                            $target['cursor']++;

                            // increment scraps found
                            $totalFound++;

                            // grab handle on detail
                            $detailCrawler = $client->click($scrapLink);

                            // focus detail crawler on section if specified
                            if (!empty($markup[H::specialKey('inside')][H::specialKey('focus')])) {
                                $detailCrawler = $detailCrawler->filter($markup[H::specialKey('inside')][H::specialKey('focus')]);
                            }

                            // build the scrap...
                            $scrap = [];
                            $scrap['title'] = $titleLinkText;
                            $scrap[H::specialKey('source')] = $scrapLink->getUri();
                            $scrap = $this->buildScrap($detailCrawler, $markup[H::specialKey('inside')], $scrap);
                        });
                } elseif (!empty($markup[H::specialKey('item_wrapper')])) {
                    // grab items
                    $items = $crawler->filter($markup[H::specialKey('item_wrapper')]);
                    // scrape each by item, ideal for scraping a single list, e.g. SERP
                    $items->each(function ($resultCrawler) use (&$client, &$totalFound, &$scrapsGathered, &$log, &$target, $backOff, $markup) {
                        try {
                            $titleLinkCrawler = $resultCrawler->filter($markup['title_link']);
                            $titleLinkText = $this->cleanText($titleLinkCrawler->text());
                            // Print to output
                            $this->tell("{$titleLinkText}", 'flat');
                            // grab link
                            $resultLink = $titleLinkCrawler->selectLink($titleLinkText)->link();
                        } catch (InvalidArgumentException $e) {
                            $this->tell("No title/link found for result which would be at: " . ($target['cursor'] + 1));
                            $log->error($e);
                            // escape 'each' block
                            return;
                        }
                        
                        // increment target cursor
                        $target['cursor']++;

                        // increment scraps found
                        $totalFound++;
                        
                        // build the scrap...
                        $scrap = [];
                        $scrap['title'] = $titleLinkText;
                        $scrap[H::specialKey('link')] = $resultLink->getUri();
                        $scrap = $this->buildScrap($resultCrawler, $markup, $scrap);
                    });
                }

                if ($page >= $pageLimit || empty($target['pager']['selector']) || empty($target['pager']['text'])) {
                    $crawler = false;
                } else {
                    // Look for next page.
                    // An InvalidArgumentException may be thrown if a 'next' link does not exist.
                    try {
                        // Select pager
                        $pager = $crawler->filter($target['pager']['selector']);
                        // Grab pager/next link
                        $nextLink = $pager->selectLink($target['pager']['text'])->Link();
                        // Click it!
                        $crawler = $client->click($nextLink);
                    } catch (InvalidArgumentException $e) {
                        // Next link doesn't exist
                        $crawler = false;
                    }

                    $page++;
                }

                // backoff
                sleep($backOff);
            } while ($crawler); // Crawler died...
        } else {
            $result->error = "Missing title link in configuration for target `{$target['name']}`.";
        }
    }

    /**
     * Build scrap array from crawler using markup.
     *
     * @param Symfony\Component\DomCrawler\Crawler $crawler
     * @param array $markup Target markup.
     * @param array $scrap
     * @param array $target
     * @return array
     */
    private function buildScrap(&$crawler, &$markup, &$scrap = [], $target = false)
    {
        $log = &$this->log;

        if (!$target && is_array($this->currentTarget)) {
            $target = $this->currentTarget;
        }

        // initialize scrap
        $this->initializeScrap($scrap, $target);

        // build initial scrap from markup and dissect
        foreach ($markup as $attr => $path) {
            if (H::isSpecialKey($path)) {
                // path is special key, use special key value from scrap
                $scrap[$attr] = !empty($scrap[$path]) ? $scrap[$path] : '!#ERR - S.Key not valid!';
            } elseif (!H::isSpecialKey($attr)) {
                try {
                    $scrap[$attr] = $this->cleanText($crawler->filter($path)->text());
                    
                    // split single attributes into multiple based on regex
                    if (!empty($target['dissect'][$attr])) {
                        $dissectMap = $target['dissect'][$attr];

                        // check _retain meta property 
                        // to determine whether details should be left in source attribute after extraction
                        $retain = empty($dissectMap[H::specialKey('retain')]) ? false : $dissectMap[H::specialKey('retain')];
                        unset($dissectMap[H::specialKey('retain')]);
                        
                        // Extract details into scrap
                        $scrap = array_merge($scrap, $this->carve($scrap[$attr], $dissectMap, $retain));

                        // unset dissectMap
                        unset($dissectMap);
                    }
                } catch (InvalidArgumentException $e) {
                    $exMessage = "[!] Exception thrown for attribute '{$attr}' on target '{$target['name']}': {$e->getMessage()}";
                    $this->tell($exMessage);
                    $log->warning($exMessage, [$markup[$attr]]);
                }
            }
            unset($path);
        }

        // preprocess and remap scrap parts
        foreach (($scrapSnap = $scrap) as $attr => $value) {
            // preprocess
            if (!empty($target['preprocess'][$attr])) {
                $preprocess = $target['preprocess'][$attr];
                // check for optional third parameter of array, which indicates that callable method needs an instance
                if (is_array($preprocess) && isset($preprocess[2])) {
                    // if callable needs instance, resolve object 
                    if ($preprocess[2]) {
                        $preprocess[0] = resolve($preprocess[0]);
                    }
                    unset($preprocess[2]);
                }
                // if preproccess is callable call it on attribute value
                if (is_callable($preprocess)) {
                    try {
                        $scrap[$attr] = call_user_func($preprocess, $scrap[$attr]);
                    } catch (Exception $e) {
                        $log->error($e);
                    }
                } else {
                    $log->warning("Preprocess for attribute {$attr} on target {$target['name']} is not callable. Skipped.");
                }
            }

            // remap entity attribute name if specified
            if (!empty($target['remap'][$attr])) {
                $newAttrName = $target['remap'][$attr];
                $scrap[$newAttrName] = !empty($scrap[$attr]) ? $scrap[$attr] : null;
                unset($scrap[$attr], $newAttrName);
            }
        }

        // check for bad words
        $badWords = empty($target['bad_words']) ? [] : $target['bad_words'];
        if ($this->hasBadWords($scrap, $badWords)) {
            $badWordMessage = "Scrap was found to contain bad words. Discarded -- " . json_encode($scrap);
            $log->notice($badWordMessage);
            if ($this->verbosity >= 3) {
                $this->tell($badWordMessage);
            }
            return false;
        }

        // make it pretty
        $this->finalizeScrap($scrap, $target);

        $this->tell("Scrap gathered: {$scrap[H::specialKey('id')]}" . ($this->verbosity >= 3 ? "-- " . json_encode($scrap) : null));

        return $this->scrapsGathered[$scrap[H::specialKey('id')]] = $scrap;
    }

    /**
     * Initialize scrap.
     *
     * @param array $scrap Scrap reference.
     * @param array $target Target reference.
     * @return void
     */
    private function initializeScrap(array &$scrap, array &$target)
    {
        $clientLocation = $this->client->getRequest()->getUri();

        $scrap[H::specialKey('page')] = $this->page;
        $scrap[H::specialKey('position')] = $target['cursor'];
        $scrap[H::specialKey('model')] = get_class($target['model']);
        
        // affirm link
        if (empty($scrap[H::specialKey('link')])) {
            $scrap[H::specialKey('link')] = $clientLocation;
        }
        if (empty($scrap[H::specialKey('source')])) {
            $scrap[H::specialKey('source')] = $clientLocation;
        }
    }

    /**
     * Finalize scrap.
     *
     * @param array $scrap Scrap reference.
     * @param array $target Target reference.
     * @return void
     */
    private function finalizeScrap(array &$scrap, array &$target)
    {
        $scrap[H::specialKey('id')] = hash($this->hashAlgo, json_encode($scrap));
        $scrap[H::specialKey('serp_result')] = !empty($target['serp']) ? $target['serp'] : false;;
        $scrap[H::specialKey('target')] = $target['name'];
        
        ksort($scrap);
    }
}
