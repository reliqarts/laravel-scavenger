<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionMethod;
use ReliqArts\Scavenger\Helper\TargetKey;
use ReliqArts\Scavenger\Service\Scrapper;

#[CoversClass(Scrapper::class)]
final class ScrapperTest extends TestCase
{
    public function test_title_is_title_cased(): void
    {
        self::assertSame('Hello World', $this->encode(TargetKey::TITLE, 'hello world'));
    }

    public function test_utf8_title_is_still_read_as_latin1(): void
    {
        self::assertSame('CafÃ© Title', $this->encode(TargetKey::TITLE, 'café title'));
    }

    public function test_attribute_with_undetectable_encoding_is_unchanged(): void
    {
        self::assertSame("\x80", $this->encode('body', "\x80"));
    }

    private function encode(string $key, string $value): string
    {
        $method = new ReflectionMethod(Scrapper::class, 'encodeAttribute');
        $encoded = $method->invoke(new Scrapper(new NullLogger), $key, $value);

        self::assertIsString($encoded);

        return $encoded;
    }
}
