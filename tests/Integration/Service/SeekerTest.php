<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Integration\Service;

use Exception;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use PHPUnit\Framework\Attributes\DataProvider;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReliqArts\Scavenger\Contract\ConfigProvider;
use ReliqArts\Scavenger\Contract\Seeker as SeekerContract;
use ReliqArts\Scavenger\Helper\NodeProximityAssistant;
use ReliqArts\Scavenger\Model\Scrap;
use ReliqArts\Scavenger\OptionSet;
use ReliqArts\Scavenger\Service\Seeker;
use ReliqArts\Scavenger\Tests\Fixtures\Model\BingResult;
use ReliqArts\Scavenger\Tests\Fixtures\Model\Item;
use ReliqArts\Scavenger\Tests\Integration\TestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Link;
use Symfony\Component\HttpFoundation\Request;

final class SeekerTest extends TestCase
{
    use DatabaseMigrations;
    use ProphecyTrait;

    private const string KEY_EXPECTED_TOTAL_SCRAPS = 'scraps';

    private const string KEY_EXPECTED_TOTAL_CONVERTED = 'converted';

    /**
     * @var AbstractBrowser|ObjectProphecy
     */
    private ObjectProphecy $browser;

    private SeekerContract $subject;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $logger = $this->prophesize(LoggerInterface::class);
        $this->browser = $this->prophesize(AbstractBrowser::class);

        $logger
            ->info(Argument::cetera());

