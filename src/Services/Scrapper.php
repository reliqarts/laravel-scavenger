<?php

/*
 * @author    ReliQ <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliQArts\Scavenger\Services;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Monolog\Logger;
use ReliQArts\Scavenger\DTOs\TitleLink;
use ReliQArts\Scavenger\Helpers\Config;
use ReliQArts\Scavenger\Helpers\FormattedMessage;
use ReliQArts\Scavenger\Helpers\TargetKey;
use ReliQArts\Scavenger\Models\Scrap;
use ReliQArts\Scavenger\Models\Target;
use Symfony\Component\DomCrawler\Crawler;

class Scrapper extends Communicator
{
    private const ERROR_INVALID_SPECIAL_KEY = '!#ERR - S.Key not valid!';
    private const KEY_PREFIX = Config::SPECIAL_KEY_PREFIX;
    private const KEY_TARGET = self::KEY_PREFIX . 'target';
    private const KEY_ID = self::KEY_PREFIX . 'id';
    private const KEY_SERP_RESULT = self::KEY_PREFIX . 'serp_result';

    /**
     * @var array[]
     */
    private $raw;

    /**
     * Event logger.
     *
     * @var Logger
     */
    private $log;

    /**
     * @var int
     */
    private $newScrapsCount;

    /**
     * @var Collection
     */
    private $relatedObjects;

    /**
     * @var Scanner
     */
    private $scanner;

    /**
     * Scraps found so far.
     *
     * @var Collection
     */
    private $scraps;

    /**
     * Scrapper constructor.
     *
     * @param Logger       $log
     * @param Scanner      $scanner
     * @param null|Command $callingCommand
     * @param string       $hashAlgorithm
     * @param int          $verbosity
     */
    public function __construct(
        Logger $log,
        Scanner $scanner,
        ?Command $callingCommand,
        string $hashAlgorithm = self::HASH_ALGORITHM,
        int $verbosity = self::VERBOSITY_LOW
    ) {
        parent::__construct($callingCommand);

        $this->raw = [];
        $this->log = $log;
        $this->scanner = $scanner;
        $this->hashAlgorithm = $hashAlgorithm;
        $this->scraps = collect([]);
        $this->relatedObjects = collect([]);
        $this->newScrapsCount = 0;
        $this->verbosity = $verbosity;
    }

    /**
     * Collect scrap array from crawler using markup.
     *
     * @param TitleLink $titleLink
     * @param Target    $target
     * @param Crawler   $crawler
     */
    public function collect(TitleLink $titleLink, Target $target, Crawler $crawler): void
    {
        $markup = $target->getMarkup();
        $data = $this->initializeData(
            $titleLink,
            $target,
            $crawler,
            $markup[
                TargetKey::special(TargetKey::MARKUP_INSIDE)
            ] ?? []
        );
        $data = $this->preprocess($data, $target);

        if (!$this->verifyData($data, $target)) {
            return;
        }

        $data = $this->finalizeData($data, $target);

        $this->raw[$data[self::KEY_ID]] = $data;
        $this->scraps->push($this->buildScrapFromData($data));

        // feedback
        $this->tell(
            FormattedMessage::get(FormattedMessage::SCRAP_GATHERED, $data[self::KEY_ID])
            . ($this->verbosity >= self::VERBOSITY_HIGH ? '-- ' . json_encode($data) : null)
        );
    }

    /**
     * @param bool $convertDuplicates
     * @param bool $storeRelatedReferences
     */
    public function convertScraps(bool $convertDuplicates = false, bool $storeRelatedReferences = false): void
    {
        $this->scraps->map(function (Scrap $scrap) use ($convertDuplicates, $storeRelatedReferences) {
            try {
                $relatedObject = $scrap->convert($convertDuplicates, $storeRelatedReferences);
                if (!empty($relatedObject)) {
                    $this->relatedObjects->push($relatedObject);
                }
            } catch (QueryException $e) {
                $this->log->warning($e, ['scrap' => $scrap]);
            }
        });
    }

    /**
     * @return int
     */
    public function getNewScrapsCount(): int
    {
        return $this->newScrapsCount;
    }

    /**
     * @return Collection
     */
    public function getScraps(): Collection
    {
        return $this->scraps;
    }

    public function saveScraps(): void
    {
        $this->scraps->map(function (Scrap $scrap): void {
            try {
                $scrap->save();
            } catch (QueryException $e) {
                $errorMessage = FormattedMessage::get(
                    FormattedMessage::SCRAP_SAVE_EXCEPTION,
                    $scrap->hash,
                    $e->getMessage()
                );
                if ($this->verbosity >= self::VERBOSITY_HIGH) {
                    $errorMessage .= ' -- ' . $scrap->toJson();
                }

                $this->tell($errorMessage);
                $this->log->error($errorMessage);
            }
        });
    }

    /**
     * Retrieve related objects which are a result of scrap conversion.
     *
     * @return Collection
     */
    public function getRelatedObjects(): Collection
    {
        return $this->relatedObjects;
    }

    /**
     * @param array $data
     *
     * @return Scrap
     */
    private function buildScrapFromData(array $data): Scrap
    {
        $scrap = Scrap::firstOrNew(
            [
                'hash' => $data[self::KEY_ID],
            ],
            [
                'model' => $data[TargetKey::special(TargetKey::MODEL)],
                'source' => $data[TargetKey::special(TargetKey::SOURCE)],
                'title' => $data[TargetKey::TITLE],
                'data' => json_encode($data),
            ]
        );

        if (!$scrap->exists) {
            ++$this->newScrapsCount;
        }

        return $scrap;
    }

    /** @noinspection PhpTooManyParametersInspection */

    /**
     * Initialize data.
     *
     * @param TitleLink $titleLink
     * @param Target    $target
     * @param Crawler   $crawler
     * @param array     $markupOverride
     *
     * @return array
     */
    private function initializeData(TitleLink $titleLink, Target $target, Crawler $crawler, array $markupOverride = [])
    {
        $markup = !empty($markupOverride) ? $markupOverride : $target->getMarkup();

        $data[TargetKey::TITLE] = $titleLink->getTitle();
        $data[TargetKey::special(TargetKey::LINK)] = $titleLink->getLink();
        $data[TargetKey::special(TargetKey::POSITION)] = $target->getCursor();
        $data[TargetKey::special(TargetKey::SOURCE)] = $titleLink->getLink();
        $data[TargetKey::special(TargetKey::MODEL)] = $target->getModel();

        // build initial scrap data from markup and dissect
        foreach ($markup as $attr => $path) {
            if (Config::isSpecialKey($path)) {
                // path is special key, use special key value from scrap
                $data[$attr] = !empty($data[$path]) ? $data[$path] : self::ERROR_INVALID_SPECIAL_KEY;
            } elseif (!Config::isSpecialKey($attr)) {
                try {
                    $data[$attr] = $this->scanner->removeReturnsAndTabs($crawler->filter($path)->text());

                    // split single attributes into multiple based on regex
                    if (!empty($target->getDissect()[$attr])) {
                        $dissectMap = $target->getDissect()[$attr];

                        // check _retain meta property
                        // to determine whether details should be left in source attribute after extraction
                        $retain = empty($dissectMap[TargetKey::special(TargetKey::RETAIN)])
                            ? false
                            : $dissectMap[TargetKey::special(TargetKey::RETAIN)];
                        unset($dissectMap[TargetKey::special(TargetKey::RETAIN)]);

                        // Extract details into scrap
                        $data = array_merge($data, $this->scanner->pluckDetails($data[$attr], $dissectMap, $retain));

                        // unset dissectMap
                        unset($dissectMap);
                    }
                } catch (InvalidArgumentException $e) {
                    $exMessage = FormattedMessage::get(
                        FormattedMessage::EXCEPTION_THROWN_FOR_ATTRIBUTE,
                        $attr,
                        $target->getName(),
                        $e->getMessage()
                    );
                    $this->tell($exMessage);
                    $this->log->warning($exMessage, [$markup[$attr]]);
                }
            }
            unset($path);
        }

        return $data;
    }

    /**
     * @param array  $data
     * @param Target $target
     *
     * @return array
     */
    private function preprocess(array $data, Target $target): array
    {
        // preprocess and remap scrap data parts
        foreach (($dataSnap = $data) as $attr => $value) {
            // ensure title has UC words
            if ($attr === TargetKey::TITLE) {
                $data[$attr] = utf8_encode(ucwords(mb_strtolower($data[$attr])));
            }

            // preprocess
            if (!empty($target->getPreprocess()[$attr])) {
                $preprocess = $target->getPreprocess()[$attr];
                // check for optional third parameter of array, which indicates that callable method needs an instance
                if (is_array($preprocess) && isset($preprocess[2])) {
                    // if callable needs instance, resolve object
                    if ($preprocess[2]) {
                        $preprocess[0] = resolve($preprocess[0]);
                    }
                    unset($preprocess[2]);
                }
                // if preprocess is callable call it on attribute value
                if (is_callable($preprocess)) {
                    try {
                        $data[$attr] = call_user_func($preprocess, $data[$attr]);
                    } catch (Exception $e) {
                        $this->log->error($e);
                    }
                } else {
                    $this->log->warning(FormattedMessage::get(
                        FormattedMessage::PREPROCESS_NOT_CALLABLE,
                        $attr,
                        $target->getName()
                    ));
                }
            }

            // remap entity attribute name if specified
            if (!empty($target->getRemap()[$attr])) {
                $newAttrName = $target->getRemap()[$attr];
                $data[$newAttrName] = !empty($data[$attr]) ? $data[$attr] : null;

                if ($attr !== TargetKey::TITLE) {
                    unset($data[$attr]);
                }
            }
        }

        return $data;
    }

    /**
     * Finalize scrap.
     *
     * @param array  $data
     * @param Target $target
     *
     * @return array
     */
    private function finalizeData(array $data, Target $target)
    {
        $data[self::KEY_ID] = hash($this->hashAlgorithm, json_encode($data));
        $data[self::KEY_SERP_RESULT] = $target->isSearchEngineRequestPages();
        $data[self::KEY_TARGET] = $target->getName();

        ksort($data);

        return $data;
    }

    /**
     * @param array  $data
     * @param Target $target
     *
     * @return bool
     */
    private function verifyData(array $data, Target $target): bool
    {
        if ($this->scanner->hasBadWords($data, $target->getBadWords())) {
            $badWordMessage = sprintf(FormattedMessage::SCRAP_CONTAINS_BAD_WORD, json_encode($data));
            $this->log->notice($badWordMessage);
            if ($this->verbosity >= self::VERBOSITY_HIGH) {
                $this->tell($badWordMessage);
            }

            return false;
        }

        return true;
    }
}
