<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Helper;

use ReliqArts\Scavenger\Service\ConfigProvider;

final class TargetKey
{
    public const string NAME = 'name';

    public const string SEARCH_FORM = 'form';

    public const string SEARCH_FORM_INPUT = 'keyword_input_name';

    public const string SEARCH_FORM_SELECTOR = 'selector';

    public const string SEARCH_FORM_SUBMIT_BUTTON = 'submit_button';

    public const string SEARCH_FORM_SUBMIT_BUTTON_ID = 'id';

    public const string SEARCH_FORM_SUBMIT_BUTTON_TEXT = 'text';

    public const string SEARCH_KEYWORDS = 'keywords';

    public const string EXAMPLE = 'example';

    public const string MODEL = 'model';

    public const string LINK = 'link';

    public const string SOURCE = 'source';

    public const string MARKUP = 'markup';

    public const string MARKUP_TITLE_LINK = 'title_link';

    public const string MARKUP_TITLE = self::TITLE;

    public const string MARKUP_LINK = 'link';

    public const string MARKUP_INSIDE = 'inside';

    public const string MARKUP_FOCUS = 'focus';

    public const string PAGER = 'pager';

    public const string PAGER_SELECTOR = 'selector';

    public const string PAGER_TEXT = 'text';

    public const string PAGES = 'pages';

    public const string POSITION = 'position';

    public const string TITLE = 'title';

    public const string DISSECT = 'dissect';

    public const string PREPROCESS = 'preprocess';

    public const string REMAP = 'remap';

    public const string BAD_WORDS = 'bad_words';

    public const string SEARCH = 'search';

    public const string SEARCH_ENGINE_REQUEST_PAGES = 'serp';

    public const string ITEM_WRAPPER = 'item_wrapper';

    public const string RESULT = 'result';

    public const string RETAIN = 'retain';

    public const string ITEM = 'item';

    public const string WRAPPER = 'wrapper';

    /**
     * TargetKey constructor.
     *
     * @codeCoverageIgnore
     */
    private function __construct() {}

    public static function special(string $keyName): string
    {
        return ConfigProvider::specialKey($keyName);
    }
}
