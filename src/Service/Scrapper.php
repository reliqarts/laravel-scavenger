<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Service;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use JsonException;
use Psr\Log\LoggerInterface;
use ReliqArts\Scavenger\Helper\FormattedMessage;
use ReliqArts\Scavenger\Helper\TargetKey;
use ReliqArts\Scavenger\Model\Scrap;
use ReliqArts\Scavenger\Model\Target;
use ReliqArts\Scavenger\TitleLink;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

class Scrapper extends Communicator
{
    private const ERROR_INVALID_SPECIAL_KEY = '!#ERR - S.Key not valid!';
    private const KEY_PREFIX = ConfigProvider::SPECIAL_KEY_PREFIX;
    private const KEY_TARGET = self::KEY_PREFIX . 'target';
    private const KEY_ID = self::KEY_PREFIX . 'id';
    private const KEY_SERP_RESULT = self::KEY_PREFIX . 'serp_result';
    private const ENCODE_CHARSET = 'UTF-8';

    private LoggerInterface $logger;
    private int $newScrapsCount;
    private Collection $relatedObjects;
    private Scanner $scanner;

    /**
     * Scraps found so far.
     */
    private Collection $scraps;

    /**
     * Scrapper constructor.
     */
    public function __construct(
        LoggerInterface $logger,
        ?Command $callingCommand = null,
        string $hashAlgorithm = self::HASH_ALGORITHM,
        int $verbosity = self::VERBOSITY_LOW
    ) {
        parent::__construct($callingCommand);

        $this->logger = $logger;
        $this->scanner = new Scanner();
        $this->hashAlgorithm = $hashAlgorithm;
        $this->scraps = collect([]);
        $this->relatedObjects = collect([]);
        $this->newScrapsCount = 0;
        $this->verbosity = $verbosity;
    }

    /**
     * Collect scrap array from crawler using markup.
     *
     * @throws JsonException|RuntimeException
     */
    public function collect(TitleLink $titleLink, Target $target, Crawler $crawler): void
    {
        $markup = $target->getMarkup();
        $data = $this->initializeData(
            $titleLink,
            $target,
            $crawler,
            $markup[TargetKey::special(TargetKey::MARKUP_INSIDE)] ?? []
        );
        $data = $this->preprocess($data, $target);

        if (!$this->verifyData($data, $target)) {
            return;
        }

        $data = $this->finalizeData($data, $target);

        $this->scraps->push($this->buildScrapFromData($data));

        // feedback
        $this->tell(
            FormattedMessage::get(FormattedMessage::SCRAP_GATHERED, $data[self::KEY_ID])
            . ($this->verbosity >= self::VERBOSITY_HIGH ? '-- ' . json_encode($data, JSON_THROW_ON_ERROR) : null)
        );
    }

    public function convertScraps(bool $convertDuplicates = false, bool $storeRelatedReferences = false): void
    {
        $this->scraps->map(
            function (Scrap $scrap) use ($convertDuplicates, $storeRelatedReferences) {
                try {
                    $relatedObject = $scrap->convert($convertDuplicates, $storeRelatedReferences);
                    if ($relatedObject !== null) {
                        $this->relatedObjects->push($relatedObject);
                    }
                } catch (QueryException $e) {
                    $this->logger->warning($e, ['scrap' => $scrap]);
                }
            }
        );
    }

    public function getNewScrapsCount(): int
    {
        return $this->newScrapsCount;
    }

    public function getScraps(): Collection
    {
        return $this->scraps;
    }

