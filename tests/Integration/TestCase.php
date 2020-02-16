<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Integration;

use DOMDocument;
use Illuminate\Foundation\Application;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Symfony\Component\DomCrawler\Crawler;

abstract class TestCase extends BaseTestCase
{
    protected const FIXTURES_PATH = __DIR__ . '/../Fixtures/';
    protected const HTML_FIXTURES_DIR = 'html';

    /**
     * @param Application $app
     */
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    protected function readFixtureFile(string $path): ?string
    {
        $fullPath = realpath(self::FIXTURES_PATH . $path);
        if (empty($fullPath)) {
            return null;
        }

        return file_get_contents($fullPath);
    }

    protected function getPageDOMCrawler(string $path): Crawler
    {
        $url = sprintf('https://base.uri/%s', $path);
        $document = new DOMDocument();
        @$document->loadHTML($this->readFixtureFile(sprintf(self::HTML_FIXTURES_DIR . '/%s', $path)));

        return new Crawler($document, $url);
    }
}
