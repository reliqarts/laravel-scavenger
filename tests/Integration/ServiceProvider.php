<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Integration;

use ReliqArts\Scavenger\ServiceProvider as PackageServiceProvider;

final class ServiceProvider extends PackageServiceProvider
{
    protected const ASSET_DIRECTORY = __DIR__ . '../../Fixtures';
}
