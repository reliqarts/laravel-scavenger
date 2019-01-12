<?php

/*
 * @author    ReliQ <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliQArts\Scavenger\Helpers;

final class FormattedMessage
{
    public const MISSING_SEARCH_KEY = self::PREFIX_ABORTED . 'Search enabled but %s (%s) is not set for target (%s).';
    public const EXCEPTION_THROWN_FOR_ATTRIBUTE = self::PREFIX_EXCEPTION . 'thrown for attribute (%s) on target %s: %s';
    public const FIRST_RESULT_PAGE_FOR_KEYWORD_ON_TARGET = '1st Result Page for \'%s\' on target \'%s\'';
    public const PREPROCESS_NOT_CALLABLE = 'Preprocess for attribute %s on target %s is not callable.'
    . self::SUFFIX_SKIPPED;
    public const PROCESSING_PAGE_N = 'Processing page %d:';
    public const NO_ITEMS_FOUND_ON_PAGE_N = 'No items found on page %d.';
    public const SEARCH_KEYWORD = 'Search keyword %s';
    public const SCRAP_CONTAINS_BAD_WORD = 'Scrap was found to contain bad words. Discarded -- %s';
    public const SCRAP_GATHERED = 'Scrap gathered: %s';
    public const SCRAP_SAVE_EXCEPTION = 'Failed to save scrap: %s. Exception: %s';
    public const NO_TITLE_LINK_FOUND_FOR_X = 'No title/link found for item which would be at: %d'
    . self::SUFFIX_SKIPPED;
    public const UNABLE_TO_RETRIEVE_SCRAP_FOR_X = 'Unable to retrieve scrap which would be at: %d'
    . self::SUFFIX_SKIPPED;
    public const EXAMPLE_TARGET = 'Target (%s) is for example purposes only.' . self::SUFFIX_SKIPPED;
    public const INVALID_SPECIAL_KEY = self::PREFIX_ERROR . 'Special key (%s) is not valid.';
    public const TARGET = 'Target: %s';
    public const TARGET_MODEL_RESOLUTION_FAILED = 'Could not resolve model for target %s. %s' . self::SUFFIX_SKIPPED;
    public const TARGET_NAME_MISSING = 'Missing target name. (%s)';
    public const TARGET_PAGER_NEXT_NOT_FOUND = self::PREFIX_ERROR
    .'Pager next link could not be found for target (%s). Specified as (%s), with expected text: "%s".';
    public const TARGET_SOURCE_MISSING = 'Missing source for target (%s).' . self::SUFFIX_SKIPPED;
    public const TARGET_INVALID_SEARCH_FORM = 'Could not retrieve form for target (%s). Please check configuration.';
    public const TARGET_UNKNOWN = self::PREFIX_ABORTED . 'Unknown target (%s).';
    public const TARGET_UNEXPECTED_EXCEPTION = self::PREFIX_EXCEPTION . 'Target (%s) resulted in exception: %s';
    public const TARGET_MISSING_TITLE_LINK = 'Missing title link in configuration for target (%s).';
    public const TARGET_MISSING_ITEM_WRAPPER = 'Neither `inside` nor `item_wrapper` is specified for target (%s).';

    private const PREFIX_ABORTED = '[!] Aborted - ';
    private const PREFIX_ERROR = '[!] Error - ';
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
        return sprintf($message, ...$args);
    }
}
