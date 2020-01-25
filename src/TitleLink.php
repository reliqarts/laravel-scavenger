<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger;

/**
 * A simple combination of **title** and **link**.
 */
final class TitleLink
{
    /**
     * @var string
     */
    private string $title;

    /**
     * @var string
     */
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
