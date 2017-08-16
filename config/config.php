<?php

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

    // Hashing algorithm to use
    'hash_algorithm' => 'sha512',

    // storage
    'storage' => [
        // This directory will live inside your application's storage directory.
        'dir' => env('SCAVENGER_STORAGE_DIR', 'scavenger'),
    ],

    // Different entities and mapping information
    'targets' => [
        'states' => [
            'model' => 'App\\Status',
            'source' => 'http://gleanerclassifieds.com/showads/section/Real+Estate-10100',
            'search' => [
                // keywords
                'keywords' => ['utech'],
                // input element name for search term/keyword
                'keyword_input' => 'keyword',
                // form markup, used to locate search form
                'form_markup' => 'div.content',
                // text on submit button
                'submit_button_text' => 'Search'
            ],
            'pager' => 'div.content #page .pagingnav',
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
                'title' => ['App\\Status', 'haha', true],
                'body' => 'bumbo'
            ],
            // remap entity attributes to model properties
            'remap' => [
                'title' => 'name',
                'body' => 'description'
            ],
            // scraps containing any of these words will be rejected
            'bad_words' => [
                'car',
                'bar',
                'land',
                'loan',
                'club',
                'shop',
                'sale',
                'store',
                'lease',
                'plaza',
                'condo',
                'seeks',
                'garage',
                'barber',
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
