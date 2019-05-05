<?php

/*
 * @author    Reliq <reliq@reliqarts.com>
 * @copyright 2018
 */

namespace ReliqArts\Scavenger\Services;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use ReliqArts\Scavenger\Contracts\Paraphraser as ParaphraserInterface;

class Paraphraser implements ParaphraserInterface
{
    /**
     * Mapping of resources and url.
     *
     * @const array
     */
    private const API_URL = 'http://script4.prothemes.biz/php/process.php';

    /**
     * HTTP Client instance.
     *
     * @var GuzzleClient
     */
    protected $client;
    /**
     * Guzzle settings.
     *
     * @var array
     */
    private $guzzleSettings = [
        'timeout' => -1,
        'defaults' => [
            'verify' => false,
        ],
    ];

    /**
     * Create a new seeker.
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
        } catch (GuzzleException | Exception $e) {
            Log::error($e);
        }

        return $text;
    }
}
