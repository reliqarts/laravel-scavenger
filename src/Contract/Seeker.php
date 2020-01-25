<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Contract;

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
     */
    public function seek(?string $targetName = null): Result;
}
