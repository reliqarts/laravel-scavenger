<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Integration;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param Application $app
     */
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }
}
