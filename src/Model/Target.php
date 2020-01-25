<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Model;

class Target
{
    private const INITIAL_CURSOR = 0;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var bool
     */
    private bool $example;

    /**
     * @var bool
     */
    private bool $searchEngineRequestPages;

    /**
     * @var string
     */
    private string $model;

    /**
     * Source URL.
     *
     * @var string
     */
    private string $source;

    /**
     * @var array
     */
    private array $search;

    /**
     * @var int
     */
    private int $pages;

    /**
     * @var array
     */
    private array $pager;

    /**
     * @var array
     */
    private array $markup;

    /**
     * @var array
     */
    private array $dissect;

    /**
     * @var array
     */
    private array $preprocess;

    /**
     * @var array
     */
    private array $remap;

    /**
     * @var string[]
     */
    private array $badWords;

    /**
     * @var int
     */
    private int $cursor;

    /**
     * Definition constructor.
     *
     * @param string[] $badWords
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

    public function getName(): string
    {
        return $this->name;
    }

    public function isExample(): bool
    {
        return $this->example;
    }

    public function isSearchEngineRequestPages(): bool
    {
        return $this->searchEngineRequestPages;
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getSearch(): array
    {
        return $this->search;
    }

    public function hasSearch(): bool
    {
        return !empty($this->search);
    }

    public function getPages(): int
    {
        return $this->pages;
    }

    public function hasPages(): bool
    {
        return !empty($this->pages);
    }

    public function getPager(): array
    {
        return $this->pager;
    }

    public function hasPager(): bool
    {
        return !empty($this->pager);
    }

    public function getMarkup(): array
    {
        return $this->markup;
    }

    public function getDissect(): array
    {
        return $this->dissect;
    }

    public function hasDissect(): bool
    {
        return !empty($this->dissect);
    }

    public function getPreprocess(): array
    {
        return $this->preprocess;
    }

    public function hasPreprocess(): bool
    {
        return !empty($this->preprocess);
    }

    public function getRemap(): array
    {
        return $this->remap;
    }

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

    public function hasBadWords(): bool
    {
        return !empty($this->badWords);
    }

    public function getCursor(): int
    {
        return $this->cursor;
    }

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
