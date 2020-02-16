<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Unit\Console\Command;

use PHPUnit\Framework\TestCase;
use ReliqArts\Scavenger\Console\Command\Seek;

/**
 * Class SeekTest.
 *
 * @coversDefaultClass \ReliqArts\Scavenger\Console\Command\Seek
 *
 * @internal
 */
final class SeekTest extends TestCase
{
    /**
     * @var Seek
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new Seek();
    }

    /**
     * @covers ::handle
     */
    public function testHandle(): void
    {
        $this->markTestIncomplete('WIP');
    }
}
