<?php

/*
 * @author    ReliQ <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliQArts\Scavenger\Contracts;

use ReliQArts\Scavenger\DTOs\Result;

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
