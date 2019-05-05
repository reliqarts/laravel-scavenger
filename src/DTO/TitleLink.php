<?php

/*
 * @author    Reliq <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliqArts\Scavenger\DTO;

/**
 * A simple combination of **title** and **link**.
 */
final class TitleLink
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $link;

    /**
     * TitleLink constructor.
     *
     * @param string $title
     * @param string $link
     */
    public function __construct(string $title, string $link)
    {
        $this->title = $title;
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }
}
