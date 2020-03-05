<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Integration;

use ReliqArts\Scavenger\ScavengerServiceProvider as ServiceProvider;

final class ScavengerServiceProvider extends ServiceProvider
{
    protected const ASSET_DIRECTORY = __DIR__ . '/../Fixtures';
}
