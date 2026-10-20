<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger;

final readonly class OptionSet
{
    private const int DEFAULT_PAGE_LIMIT = 999999;

    /**
     * @codeCoverageIgnore
     */
    public function __construct(
        private bool $saveScraps = true,
        private bool $convertScraps = true,
        private int $backOff = 3,
        private int $pages = self::DEFAULT_PAGE_LIMIT,
        private ?string $keywords = null
    ) {}

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
