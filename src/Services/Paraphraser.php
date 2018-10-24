<?php

namespace ReliQArts\Scavenger\Services;

use GuzzleHttp\Client as GuzzleClient;
use Log;
use ReliQArts\Scavenger\Contracts\Paraphraser as ParaphraserInterface;

class Paraphraser implements ParaphraserInterface
{
    /**
     * Mapping of resources and url.
     *
     * @const array
     */
    const API_URL = 'http://script4.prothemes.biz/php/process.php';

    /**
     * HTTP Client instance.
     *
     * @var Goute\Client
     */
    protected $client;
    /**
     * Guzzle settings.
     *
     * @var array
     */
    private $guzzleSettings = [
        'timeout'  => -1,
        'defaults' => [
            'verify' => false,
        ],
    ];

    /**
     * Create a new seeker.
     *
     * @return void
     */
    public function __construct()
    {
        $this->client = new GuzzleClient($this->guzzleSettings);
    }

    /**
     * {@inheritdoc}
     */
    public function paraphrase($text)
    {
        try {
            $response = $this->client->request(
                'POST',
                static::API_URL,
                [
                    'form_params' => [
                        'lang' => 'en',
                        'data' => $text,
                    ],
                ]
            );

            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            Log::error($e);
        }
    }
}
