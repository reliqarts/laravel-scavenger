<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Integration\Facade;

use ReliqArts\Scavenger\Facade\Scavenger;
use ReliqArts\Scavenger\Tests\Integration\TestCase;

/**
 * @internal
 */
final class ScavengerTest extends TestCase
{
    public function testFacadeResolution(): void
    {
        $scavenger = resolve(\Scavenger::class);

        $this->assertInstanceOf(Scavenger::class, $scavenger);
    }
}
