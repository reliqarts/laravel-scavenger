<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Integration\Service;

use ReliqArts\Scavenger\Contract\Seeker;
use ReliqArts\Scavenger\OptionSet;
use ReliqArts\Scavenger\Tests\Integration\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class SeekerTest extends TestCase
{
    /**
     * @var Seeker
     */
    private $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = resolve(Seeker::class);
    }

    /**
     * @dataProvider seekDataProvider
     */
    public function testSeek(OptionSet $options, ?string $targetName = null): void
    {
        $result = $this->subject->seek($options, $targetName);

        $this->assertEmpty($result->getErrors());
    }

    public function seekDataProvider(): array
    {
        return [
            [
                new OptionSet(),
                null,
            ],
        ];
    }
}
