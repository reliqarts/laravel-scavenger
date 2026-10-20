<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger;

/**
 * A simple combination of **title** and **link**.
 */
final readonly class TitleLink
{
    public function __construct(private string $title, private string $link) {}

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getLink(): string
    {
        return $this->link;
    }
}