        $this->subject = new Seeker(
            $logger->reveal(),
            $this->browser->reveal(),
            resolve(ConfigProvider::class),
            resolve(NodeProximityAssistant::class),
        );
    }

    /**
     * @param  Crawler[]  $nextPages  Next link (and/or item link) pages
     *
     * @throws Exception
     */
    #[DataProvider('seekDataProvider')]
    public function test_seek(
        OptionSet $options,
        Crawler $landingPage,
        array $expectations,
        string $modelClass,
        ?string $targetName = null,
        array $nextPages = []
    ): void {
        $this->browser
            ->request(Request::METHOD_GET, Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($landingPage);
        $this->browser
            ->submit(Argument::type(Form::class))
            ->shouldNotBeCalled();
        $this->browser
            ->click(Argument::type(Link::class))
            ->willReturn(...$nextPages);

        $result = $this->subject->seek($options, $targetName);
        $scraps = Scrap::all();
        $items = call_user_func($modelClass.'::all');

        self::assertTrue($result->isSuccess());
        self::assertEmpty($result->getErrors());
        self::assertSame($expectations[self::KEY_EXPECTED_TOTAL_SCRAPS], $scraps->count());
        self::assertSame($expectations[self::KEY_EXPECTED_TOTAL_CONVERTED], $items->count());
    }

    public static function seekDataProvider(): array
    {
        return [
            'rooms' => [
                new OptionSet,
                self::getPageDOMCrawler('rooms/1.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 2,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 2,
                ],
                Item::class,
                'rooms',
                [
                    self::getPageDOMCrawler('rooms/items/1.html'),
                    self::getPageDOMCrawler('rooms/items/2.html'),
                ],
            ],
            'rooms - no conversion' => [
                new OptionSet(true, false),
                self::getPageDOMCrawler('rooms/1.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 2,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 0,
                ],
                Item::class,
                'rooms',
                [
                    self::getPageDOMCrawler('rooms/items/1.html'),
                    self::getPageDOMCrawler('rooms/items/2.html'),
                    self::getPageDOMCrawler('rooms/items/3.html'),
                    self::getPageDOMCrawler('rooms/items/4.html'),
                    self::getPageDOMCrawler('rooms/items/5.html'),
                    self::getPageDOMCrawler('rooms/items/6.html'),
                ],
            ],
            'SERP' => [
                new OptionSet,
                self::getPageDOMCrawler('bing/1.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 18,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 18,
                ],
                BingResult::class,
                'bing',
                [
                    self::getPageDOMCrawler('bing/last.html'),
                ],
            ],
            'SERP - with 1 page limit' => [
                new OptionSet(true, true, 3, 1),
                self::getPageDOMCrawler('bing/1.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 8,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 8,
                ],
                BingResult::class,
                'bing',
                [
                    self::getPageDOMCrawler('bing/last.html'),
                ],
            ],
        ];
    }

    /**
     * @param  Crawler[]  $searchResultPages
     * @param  Crawler[]  $nextPages  Next link (and/or item link) pages
     *
     * @throws Exception
     */
    #[DataProvider('seekWithSearchDataProvider')]
    public function test_seek_with_search(
        OptionSet $options,
        Crawler $landingPage,
        array $expectations,
        string $modelClass,
        ?string $targetName = null,
        array $searchResultPages = [],
        array $nextPages = []
    ): void {
        $this->browser
            ->request(Request::METHOD_GET, Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($landingPage);
        $this->browser
            ->submit(Argument::type(Form::class))
            ->shouldBeCalled()
            ->willReturn(...$searchResultPages);
        $this->browser
            ->click(Argument::type(Link::class))
            ->willReturn(...$nextPages);

        $result = $this->subject->seek($options, $targetName);
        $scraps = Scrap::all();
        $items = call_user_func($modelClass.'::all');

        self::assertTrue($result->isSuccess());
        self::assertEmpty($result->getErrors());
        self::assertSame($expectations[self::KEY_EXPECTED_TOTAL_SCRAPS], $scraps->count());
        self::assertSame($expectations[self::KEY_EXPECTED_TOTAL_CONVERTED], $items->count());
    }

    public static function seekWithSearchDataProvider(): array
    {
        return [
            'rooms' => [
                new OptionSet,
                self::getPageDOMCrawler('rooms/landing.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 6,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 6,
                ],
                Item::class,
                'rooms (with search)',
                [
                    self::getPageDOMCrawler('rooms/1.html'),
                ],
                [
                    self::getPageDOMCrawler('rooms/items/1.html'),
                    self::getPageDOMCrawler('rooms/items/2.html'),
                    self::getPageDOMCrawler('rooms/items/3.html'),
                    self::getPageDOMCrawler('rooms/items/4.html'),
                    self::getPageDOMCrawler('rooms/items/5.html'),
                    self::getPageDOMCrawler('rooms/items/6.html'),
                ],
            ],
            'rooms - no conversion' => [
                new OptionSet(true, false),
                self::getPageDOMCrawler('rooms/landing.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 6,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 0,
                ],
                Item::class,
                'rooms (with search)',
                [
                    self::getPageDOMCrawler('rooms/1.html'),
                ],
                [
                    self::getPageDOMCrawler('rooms/items/1.html'),
                    self::getPageDOMCrawler('rooms/items/2.html'),
                    self::getPageDOMCrawler('rooms/items/3.html'),
                    self::getPageDOMCrawler('rooms/items/4.html'),
                    self::getPageDOMCrawler('rooms/items/5.html'),
                    self::getPageDOMCrawler('rooms/items/6.html'),
                ],
            ],
            'SERP' => [
                new OptionSet,
                self::getPageDOMCrawler('bing/landing.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 18,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 18,
                ],
                BingResult::class,
                'bing (with search)',
                [
                    self::getPageDOMCrawler('bing/1.html'),
                ],
                [
                    self::getPageDOMCrawler('bing/last.html'),
                ],
            ],
            'SERP - with 1 page limit' => [
                new OptionSet(true, true, 3, 1),
                self::getPageDOMCrawler('bing/landing.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 8,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 8,
                ],
                BingResult::class,
                'bing (with search)',
                [
                    self::getPageDOMCrawler('bing/1.html'),
                ],
                [
                    self::getPageDOMCrawler('bing/last.html'),
                ],
            ],
        ];
    }
}
