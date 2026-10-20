<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReliqArts\Scavenger\Helper\TargetKey;
use ReliqArts\Scavenger\Service\Scanner;

#[CoversClass(Scanner::class)]
final class ScannerTest extends TestCase
{
    #[DataProvider('hasBadWordsDataProvider')]
    public function test_has_bad_words(array $badWords, array $subject, bool $expectedResult): void
    {
        $scanner = new Scanner($badWords);
        $result = $scanner->hasBadWords($subject);

        self::assertSame($expectedResult, $result);
    }

    public static function hasBadWordsDataProvider(): array
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

    #[DataProvider('pluckDetailsDataProvider')]
    public function test_pluck_details(string $subject, array $map, array $expectedResult): void
    {
        $result = Scanner::pluckDetails($subject, $map, true);

        self::assertSame($expectedResult, $result);
    }

    public static function pluckDetailsDataProvider(): array
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

    #[DataProvider('firstNonEmptyDataProvider')]
    public function test_first_non_empty(array $subject, mixed $expectedResult, array $needles = []): void
    {
        $result = Scanner::firstNonEmpty($subject, $needles);

        self::assertSame($expectedResult, $result);
    }

    public static function firstNonEmptyDataProvider(): array
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

    #[DataProvider('br2nlDataProvider')]
    public function test_br2nl(string $subject, string $expectedResult): void
    {
        $result = Scanner::br2nl($subject);

        self::assertSame($expectedResult, $result);
    }

    public static function br2nlDataProvider(): array
    {
        return [
            [
                'hi<br/>hello<br>',
                "hi\nhello\n",
            ],
        ];
    }

    #[DataProvider('cleanTextDataProvider')]
    public function test_clean_text(string $subject, string $expectedResult): void
    {
        $result = Scanner::cleanText($subject);

        self::assertSame($expectedResult, $result);
    }

    public static function cleanTextDataProvider(): array
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
