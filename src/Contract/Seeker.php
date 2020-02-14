<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Contract;

use Illuminate\Console\Command;
use ReliqArts\Scavenger\OptionSet;
use ReliqArts\Scavenger\Result;

/**
 * A service that abstracts seeker related methods.
 */
interface Seeker
{
    /**
     * Search target site(s) for listings and collect relevant data.
     *
     * @param OptionSet    $options        Seek Options
     * @param null|string  $targetName     Target
     * @param null|Command $callingCommand current console Command which called this method
     */
    public function seek(
        OptionSet $options,
        ?string $targetName = null,
        ?Command $callingCommand = null
    ): Result;
}
