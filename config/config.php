<?php
/**
 * Laravel Scavenger Configuration
 * 
 * NB: Special keys start with an "_". 
 * Please refer to the documentation found at http://scavenger.reliqarts.com for more info.
 * 
 * Ps. Thank you for choosing Scavenger!
 */

return [
    // debug mode?
    'debug' => false,
    
    // whther log file should be written
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
        'model' => 'App\\User',

        // Model property to check for daemon ID
        'id_prop' => 'email',

        // Daemon ID
        'id' => 'daemon@scavenger.reliqarts.com',

        // Any additional information required to create a user:
        // NB. this is only used when creating a daemon user, there is no "safe" way 
        // to change the daemon's password once he has been created.
        'info' => [
            'name' => 'Scavenger Daemon',
            'password' => 'pass'
        ]
    ],

    // hashing algorithm to use
    'hash_algorithm' => 'sha512',

    // storage
    'storage' => [
        // This directory will live inside your application's storage directory.
        'dir' => env('SCAVENGER_STORAGE_DIR', 'scavenger'),
    ],

    // different model entities and mapping information
    'targets' => [
        // NB. the "rooms" target shown below is for example purposes only.
        'rooms' => [
            '_example' => true,
            'model' => 'App\\Room',
            'source' => 'http://roomssite.demo.com/showads/section/rooms',
            'search' => [
                // keywords
                'keywords' => ['school'],
                // form markup
                'form' => [
                    // search form selector (important)
                    'selector' => '#form',
                    // input element name for search term/keyword
                    'keyword_input_name' => 'keyword',
                    // 'submit_button' => [
                    //     // text on submit button (optional)
                    //     'text' => 'Search',
                    //     // submit element id, use if button doesn't have text (optional)
                    //     'id' => 'submit-search',
                    // ],
                ],
            ],
            'pager' => [
                // link (a tag) selector
                'selector' => 'div.content #page .pagingnav',
                // link (or element within link text)
                'text' => '>',
            ],
            'markup' => [
                'title' => 'div.content section > table tr h3',
                // content to be found upon clicking title link
                '_inside' => [
                    'title' => '#ad-title > h1 > a',
                    'body' => 'article .adcontent > p[align="LEFT"]:last-of-type',
                    // focus detail on the following section
                    '_focus' => 'section section > .content #ad-detail > article'
                ],
            ],
            // split single attributes into multiple based on regex
            'dissect' => [
                'body' => [
                    'email' => '(([eE]mail)*:*\s*\w+\@(\s*\w)*\.(net|com))',
                    'phone' => '((([cC]all|[[tT]el|[Pp][Hh](one)*)[:\d\-,\sDL\/]*\d)|(\d{3}\-?\d{4}))',
                    'money' => '((US)*\$[,\d\.]+[Kk]*)',
                    'beds' => '([\d]+[\d\.\/\s]*[^\w]*([Bb]edroom|b\/r|[Bb]ed)s?)',
                    'baths' => '([\d]+[\d\.\/\s]*[^\w]*([Bb]athroom|bth|[Bb]ath)s?)',
                    '_retain' => true
                ],
            ],
            // modify attributes by calling functions
            'preprocess' => [
                // takes a callable
                // optional third parameter of array if callable method needs an instance
                'title' => ['App\\Status', 'foo', true],
                'body' => 'bar'
            ],
            // remap entity attributes to model properties
            'remap' => [
                'title' => 'name',
                'body' => 'description'
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
        ],
    ],

];
