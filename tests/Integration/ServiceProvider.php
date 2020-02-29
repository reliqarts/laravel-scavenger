<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Integration;

use ReliqArts\Scavenger\ServiceProvider as ScavengerServiceProvider;

final class ServiceProvider extends ScavengerServiceProvider
{
    protected const ASSET_DIRECTORY = __DIR__ . '/../Fixtures';
}
