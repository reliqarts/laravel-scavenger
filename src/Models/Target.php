<?php

/*
 * @author    ReliQ <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliQArts\Scavenger\Models;

class Target
{
    private const INITIAL_CURSOR = 0;

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $example;

    /**
     * @var bool
     */
    private $searchEngineRequestPages;

    /**
     * @var string
     */
    private $model;

    /**
     * Source URL.
     *
     * @var string
     */
    private $source;

    /**
     * @var array
     */
    private $search;

    /**
     * @var int
     */
    private $pages;

    /**
     * @var array
     */
    private $pager;

    /**
     * @var array
     */
    private $markup;

    /**
     * @var array
     */
    private $dissect;

    /**
     * @var array
     */
    private $preprocess;

    /**
     * @var array
     */
    private $remap;

    /**
     * @var string[]
     */
    private $badWords;

    /**
     * @var int
     */
    private $cursor;

    /**
     * Definition constructor.
     *
     * @param string   $name
     * @param string   $model
     * @param string   $source
     * @param array    $markup
     * @param array    $pager
     * @param int      $pages
     * @param array    $dissect
     * @param array    $preprocess
     * @param array    $remap
     * @param string[] $badWords
     * @param array    $search
     * @param bool     $searchEngineRequestPages
     * @param bool     $example
     */
    public function __construct(
        string $name,
        string $model,
        string $source,
        array $markup,
        array $pager = [],
        int $pages = 0,
        array $dissect = [],
        array $preprocess = [],
        array $remap = [],
        array $badWords = [],
        array $search = [],
        bool $searchEngineRequestPages = false,
        bool $example = false
    ) {
        $this->name = $name;
        $this->example = $example;
        $this->searchEngineRequestPages = $searchEngineRequestPages;
        $this->model = $model;
        $this->source = $source;
        $this->search = $search;
        $this->pages = $pages;
        $this->pager = $pager;
        $this->markup = $markup;
        $this->dissect = $dissect;
        $this->preprocess = $preprocess;
        $this->remap = $remap;
        $this->badWords = $badWords;
        $this->cursor = self::INITIAL_CURSOR;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isExample(): bool
    {
        return $this->example;
    }

    /**
     * @return bool
     */
    public function isSearchEngineRequestPages(): bool
    {
        return $this->searchEngineRequestPages;
    }

    /**
     * @return string
     */
    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * @return string
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @return array
     */
    public function getSearch(): array
    {
        return $this->search;
    }

    /**
     * @return bool
     */
    public function hasSearch(): bool
    {
        return !empty($this->search);
    }

    /**
     * @return int
     */
    public function getPages(): int
    {
        return $this->pages;
    }

    /**
     * @return bool
     */
    public function hasPages(): bool
    {
        return !empty($this->pages);
    }

    /**
     * @return array
     */
    public function getPager(): array
    {
        return $this->pager;
    }

    /**
     * @return bool
     */
    public function hasPager(): bool
    {
        return !empty($this->pager);
    }

    /**
     * @return array
     */
    public function getMarkup(): array
    {
        return $this->markup;
    }

    /**
     * @return array
     */
    public function getDissect(): array
    {
        return $this->dissect;
    }

    /**
     * @return bool
     */
    public function hasDissect(): bool
    {
        return !empty($this->dissect);
    }

    /**
     * @return array
     */
    public function getPreprocess(): array
    {
        return $this->preprocess;
    }

    /**
     * @return bool
     */
    public function hasPreprocess(): bool
    {
        return !empty($this->preprocess);
    }

    /**
     * @return array
     */
    public function getRemap(): array
    {
        return $this->remap;
    }

    /**
     * @return bool
     */
    public function hasRemap(): bool
    {
        return !empty($this->remap);
    }

    /**
     * @return string[]
     */
    public function getBadWords(): array
    {
        return $this->badWords;
    }

    /**
     * @return bool
     */
    public function hasBadWords(): bool
    {
        return !empty($this->badWords);
    }

    /**
     * @return int
     */
    public function getCursor(): int
    {
        return $this->cursor;
    }

    /**
     * @param int $cursor
     */
    public function setCursor(int $cursor): void
    {
        $this->cursor = $cursor;
    }

    /**
     * Increment cursor by 1.
     */
    public function incrementCursor(): void
    {
        ++$this->cursor;
    }
}
