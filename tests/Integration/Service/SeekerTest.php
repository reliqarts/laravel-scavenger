<?php
/**
 * @noinspection PhpTooManyParametersInspection
 * @noinspection PhpUsageOfSilenceOperatorInspection
 */

declare(strict_types=1);

namespace ReliqArts\Scavenger\Tests\Integration\Service;

use DOMDocument;
use Exception;
use Goutte\Client;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use ReliqArts\Scavenger\Contract\ConfigProvider;
use ReliqArts\Scavenger\Contract\Seeker as SeekerContract;
use ReliqArts\Scavenger\Helper\NodeProximityAssistant;
use ReliqArts\Scavenger\Model\Scrap;
use ReliqArts\Scavenger\OptionSet;
use ReliqArts\Scavenger\Service\Seeker;
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

    private const KEY_EXPECTED_TOTAL_SCRAPS = 'scraps';
    private const KEY_EXPECTED_TOTAL_CONVERTED = 'converted';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Client|ObjectProphecy
     */
    private $goutteClient;

    /**
     * @var SeekerContract
     */
    private $subject;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->goutteClient = $this->prophesize(Client::class);

        $this->logger
            ->info(Argument::cetera())
            ->willReturn();

        $this->subject = new Seeker(
            $this->logger->reveal(),
            $this->goutteClient->reveal(),
            resolve(ConfigProvider::class),
            resolve(NodeProximityAssistant::class),
        );

//        $this->subject = resolve(SeekerContract::class);
    }

    /**
     * @dataProvider seekDataProvider
     *
     * @param Crawler[] $landingPages
     * @param Crawler[] $searchResultPages
     * @param Crawler[] $itemPages
     */
    public function testSeek(
        OptionSet $options,
        array $landingPages,
        array $expectations,
        ?string $targetName = null,
        array $searchResultPages = [],
        array $itemPages = []
    ): void {
        $this->goutteClient
            ->request(Request::METHOD_GET, Argument::type('string'))
            ->shouldBeCalled()
            ->willReturn(...$landingPages);
        $this->goutteClient
            ->submit(Argument::type(Form::class))
            ->shouldBeCalled()
            ->willReturn(...$searchResultPages);
        $this->goutteClient
            ->click(Argument::type(Link::class))
            ->shouldBeCalled()
            ->willReturn(...$itemPages);

        $result = $this->subject->seek($options, $targetName);
        $scraps = Scrap::all();
        $items = Item::all();

        $this->assertTrue($result->isSuccess());
        $this->assertEmpty($result->getErrors());
        $this->assertSame($expectations[self::KEY_EXPECTED_TOTAL_SCRAPS], $scraps->count());
        $this->assertSame($expectations[self::KEY_EXPECTED_TOTAL_CONVERTED], $items->count());
    }

    public function seekDataProvider(): array
    {
        return [
            'rooms' => [
                new OptionSet(),
                [
                    $this->getDOMCrawlerPage('rooms/landing.html'),
                ],
                [
                    self::KEY_EXPECTED_TOTAL_SCRAPS => 6,
                    self::KEY_EXPECTED_TOTAL_CONVERTED => 6,
                ],
                'rooms',
                [
                    $this->getDOMCrawlerPage('rooms/1.html'),
                ],
                [
                    $this->getDOMCrawlerPage('rooms/items/1.html'),
                    $this->getDOMCrawlerPage('rooms/items/2.html'),
                    $this->getDOMCrawlerPage('rooms/items/3.html'),
                    $this->getDOMCrawlerPage('rooms/items/4.html'),
                    $this->getDOMCrawlerPage('rooms/items/5.html'),
                    $this->getDOMCrawlerPage('rooms/items/6.html'),
                ],
            ],
        ];
    }

    private function getDOMCrawlerPage(string $path): Crawler
    {
        $document = new DOMDocument();
        @$document->loadHTML($this->readFixtureFile(sprintf(self::HTML_FIXTURES_DIR . '/%s', $path)));

        $crawler = new Crawler();
        $crawler->addDocument($document);

        return $crawler;
    }
}
