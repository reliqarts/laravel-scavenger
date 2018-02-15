<?php

namespace ReliQArts\Scavenger\Traits;

use App;
use Log;
use Exception;
use Carbon\Carbon;
use ReliQArts\Scavenger\Helpers\CoreHelper as Helper;

/**
 * Scavenger trait.
 */
trait Scavenger
{

    /**
     * List of words (regex) we don't want in our scraps.'.
     *
     * @var array
     */
    protected $badWords = [];

    /**
     * Calling command if running in console.
     *
     * @var Illuminate\Console\Command
     */
    protected $callingCommand = null;

    /**
     * Level of detail.
     *
     * @var integer
     */
    protected $verbosity = 0;

    /**
     * Hashing algorithm in use.
     *
     * @var string
     */
    protected $hashAlgo = 'sha512';

    /**
     * Remove tabs and newlines from text.
     *
     * @param string $text
     * @return string Clean text; without newlines and tabs.
     */
    protected function cleanText($text)
    {
        $t = preg_replace("/\s{2,}/", ' ', preg_replace("/[\r\n\t]+/", '', $text));
        $t = str_replace(' / ', null, $t);

        return $t;
    }

    /**
     * Convert <br/> to newlines.
     *
     * @param string $text
     * @return string
     */
    protected function br2nl($text)
    {
        return preg_replace("/<br[\/]?>/", "\n", $text);
    }

    /**
     * Salvage a string for details. These details are also removed/plucked from the string.
     *
     * @param string $string The string to be scoured.
     * @param array $map Map to use for detail scouring.
     * @param boolean $leaveInString Whether to leave match in source string.
     * @return array Details found array.
     */
    protected function carve(&$string, $map = [], $retain = false)
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
     * @param array $needles Optional list of items to check for.
     * @return mixed
     */
    protected function firstNonEmpty(array &$haystack, array $needles = [])
    {
        $found = false;

        if (!empty($needles)) {
            foreach ($needles as $value) {
                if(!empty($haystack[$value])) {
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
     * @return mixed
     */
    protected function hasBadWords(&$scrap, array $badWords = [])
    {
        $invalid = false;
        $badWords = array_merge($this->badWords, $badWords);

        if (count($badWords)) {
            $badWordsRegex = '/('.implode(')|(', $badWords).')/i';
    
            // check for bad words
            foreach ($scrap as $attr) {
                if (!Helper::isSpecialKey($attr)) {
                    if ($hasBadWords = preg_match($badWordsRegex, $attr)) {
                        $invalid = true;
                        break;
                    }
                }
            }
        }

        return $invalid;
    }

    /**
     * Print to console or screen.
     *
     * @param string $text
     * @param string $direction in|out
     * @return string
     */
    protected function tell($text, $direction = 'out')
    {
        $direction = strtolower($direction);
        $nl = App::runningInConsole() ? "\n" : '<br/>';
        $dirSymbol = ($direction == 'in' ? '>> ' : ($direction == 'flat' ? '-- ' : '<< '));
        if ($direction == 'none') {
            $dirSymbol = '';
        }

        if (App::runningInConsole() && $this->callingCommand) {
            if ($direction == 'out') {
                $this->callingCommand->line("<info>\<\< {$text}</info>");
            } else {
                $this->callingCommand->line("$dirSymbol$text");
            }
        } else {
            print "$nl$dirSymbol$text";
        }

        return $text;
    }
}
