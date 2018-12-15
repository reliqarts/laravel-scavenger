<?php

/*
 * @author    ReliQ <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliQArts\Scavenger\Helpers;

class FormattedMessage
{
    public const MISSING_SEARCH_KEY = self::PREFIX_ABORTED . 'Search enabled but %s (%s) is not set for target (%s).';
    public const EXCEPTION_THROWN_FOR_ATTRIBUTE = self::PREFIX_EXCEPTION . 'thrown for attribute %s on target %s: %s';
    public const PREPROCESS_NOT_CALLABLE = 'Preprocess for attribute %s on target %s is not callable.'
    . self::SUFFIX_SKIPPED;
    public const SCRAP_CONTAINS_BAD_WORD = 'Scrap was found to contain bad words. Discarded -- %s';
    public const EXAMPLE_TARGET = 'Target (%s) is for example purposes only.' . self::SUFFIX_SKIPPED;
    public const TARGET_MODEL_RESOLUTION_FAILED = 'Could not resolve model for target %s. %s' . self::SUFFIX_SKIPPED;
    public const TARGET_SOURCE_MISSING = 'Missing source for target (%s).' . self::SUFFIX_SKIPPED;
    public const TARGET_UNKNOWN = self::PREFIX_ABORTED . 'Unknown target (%s).';

    private const PREFIX_ABORTED = '[!] Aborted - ';
    private const PREFIX_EXCEPTION = '[!] Exception - ';
    private const SUFFIX_SKIPPED = ' - Skipped';

    /**
     * Message constructor.
     */
    private function __construct()
    {
    }

    /**
     * @param string $message
     * @param mixed  ...$args
     *
     * @return string
     */
    public static function get(string $message, ...$args): string
    {
        return sprintf($message, $args);
    }
}
