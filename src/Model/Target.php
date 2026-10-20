<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Model;

class Target
{
    private const int INITIAL_CURSOR = 0;

    private int $cursor;

    /**
     * @param  string[]  $badWords
     */
    public function __construct(
        private string $name,
        private string $model,
        private string $source,
        private array $markup,
        private array $pager = [],
        private int $pages = 0,
        private array $dissect = [],
        private array $preprocess = [],
        private array $remap = [],
        private array $badWords = [],
        private array $search = [],
        private bool $searchEngineRequestPages = false,
        private bool $example = false
    ) {
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
        return ! empty($this->search);
    }

    public function getPages(): int
    {
        return $this->pages;
    }

    public function hasPages(): bool
    {
        return ! empty($this->pages);
    }

    public function getPager(): array
    {
        return $this->pager;
    }

    public function hasPager(): bool
    {
        return ! empty($this->pager);
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
        return ! empty($this->dissect);
    }

    public function getPreprocess(): array
    {
        return $this->preprocess;
    }

    public function hasPreprocess(): bool
    {
        return ! empty($this->preprocess);
    }

    public function getRemap(): array
    {
        return $this->remap;
    }

    public function hasRemap(): bool
    {
        return ! empty($this->remap);
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
        return ! empty($this->badWords);
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
        $this->cursor++;
    }
}
