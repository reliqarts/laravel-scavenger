<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Service;

class Scanner
{
    /**
     * List of words (regex) we don't want in our scraps.'.
     *
     * @var array
     */
    protected array $badWords = [];

    /**
     * Scanner constructor.
     */
    public function __construct(array $badWords = [])
    {
        $this->badWords = $badWords;
    }

    /**
     * Determine whether a scrap data has bad words and therefore is unwanted.
     *
     * @param array $badWords List of words (regex) we don't want in our scraps.'.
     *
     * @return mixed
     */
    public function hasBadWords(array $scrapData, array $badWords = [])
    {
        $invalid = false;
        $badWords = array_merge($this->badWords, $badWords);

        if (count($badWords)) {
            $badWordsRegex = sprintf('/(%s)/i', implode(')|(', $badWords));

            // check for bad words
            foreach ($scrapData as $attr => $value) {
                if (!ConfigProvider::isSpecialKey($attr) && preg_match($badWordsRegex, $value)) {
                    $invalid = true;

                    break;
                }
            }
        }

        return $invalid;
    }

    /**
     * Scour a string and pluck details.
     *
     * @param string $string the string to be scoured
     * @param array  $map    map to use for detail scouring
     * @param bool   $retain whether to leave match in source string
     */
    public static function pluckDetails(string &$string, array $map = [], bool $retain = false): array
    {
        $details = [];

        // Pluck mapped details from string
        foreach ($map as $attr => $regex) {
            // match and replace details in string
            $string = preg_replace_callback($regex, static function ($m) use ($attr, &$details, $retain) {
                // grab match
                $match = trim($m[0]);
                $details[$attr] = $match;
                // return match if it should be left in string
                if ($retain) {
                    return $match;
                }
                // @noinspection PhpInconsistentReturnPointsInspection
            }, $string);
        }

        return $details;
    }

    /**
     * Searches array for needles. The first one found is returned.
     * If needles aren't supplied the first non-empty item in array is returned.
     *
     * @param array $haystack array to search
     * @param array $needles  optional list of items to check for
     *
     * @return mixed
     */
    public static function firstNonEmpty(array &$haystack, array $needles = [])
    {
        if (!empty($needles)) {
            foreach ($needles as $value) {
                if (!empty($haystack[$value])) {
                    return $haystack[$value];
                }
            }
        } else {
            foreach ($haystack as $value) {
                if (!empty($value)) {
                    return $value;
                }
            }
        }

        return null;
    }

    /**
     * Convert <br/> to newlines.
     */
    public static function br2nl(string $text): string
    {
        return preg_replace('/<br[\\/]?>/', "\n", $text);
    }

    public static function cleanText(string $text): string
    {
        return self::removeReturnsAndTabs(strip_tags($text));
    }

    /**
     * Remove tabs and newlines from text.
     */
    private static function removeReturnsAndTabs(string $text): string
    {
        $text = preg_replace('/\\s{2,}/', ' ', preg_replace("/[\r\n\t]+/", ' ', $text));

        return str_replace(' / ', null, $text);
    }
}
