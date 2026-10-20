<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Helper;

final class FormattedMessage
{
    public const string MISSING_SEARCH_KEY = self::PREFIX_ABORTED.'Search enabled but %s (%s) is not set for target (%s).';

    public const string EXCEPTION_THROWN_FOR_ATTRIBUTE = self::PREFIX_EXCEPTION.'thrown for attribute (%s) on target %s: %s';

    public const string FIRST_RESULT_PAGE_FOR_KEYWORD_ON_TARGET = '1st Result Page for \'%s\' on target \'%s\'';

    public const string PREPROCESS_NOT_CALLABLE = 'Preprocess for attribute %s on target %s is not callable.'.self::SUFFIX_SKIPPED;

    public const string PROCESSING_PAGE_N = 'Processing page %d:';

    public const string NO_ITEMS_FOUND_ON_PAGE_N = 'No items found on page %d.';

    public const string SEARCH_KEYWORD = 'Search keyword %s';

    public const string SCRAP_CONTAINS_BAD_WORD = 'Scrap was found to contain bad words. Discarded -- %s';

    public const string SCRAP_GATHERED = 'Scrap gathered: %s';

    public const string SCRAP_SAVE_EXCEPTION = 'Failed to save scrap: %s. Exception: %s';

    public const string NO_TITLE_LINK_FOUND_FOR_X = 'No title/link found for item which would be at: %d'.self::SUFFIX_SKIPPED;

    public const string UNABLE_TO_RETRIEVE_SCRAP_FOR_X = 'Unable to retrieve scrap which would be at: %d'.self::SUFFIX_SKIPPED;

    public const string EXAMPLE_TARGET = 'Target (%s) is for example purposes only.'.self::SUFFIX_SKIPPED;

    public const string INVALID_SPECIAL_KEY = self::PREFIX_ERROR.'Special key (%s) is not valid.';

    public const string TARGET = 'Target: %s';

    public const string TARGET_MODEL_RESOLUTION_FAILED = 'Could not resolve model for target %s. %s'.self::SUFFIX_SKIPPED;

    public const string TARGET_NAME_MISSING = 'Missing target name. (%s)';

    public const string TARGET_PAGER_NEXT_NOT_FOUND = self::PREFIX_ERROR.'Pager next link could not be found for target (%s). Specified as (%s), with expected text: "%s".';

    public const string TARGET_SOURCE_MISSING = 'Missing source for target (%s).'.self::SUFFIX_SKIPPED;

    public const string TARGET_INVALID_SEARCH_FORM = 'Could not retrieve form for target (%s). Please check configuration.';

    public const string TARGET_UNKNOWN = self::PREFIX_ABORTED.'Unknown target (%s).';

    public const string TARGET_UNEXPECTED_EXCEPTION = self::PREFIX_EXCEPTION.'Target (%s) resulted in exception: %s';

    public const string TARGET_MISSING_TITLE_LINK = 'Missing title link in configuration for target (%s).';

    public const string TARGET_MISSING_ITEM_WRAPPER = 'Neither `inside` nor `item_wrapper` is specified for target (%s).';

    private const string PREFIX_ABORTED = '[!] Aborted - ';

    private const string PREFIX_ERROR = '[!] Error - ';

    private const string PREFIX_EXCEPTION = '[!] Exception - ';

    private const string SUFFIX_SKIPPED = ' - Skipped';

    /**
     * Message constructor.
     */
    private function __construct() {}

    public static function get(string $message, mixed ...$args): string
    {
        return sprintf($message, ...$args);
    }
}
