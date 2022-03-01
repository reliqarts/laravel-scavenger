<?php
/**
 * @noinspection PhpTooManyParametersInspection
 * @noinspection PhpUsageOfSilenceOperatorInspection
 */

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Integration\Service;

use Exception;
use Goutte\Client;
use Illuminate\Foundation\Testing\DatabaseMigrations;
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
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\DomCrawler\Link;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
final class SeekerTest extends TestCase
{
    use DatabaseMigrations;
    use ProphecyTrait;

    private const KEY_EXPECTED_TOTAL_SCRAPS = 'scraps';
    private const KEY_EXPECTED_TOTAL_CONVERTED = 'converted';

    /**
     * @var Client|ObjectProphecy
     */
    private ObjectProphecy $goutteClient;

    /**
     * @var SeekerContract
     */
    private SeekerContract $subject;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $logger = $this->prophesize(LoggerInterface::class);
        $this->goutteClient = $this->prophesize(Client::class);

        $logger
            ->info(Argument::cetera());

        $this->subject = new Seeker(
            $logger->reveal(),
            $this->goutteClient->reveal(),
            resolve(ConfigProvider::class),
            resolve(NodeProximityAssistant::class),
        );
    }

    /**
     * @dataProvider seekDataProvider
     * @medium
     *
     * @param Crawler[] $nextPages Next link (and/or item link) pages
     * @throws Exception
     */
    public function testSeek(
        OptionSet $options,
        Crawler $landingPage,
        array $expectations,
        string $modelClass,
        ?string $targetName = null,
        array $nextPages = []
    ): void {
        $this->goutteClient
            ->request(Request::METHOD_GET, Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($landingPage);
        $this->goutteClient
            ->submit(Argument::type(Form::class))
            ->shouldNotBeCalled();
        $this->goutteClient
            ->click(Argument::type(Link::class))
            ->willReturn(...$nextPages);

        $result = $this->subject->seek($options, $targetName);
        $scraps = Scrap::all();
        $items = call_user_func($modelClass . '::all');

        self::assertTrue($result->isSuccess());
        self::assertEmpty($result->getErrors());
        self::assertSame($expectations[self::KEY_EXPECTED_TOTAL_SCRAPS], $scraps->count());
        self::assertSame($expectations[self::KEY_EXPECTED_TOTAL_CONVERTED], $items->count());
    }

    public function seekDataProvider(): array
    {
        return [
            'rooms' => [
                new OptionSet(),
                $this->getPageDOMCrawler('rooms/1.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 2,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 2,
                ],
                Item::class,
                'rooms',
                [
                    $this->getPageDOMCrawler('rooms/items/1.html'),
                    $this->getPageDOMCrawler('rooms/items/2.html'),
                ],
            ],
            'rooms - no conversion' => [
                new OptionSet(true, false),
                $this->getPageDOMCrawler('rooms/1.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 2,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 0,
                ],
                Item::class,
                'rooms',
                [
                    $this->getPageDOMCrawler('rooms/items/1.html'),
                    $this->getPageDOMCrawler('rooms/items/2.html'),
                    $this->getPageDOMCrawler('rooms/items/3.html'),
                    $this->getPageDOMCrawler('rooms/items/4.html'),
                    $this->getPageDOMCrawler('rooms/items/5.html'),
                    $this->getPageDOMCrawler('rooms/items/6.html'),
                ],
            ],
            'SERP' => [
                new OptionSet(),
                $this->getPageDOMCrawler('bing/1.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 18,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 18,
                ],
                BingResult::class,
                'bing',
                [
                    $this->getPageDOMCrawler('bing/last.html'),
                ],
            ],
            'SERP - with 1 page limit' => [
                new OptionSet(true, true, 3, 1),
                $this->getPageDOMCrawler('bing/1.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 8,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 8,
                ],
                BingResult::class,
                'bing',
                [
                    $this->getPageDOMCrawler('bing/last.html'),
                ],
            ],
        ];
    }

    /**
     * @dataProvider seekWithSearchDataProvider
     * @medium
     *
     * @param Crawler[] $searchResultPages
     * @param Crawler[] $nextPages         Next link (and/or item link) pages
     * @throws Exception
     */
    public function testSeekWithSearch(
        OptionSet $options,
        Crawler $landingPage,
        array $expectations,
        string $modelClass,
        ?string $targetName = null,
        array $searchResultPages = [],
        array $nextPages = []
    ): void {
        $this->goutteClient
            ->request(Request::METHOD_GET, Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn($landingPage);
        $this->goutteClient
            ->submit(Argument::type(Form::class))
            ->shouldBeCalled()
            ->willReturn(...$searchResultPages);
        $this->goutteClient
            ->click(Argument::type(Link::class))
            ->willReturn(...$nextPages);

        $result = $this->subject->seek($options, $targetName);
        $scraps = Scrap::all();
        $items = call_user_func($modelClass . '::all');

        self::assertTrue($result->isSuccess());
        self::assertEmpty($result->getErrors());
        self::assertSame($expectations[self::KEY_EXPECTED_TOTAL_SCRAPS], $scraps->count());
        self::assertSame($expectations[self::KEY_EXPECTED_TOTAL_CONVERTED], $items->count());
    }

    public function seekWithSearchDataProvider(): array
    {
        return [
            'rooms' => [
                new OptionSet(),
                $this->getPageDOMCrawler('rooms/landing.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 6,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 6,
                ],
                Item::class,
                'rooms (with search)',
                [
                    $this->getPageDOMCrawler('rooms/1.html'),
                ],
                [
                    $this->getPageDOMCrawler('rooms/items/1.html'),
                    $this->getPageDOMCrawler('rooms/items/2.html'),
                    $this->getPageDOMCrawler('rooms/items/3.html'),
                    $this->getPageDOMCrawler('rooms/items/4.html'),
                    $this->getPageDOMCrawler('rooms/items/5.html'),
                    $this->getPageDOMCrawler('rooms/items/6.html'),
                ],
            ],
            'rooms - no conversion' => [
                new OptionSet(true, false),
                $this->getPageDOMCrawler('rooms/landing.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 6,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 0,
                ],
                Item::class,
                'rooms (with search)',
                [
                    $this->getPageDOMCrawler('rooms/1.html'),
                ],
                [
                    $this->getPageDOMCrawler('rooms/items/1.html'),
                    $this->getPageDOMCrawler('rooms/items/2.html'),
                    $this->getPageDOMCrawler('rooms/items/3.html'),
                    $this->getPageDOMCrawler('rooms/items/4.html'),
                    $this->getPageDOMCrawler('rooms/items/5.html'),
                    $this->getPageDOMCrawler('rooms/items/6.html'),
                ],
            ],
            'SERP' => [
                new OptionSet(),
                $this->getPageDOMCrawler('bing/landing.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 18,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 18,
                ],
                BingResult::class,
                'bing (with search)',
                [
                    $this->getPageDOMCrawler('bing/1.html'),
                ],
                [
                    $this->getPageDOMCrawler('bing/last.html'),
                ],
            ],
            'SERP - with 1 page limit' => [
                new OptionSet(true, true, 3, 1),
                $this->getPageDOMCrawler('bing/landing.html'),
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 8,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 8,
                ],
                BingResult::class,
                'bing (with search)',
                [
                    $this->getPageDOMCrawler('bing/1.html'),
                ],
                [
                    $this->getPageDOMCrawler('bing/last.html'),
                ],
            ],
        ];
    }
}
