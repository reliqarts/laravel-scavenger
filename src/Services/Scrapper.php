<?php

/*
 * @author    ReliQ <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliQArts\Scavenger\Services;

use ReliQArts\Scavenger\Helpers\Config;
use ReliQArts\Scavenger\Traits\Scanner;

class Scrapper
{
    use Scanner;

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
     * Build scrap array from crawler using markup.
     *
     * @param Crawler $crawler
     * @param array   $markup target markup
     * @param array   $scrap
     * @param array   $target
     *
     * @return array|null
     */
    private function buildScrap(&$crawler, &$markup, &$scrap = [], $target = null): ?array
    {
        $log = &$this->log;

        if (!$target && is_array($this->currentTarget)) {
            $target = $this->currentTarget;
        }

        // initialize scrap
        $this->initializeScrap($scrap, $target);

        // build initial scrap from markup and dissect
        foreach ($markup as $attr => $path) {
            if (Config::isSpecialKey($path)) {
                // path is special key, use special key value from scrap
                $scrap[$attr] = !empty($scrap[$path]) ? $scrap[$path] : '!#ERR - S.Key not valid!';
            } elseif (!Config::isSpecialKey($attr)) {
                try {
                    $scrap[$attr] = $this->removeReturnsAndTabs($crawler->filter($path)->text());

                    // split single attributes into multiple based on regex
                    if (!empty($target['dissect'][$attr])) {
                        $dissectMap = $target['dissect'][$attr];

                        // check _retain meta property
                        // to determine whether details should be left in source attribute after extraction
                        $retain = empty($dissectMap[Config::specialKey('retain')])
                            ? false
                            : $dissectMap[Config::specialKey('retain')];
                        unset($dissectMap[Config::specialKey('retain')]);

                        // Extract details into scrap
                        $scrap = array_merge($scrap, $this->pluckDetails($scrap[$attr], $dissectMap, $retain));

                        // unset dissectMap
                        unset($dissectMap);
                    }
                } catch (InvalidArgumentException $e) {
                    $exMessage = FormattedMessage::get(
                        FormattedMessage::EXCEPTION_THROWN_FOR_ATTRIBUTE,
                        $attr,
                        $target['name'],
                        $e->getMessage()
                    );
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
                // if preprocess is callable call it on attribute value
                if (is_callable($preprocess)) {
                    try {
                        $scrap[$attr] = call_user_func($preprocess, $scrap[$attr]);
                    } catch (Exception $e) {
                        $log->error($e);
                    }
                } else {
                    $log->warning(FormattedMessage::get(
                        FormattedMessage::PREPROCESS_NOT_CALLABLE,
                        $attr,
                        $target['name']
                    ));
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
            $badWordMessage = sprintf(FormattedMessage::SCRAP_CONTAINS_BAD_WORD, json_encode($scrap));
            $log->notice($badWordMessage);
            if ($this->verbosity >= 3) {
                $this->tell($badWordMessage);
            }

            return null;
        }

        // make it pretty
        $this->finalizeScrap($scrap, $target);

        $this->tell(
            "Scrap gathered: {$scrap[Config::specialKey('id')]}"
            . ($this->verbosity >= 3 ? '-- ' . json_encode($scrap) : null)
        );

        return $this->scrapsGathered[$scrap[Config::specialKey('id')]] = $scrap;
    }

    /**
     * Initialize scrap.
     *
     * @param array $scrap  scrap reference
     * @param array $target target reference
     */
    private function initializeScrap(array &$scrap, array &$target)
    {
        $clientLocation = $this->client->getRequest()->getUri();

        $scrap[Config::specialKey('page')] = $this->page;
        $scrap[Config::specialKey('position')] = $target['cursor'];
        $scrap[Config::specialKey('model')] = get_class($target['model']);

        // affirm link
        if (empty($scrap[Config::specialKey('link')])) {
            $scrap[Config::specialKey('link')] = $clientLocation;
        }
        if (empty($scrap[Config::specialKey('source')])) {
            $scrap[Config::specialKey('source')] = $clientLocation;
        }
    }

    /**
     * Finalize scrap.
     *
     * @param array $scrap  scrap reference
     * @param array $target target reference
     */
    private function finalizeScrap(array &$scrap, array &$target)
    {
        $scrap[Config::specialKey('id')] = hash($this->hashAlgorithm, json_encode($scrap));
        $scrap[Config::specialKey('serp_result')] = !empty($target['serp']) ? $target['serp'] : false;
        $scrap[Config::specialKey('target')] = $target['name'];

        ksort($scrap);
    }
}