<?php

namespace ReliQArts\Scavenger\Traits;

use ReliQArts\Scavenger\Helpers\Config;

/**
 * Scavenger trait.
 */
trait Scanner
{

    /**
     * List of words (regex) we don't want in our scraps.'.
     *
     * @var array
     */
    protected $badWords = [];

    /**
     * Remove tabs and newlines from text.
     *
     * @param string $text
     *
     * @return string Clean text; without newlines and tabs.
     */
    protected function removeReturnsAndTabs(string $text): string
    {
        $cleanedText = preg_replace("/\s{2,}/", ' ', preg_replace("/[\r\n\t]+/", '', $text));
        $cleanedText = str_replace(' / ', null, $cleanedText);

        return $cleanedText;
    }

    /**
     * Convert <br/> to newlines.
     *
     * @param string $text
     *
     * @return string
     */
    protected function br2nl(string $text): string
    {
        return preg_replace("/<br[\/]?>/", "\n", $text);
    }

    /**
     * Scour a string and pluck details.
     *
     * @param string  $string The string to be scoured.
     * @param array   $map    Map to use for detail scouring.
     * @param boolean $retain Whether to leave match in source string.
     *
     * @return array
     */
    protected function pluckDetails(string &$string, array $map = [], bool $retain = false): array
    {
        $details = [];

        // Pluck mapped details from string
        foreach ($map as $attr => $regex) {
            // match and replace details in string
            $string = preg_replace_callback($regex, function ($m) use ($attr, &$details, $retain) {
                // grab match
                $match = trim($m[0]);
                $details[$attr] = $match;
                // return match if it should be left in string
                if ($retain) {
                    return $match;
                }
                /** @noinspection PhpInconsistentReturnPointsInspection */
                return;
            }, $string);
        }

        return $details;
    }

    /**
     * Searches array for needles. The first one found is returned.
     * If needles aren't supplied the first non-empty item in array is returned.
     *
     * @param array $haystack Array to search.
     * @param array $needles  Optional list of items to check for.
     *
     * @return mixed
     */
    protected function firstNonEmpty(array &$haystack, array $needles = [])
    {
        $found = false;

        if (!empty($needles)) {
            foreach ($needles as $value) {
                if (!empty($haystack[$value])) {
                    $found = $haystack[$value];
                    break;
                }
            }
        } else {
            foreach ($haystack as $value) {
                if (!empty($value)) {
                    $found = $value;
                    break;
                }
            }
        }

        return $found;
    }

    /**
     * Determine whether a scrap is has bad words and therefore is unwanted.
     *
     * @param array $scrap
     * @param array $badWords List of words (regex) we don't want in our scraps.'.
     *
     * @return mixed
     */
    protected function hasBadWords(array &$scrap, array $badWords = [])
    {
        $invalid = false;
        $badWords = array_merge($this->badWords, $badWords);

        if (count($badWords)) {
            $badWordsRegex = '/(' . implode(')|(', $badWords) . ')/i';

            // check for bad words
            foreach ($scrap as $attr) {
                if (!Config::isSpecialKey($attr)) {
                    if ($hasBadWords = preg_match($badWordsRegex, $attr)) {
                        $invalid = true;
                        break;
                    }
                }
            }
        }

        return $invalid;
    }
}
