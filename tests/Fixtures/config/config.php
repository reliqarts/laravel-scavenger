<?php

declare(strict_types=1);

use ReliqArts\Scavenger\Tests\Fixtures\Model\BingResult;
use ReliqArts\Scavenger\Tests\Fixtures\Model\Item;
use ReliqArts\Scavenger\Tests\Fixtures\Model\User;

$rooms = [
    'example' => false,
    'model' => Item::class,
    'source' => 'http://gleanerclassifieds.com/showads/section/Real+Estate-10100',
    'search' => null,
    'pager' => [
        'selector' => 'div.content #page .pagingnav',
        'text' => '>',
    ],
    'markup' => [
        'title' => 'div.content section > table tr h3',
        // content to be found upon clicking title link
        '__inside' => [
            'title' => '#ad-title > h1 > a',
            'body' => 'article .adcontent > p[align="LEFT"]:last-of-type',
            // focus detail on the following section
            '__focus' => 'section section > .content #ad-detail > article',
        ],
        '__wrapper' => null,
    ],
    // split single attributes into multiple based on regex
    'dissect' => [
        'body' => [
            'email' => '(([eE]mail)*:*\s*\w+\@(\s*\w)*\.(net|com))',
            'phone' => '((([cC]all|[[tT]el|[Pp][Hh](one)*)[:\d\-,\sDL\/]*\d)|(\d{3}\-?\d{4}))',
            'money' => '((US)*\$[,\d\.]+[Kk]*)',
            'beds' => '([\d]+[\d\.\/\s]*[^\w]*([Bb]edroom|b\/r|[Bb]ed)s?)',
            'baths' => '([\d]+[\d\.\/\s]*[^\w]*([Bb]athroom|bth|[Bb]ath)s?)',
            '__retain' => true,
        ],
    ],
    // modify attributes by calling functions
    'preprocess' => [
        // takes a callable
        // optional third parameter of array if callable method needs an instance
        // e.g. ['App\\Item', 'foo', true] or 'bar'
        'title' => null,
    ],
    // remap entity attributes to model properties
    'remap' => [
        // 'title' => 'name',
        'body' => 'details',
    ],
    // scraps containing any of these words will be rejected
    'bad_words' => [
        'office',
        'company',
        'mortgage',
        'business',
        'wholesale',
        'commercial',
        'short term',
    ],
    'pages' => 1,
];

$bing = [
    'example' => false,
    'serp' => true,
    'model' => BingResult::class,
    'source' => 'https://bing.com',
    'search' => null,
    'pager' => [
        'selector' => '.sb_pagN',
    ],
    'markup' => [
        '__result' => '.b_algo',
        'title' => 'h2 a',
        'description' => '.b_caption p',
        'link' => '__link',
        'position' => '__position',
    ],
];

return [
    // debug mode?
    'debug' => false,

    // whether log file should be written
    'log' => true,

    // How much detail is expected in output, 1 being the lowest, 3 being highest.
    'verbosity' => 1,

    // Set the database config
    'database' => [
        // Scraps table
        'scraps_table' => env('SCAVENGER_SCRAPS_TABLE', 'scavenger_scraps'),
    ],

    // Daemon config - used to build daemon user
    'daemon' => [
        // Model to use for Daemon identification and login
        'model' => User::class,

        // Model property to check for daemon ID
        'id_prop' => 'email',

        // Daemon ID
        'id' => 'daemon@scavenger.reliqarts.com',

        // Any additional information required to create a user:
        // NB. this is only used when creating a daemon user, there is no "safe" way
        // to change the daemon's password once he has been created.
        'info' => [
            'name' => 'Scavenger Daemon',
            'password' => 'pass',
        ],
    ],

    // guzzle settings
    'guzzle_settings' => [
        'timeout' => 60,
    ],

    // hashing algorithm to use
    'hash_algorithm' => 'sha512',

    // storage
    'storage' => [
        // This directory will live inside your application's log directory.
        'log_dir' => env('SCAVENGER_LOG_DIR', 'scavenger'),
    ],

    // different model entities and mapping information
    'targets' => [
        // NB. the "rooms" target shown below is for example purposes only. It has all posible keys explicitly.
        'rooms' => $rooms,
        'rooms (with search)' => array_merge($rooms, [
            'search' => [
                // keywords
                'keywords' => ['uwi', 'utech', 'student room'],
                'form' => [
                    // search form selector (important)
                    'selector' => '#frmSearch',
                    // input element name for search term/keyword
                    'keyword_input_name' => 'keyword',
                    'submit_button' => [
                        // text on submit button (optional)
                        'text' => null,
                        // submit element id, use if button doesn't have text (optional)
                        'id' => null,
                    ],
                ],
            ],
        ]),

        // Bing SERP example:
        'bing' => $bing,
        'bing (with search)' => array_merge($bing, [
            'search' => [
                'keywords' => ['dog'],
                'form' => [
                    'selector' => 'form#sb_form',
                    'keyword_input_name' => 'q',
                ],
            ],
        ]),
    ],
];
