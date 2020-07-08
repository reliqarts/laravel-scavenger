<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger;

final class OptionSet
{
    private const DEFAULT_PAGE_LIMIT = 999999;

    private bool $saveScraps;
    private bool $convertScraps;
    private int $backOff;
    private int $pages;
    private ?string $keywords;

    /**
     * @codeCoverageIgnore
     */
    public function __construct(
        bool $saveScraps = true,
        bool $convertScraps = true,
        int $backOff = 3,
        int $pages = self::DEFAULT_PAGE_LIMIT,
        ?string $keywords = null
    ) {
        $this->saveScraps = $saveScraps;
        $this->convertScraps = $convertScraps;
        $this->backOff = $backOff;
        $this->pages = $pages;
        $this->keywords = $keywords;
    }

    public function isSaveScraps(): bool
    {
        return $this->saveScraps;
    }

    public function isConvertScraps(): bool
    {
        return $this->convertScraps;
    }

    public function getBackOff(): int
    {
        return $this->backOff;
    }

    public function getPages(): int
    {
        return $this->pages;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }
}
