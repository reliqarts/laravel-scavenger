<?php

declare(strict_types=1);

namespace ReliqArts\Scavenger\Service;

use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use ReliqArts\Scavenger\Contract\Paraphraser as ParaphraserContract;
use Throwable;

final class Paraphraser implements ParaphraserContract
{
    /**
     * Mapping of resources and url.
     *
     * @const array
     */
    private const API_URL = 'http://script4.prothemes.biz/php/process.php';

    /**
     * HTTP Client instance.
     */
    private GuzzleClient $client;

    /**
     * Guzzle settings.
     *
     * @var array
     */
    private array $guzzleSettings = [
        'timeout' => -1,
        'defaults' => [
            'verify' => false,
        ],
    ];

    /**
     * Create a new seeker.
     *
     * @throws InvalidArgumentException
     */
    public function __construct()
    {
        $this->client = new GuzzleClient($this->guzzleSettings);
    }

    /**
     * {@inheritdoc}
     */
    public function paraphrase(string $text): string
    {
        try {
            $response = $this->client->request(
                'POST',
                self::API_URL,
                [
                    'form_params' => [
                        'lang' => 'en',
                        'data' => $text,
                    ],
                ]
            );

            return $response->getBody()->getContents();
        } catch (Throwable $exception) {
            Log::error($exception->getMessage());
        }

        return $text;
    }
}
