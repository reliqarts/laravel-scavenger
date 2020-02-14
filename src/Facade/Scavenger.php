<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Facade;

use Illuminate\Support\Facades\Facade;
use ReliqArts\Scavenger\Contract\Seeker;

final class Scavenger extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Seeker::class;
    }
}
