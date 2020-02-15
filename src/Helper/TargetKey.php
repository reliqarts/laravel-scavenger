<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Helper;

use ReliqArts\Scavenger\Service\ConfigProvider;

final class TargetKey
{
    public const NAME = 'name';
    public const SEARCH_FORM = 'form';
    public const SEARCH_FORM_INPUT = 'keyword_input_name';
    public const SEARCH_FORM_SELECTOR = 'selector';
    public const SEARCH_FORM_SUBMIT_BUTTON = 'submit_button';
    public const SEARCH_FORM_SUBMIT_BUTTON_ID = 'id';
    public const SEARCH_FORM_SUBMIT_BUTTON_TEXT = 'text';
    public const SEARCH_KEYWORDS = 'keywords';
    public const EXAMPLE = 'example';
    public const MODEL = 'model';
    public const LINK = 'link';
    public const SOURCE = 'source';
    public const MARKUP = 'markup';
    public const MARKUP_TITLE_LINK = 'title_link';
    public const MARKUP_TITLE = self::TITLE;
    public const MARKUP_LINK = 'link';
    public const MARKUP_INSIDE = 'inside';
    public const MARKUP_FOCUS = 'focus';
    public const PAGER = 'pager';
    public const PAGER_SELECTOR = 'selector';
    public const PAGER_TEXT = 'text';
    public const PAGES = 'pages';
    public const POSITION = 'position';
    public const TITLE = 'title';
    public const DISSECT = 'dissect';
    public const PREPROCESS = 'preprocess';
    public const REMAP = 'remap';
    public const BAD_WORDS = 'bad_words';
    public const SEARCH = 'search';
    public const SEARCH_ENGINE_REQUEST_PAGES = 'serp';
    public const ITEM_WRAPPER = 'item_wrapper';
    public const RESULT = 'result';
    public const RETAIN = 'retain';
    public const ITEM = 'item';
    public const WRAPPER = 'wrapper';

    /**
     * TargetKey constructor.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    public static function special(string $keyName): string
    {
        return ConfigProvider::specialKey($keyName);
    }
}
