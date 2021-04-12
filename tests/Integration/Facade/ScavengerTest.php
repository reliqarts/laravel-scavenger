<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Integration\Facade;

use Exception;
use ReliqArts\Scavenger\Facade\Scavenger;
use ReliqArts\Scavenger\Tests\Integration\TestCase;

/**
 * @internal
 */
final class ScavengerTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testFacadeResolution(): void
    {
        $scavenger = resolve(\Scavenger::class);

        self::assertInstanceOf(Scavenger::class, $scavenger);
    }
}
