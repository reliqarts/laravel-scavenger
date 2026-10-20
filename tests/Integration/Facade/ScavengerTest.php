<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Integration\Facade;

use Exception;
use ReliqArts\Scavenger\Contract\Seeker as SeekerContract;
use ReliqArts\Scavenger\Facade\Scavenger;
use ReliqArts\Scavenger\Service\Seeker;
use ReliqArts\Scavenger\Tests\Integration\TestCase;

final class ScavengerTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test_facade_resolution(): void
    {
        $scavenger = resolve(\Scavenger::class);

        self::assertInstanceOf(Scavenger::class, $scavenger);
        self::assertInstanceOf(Seeker::class, resolve(SeekerContract::class));
    }
}
