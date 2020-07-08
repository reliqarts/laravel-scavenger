<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger;

/**
 * A simple combination of **title** and **link**.
 */
final class TitleLink
{
    private string $title;
    private string $link;

    /**
     * TitleLink constructor.
     */
    public function __construct(string $title, string $link)
    {
        $this->title = $title;
        $this->link = $link;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getLink(): string
    {
        return $this->link;
    }
}
