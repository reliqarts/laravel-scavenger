<?php

namespace ReliQArts\Scavenger\Contracts;

/**
 * A service that abstracts seeker related methods.
 */
interface Seeker
{
    /**
     * Search target site(s) for listings and collect relevant data.
     *
     * @param string $target Target site
     * @param bool $keep Whether found listings should be kept.
     * @param string $keywords List of keywords to search (comma separated).
     *
     * @return Illuminate\Support\Collection Retrived data.
     */
    public function seek($target, $keep, $keywords);
}
