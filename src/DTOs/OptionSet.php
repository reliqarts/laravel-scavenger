<?php

/*
 * @author    ReliQ <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliQArts\Scavenger\DTOs;

class OptionSet
{
    /**
     * @var bool
     */
    private $saveScraps;

    /**
     * @var bool
     */
    private $convertScraps;

    /**
     * @var int
     */
    private $backOff;

    /**
     * @var int
     */
    private $pages;

    /**
     * @var null|string
     */
    private $keywords;

    /**
     * OptionSet constructor.
     *
     * @param bool   $saveScraps
     * @param bool   $convertScraps
     * @param int    $backOff
     * @param int    $pages
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

    /**
     * @return bool
     */
    public function isSaveScraps(): bool
    {
        return $this->saveScraps;
    }

    /**
     * @return bool
     */
    public function isConvertScraps(): bool
    {
        return $this->convertScraps;
    }

    /**
     * @return int
     */
    public function getBackOff(): int
    {
        return $this->backOff;
    }

    /**
     * @return int
     */
    public function getPages(): int
    {
        return $this->pages;
    }

    /**
     * @return null|string
     */
    public function getKeywords(): ?string
    {
        return $this->keywords;
    }
}
