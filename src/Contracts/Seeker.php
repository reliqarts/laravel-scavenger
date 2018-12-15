<?php

namespace ReliQArts\Scavenger\Contracts;

use Illuminate\Console\Command;
use ReliQArts\Scavenger\DTOs\OptionSet;
use ReliQArts\Scavenger\DTOs\Result;

/**
 * A service that abstracts seeker related methods.
 */
interface Seeker
{
    /** @noinspection PhpTooManyParametersInspection */
    /**
     * Search target site(s) for listings and collect relevant data.
     *
     * @param string|null  $targetName     Target site
     * @param Command|null $callingCommand Calling Command
     *
     * @return Result
     */
    public function seek(?string $targetName = null, ?Command &$callingCommand = null): Result;
}