    public function saveScraps(): void
    {
        $this->scraps->map(
            function (Scrap $scrap): void {
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
                    $this->logger->error($errorMessage);
                }
            }
        );
    }

    /**
     * Retrieve related objects which are a result of scrap conversion.
     */
    public function getRelatedObjects(): Collection
    {
        return $this->relatedObjects;
    }

    /**
     * Initialize data.
     *
     * @noinspection PhpTooManyParametersInspection
     * @noinspection SlowArrayOperationsInLoopInspection
     * @throws RuntimeException
     */
    private function initializeData(
        TitleLink $titleLink,
        Target $target,
        Crawler $crawler,
        array $markupOverride = []
    ): array {
        $markup = !empty($markupOverride) ? $markupOverride : $target->getMarkup();

        $data[TargetKey::TITLE] = $titleLink->getTitle();
        $data[TargetKey::special(TargetKey::LINK)] = $titleLink->getLink();
        $data[TargetKey::special(TargetKey::POSITION)] = $target->getCursor();
        $data[TargetKey::special(TargetKey::SOURCE)] = $titleLink->getLink();
        $data[TargetKey::special(TargetKey::MODEL)] = $target->getModel();

        // build initial scrap data from markup and dissect
        foreach ($markup as $attr => $path) {
            if (ConfigProvider::isSpecialKey($path)) {
                // path is special key, use special key value from scrap
                $data[$attr] = !empty($data[$path]) ? $data[$path] : self::ERROR_INVALID_SPECIAL_KEY;
            } elseif (!ConfigProvider::isSpecialKey($attr)) {
                try {
                    $attrCrawler = $crawler->filter($path);
                    $data[$attr] = $attr === TargetKey::TITLE ? $attrCrawler->text() : $attrCrawler->html();

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
                        $data = array_merge($data, Scanner::pluckDetails($data[$attr], $dissectMap, $retain));

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
                    $this->logger->warning($exMessage, [$markup[$attr]]);
                }
            }
            unset($path);
        }

        return $data;
    }

    private function preprocess(array $data, Target $target): array
    {
        // preprocess and remap scrap data parts
        foreach ($data as $attr => $value) {
            $data[$attr] = $this->encodeAttribute($attr, (string)$value);

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
                        $data[$attr] = $preprocess($data[$attr]);
                    } catch (Exception $e) {
                        $this->logger->error($e);
                    }
                } else {
                    $this->logger->warning(
                        FormattedMessage::get(
                            FormattedMessage::PREPROCESS_NOT_CALLABLE,
                            $attr,
                            $target->getName()
                        )
                    );
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

    private function encodeAttribute(string $attributeName, string $attributeText): string
    {
        // ensure title has UC words
        if ($attributeName === TargetKey::TITLE) {
            return utf8_encode(ucwords(mb_strtolower($attributeText)));
        }

        return iconv(
            mb_detect_encoding($attributeText, mb_detect_order(), true),
            self::ENCODE_CHARSET,
            $attributeText
        );
    }

    /**
     * @throws JsonException
     */
    private function verifyData(array $data, Target $target): bool
    {
        if ($this->scanner->hasBadWords($data, $target->getBadWords())) {
            $badWordMessage = sprintf(
                FormattedMessage::SCRAP_CONTAINS_BAD_WORD,
                json_encode($data, JSON_THROW_ON_ERROR)
            );
            $this->logger->notice($badWordMessage);
            if ($this->verbosity >= self::VERBOSITY_HIGH) {
                $this->tell($badWordMessage);
            }

            return false;
        }

        return true;
    }

    /**
     * Finalize scrap.
     * @throws JsonException
     */
    private function finalizeData(array $data, Target $target): array
    {
        $data[self::KEY_ID] = hash($this->hashAlgorithm, json_encode($data, JSON_THROW_ON_ERROR));
        $data[self::KEY_SERP_RESULT] = $target->isSearchEngineRequestPages();
        $data[self::KEY_TARGET] = $target->getName();

        ksort($data);

        return $data;
    }

    /**
     * @throws JsonException
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
                'data' => json_encode($data, JSON_THROW_ON_ERROR),
            ]
        );

        if (!$scrap->exists) {
            ++$this->newScrapsCount;
        }

        return $scrap;
    }
}
