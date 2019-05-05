<?php

/*
 * @author    Reliq <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliqArts\Scavenger\Contracts;

use ReliqArts\Scavenger\VO\Result;

/**
 * A service that abstracts seeker related methods.
 */
interface Seeker
{
    /**
     * Search target site(s) for listings and collect relevant data.
     *
     * @param null|string $targetName Target
     *
     * @return Result
     */
    public function seek(?string $targetName = null): Result;
}
