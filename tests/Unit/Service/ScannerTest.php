<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use ReliqArts\Scavenger\Helper\TargetKey;
use ReliqArts\Scavenger\Service\Scanner;

/**
 * Class ScannerTest.
 *
 * @coversDefaultClass \ReliqArts\Scavenger\Service\Scanner
 *
 * @internal
 */
final class ScannerTest extends TestCase
{
    private Scanner $subject;

    /**
     * @covers ::__construct
     * @covers ::hasBadWords
     * @dataProvider hasBadWordsDataProvider
     */
    public function testHasBadWords(array $badWords, array $subject, bool $expectedResult): void
    {
        $scanner = new Scanner($badWords);
        $result = $scanner->hasBadWords($subject);

        $this->assertSame($expectedResult, $result);
    }

    public function hasBadWordsDataProvider(): array
    {
        return [
            'no bad words' => [
                ['bad'],
                [
                    'foo' => 'bar',
                ],
                false,
            ],
            '1 bad word' => [
                ['creep'],
                [
                    'attr1' => 'bar',
                    'attr2' => 'mi creep inno',
                ],
                true,
            ],
            'bad word in special key' => [
                ['creep'],
                [
                    'attr1' => 'bar',
                    TargetKey::special('attr2') => 'mi creep inno',
                ],
                false,
            ],
        ];
    }

    /**
     * @covers ::pluckDetails
     * @dataProvider pluckDetailsDataProvider
     */
    public function testPluckDetails(string $subject, array $map, array $expectedResult): void
    {
        $result = Scanner::pluckDetails($subject, $map, true);

        $this->assertSame($expectedResult, $result);
    }

    public function pluckDetailsDataProvider(): array
    {
        return [
            'simple phone number' => [
                'my number is 998-2332',
                [
                    'number' => '/\d{3}-\d{4}/',
                ],
                [
                    'number' => '998-2332',
                ],
            ],
        ];
    }

    /**
     * @covers ::firstNonEmpty
     * @dataProvider firstNonEmptyDataProvider
     */
    public function testFirstNonEmpty(array $subject, $expectedResult, array $needles = []): void
    {
        $result = Scanner::firstNonEmpty($subject, $needles);

        $this->assertSame($expectedResult, $result);
    }

    public function firstNonEmptyDataProvider(): array
    {
        return [
            'simple' => [
                [
                    'a' => null,
                    'b' => 'foo',
                    'c' => 'bar',
                ],
                'foo',
            ],
            'with Search' => [
                [
                    'a' => '',
                    'b' => 'foo',
                    'c' => 'bar',
                    'd' => 'bar 21',
                ],
                'bar',
                ['non-existent', 'c', 'd'],
            ],
            'no non-empty' => [
                [
                    'a' => '',
                    'b' => null,
                    'c' => false,
                ],
                null,
            ],
        ];
    }

    /**
     * @covers ::br2nl
     * @dataProvider br2nlDataProvider
     *
     * @param mixed $expectedResult
     */
    public function testBr2nl(string $subject, string $expectedResult): void
    {
        $result = Scanner::br2nl($subject);

        $this->assertSame($expectedResult, $result);
    }

    public function br2nlDataProvider(): array
    {
        return [
            [
                'hi<br/>hello<br>',
                "hi\nhello\n",
            ],
        ];
    }

    /**
     * @covers ::cleanText
     * @covers ::removeReturnsAndTabs
     * @dataProvider cleanTextDataProvider
     *
     * @param mixed $expectedResult
     */
    public function testCleanText(string $subject, string $expectedResult): void
    {
        $result = Scanner::cleanText($subject);

        $this->assertSame($expectedResult, $result);
    }

    public function cleanTextDataProvider(): array
    {
        return [
            [
                "hi\nIt's Jim",
                "hi It's Jim",
            ],
            [
                "hi\tIt's Jim",
                "hi It's Jim",
            ],
            [
                "hi\t\nIt's Jim",
                "hi It's Jim",
            ],
            [
                "hi\t\nIt's Jim. How are \n you?",
                "hi It's Jim. How are you?",
            ],
        ];
    }
}
