<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests;

use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as BaseTestCase;
use ReliqArts\Scavenger\ServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }
}
