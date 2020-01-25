<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger;

final class OptionSet
{
    /**
     * @var bool
     */
    private bool $saveScraps;

    /**
     * @var bool
     */
    private bool $convertScraps;

    /**
     * @var int
     */
    private int $backOff;

    /**
     * @var int
     */
    private int $pages;

    /**
     * @var null|string
     */
    private ?string $keywords;

    /**
     * OptionSet constructor.
     *
     * @param string $keywords
     */
    public function __construct(
        bool $saveScraps = true,
        bool $convertScraps = true,
        int $backOff = 3,
        int $pages = 0,
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
