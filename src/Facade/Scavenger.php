<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Facade;

use Illuminate\Support\Facades\Facade;
use Override;
use ReliqArts\Scavenger\Contract\Seeker;

/**
 * @codeCoverageIgnore
 */
final class Scavenger extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return Seeker::class;
    }
}
