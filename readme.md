# Laravel Scavenger

![Laravel Scavenger](./docs/images/inline-preview.png "Laravel Scavenger")

The most integrated web scraping package for Laravel.

[![Unit Tests](https://github.com/reliqarts/laravel-scavenger/actions/workflows/unit-tests.yml/badge.svg)](https://github.com/reliqarts/laravel-scavenger/actions/workflows/unit-tests.yml)
[![Codecov](https://img.shields.io/codecov/c/github/reliqarts/laravel-scavenger.svg?style=flat-square)](https://codecov.io/gh/reliqarts/laravel-scavenger)
[![Scrutinizer](https://img.shields.io/scrutinizer/g/reliqarts/laravel-scavenger.svg?style=flat-square)](https://scrutinizer-ci.com/g/reliqarts/laravel-scavenger/)
[![License](https://poser.pugx.org/reliqarts/laravel-scavenger/license?format=flat-square)](https://packagist.org/packages/reliqarts/laravel-scavenger)
[![Latest Stable Version](https://poser.pugx.org/reliqarts/laravel-scavenger/version?format=flat-square)](https://packagist.org/packages/reliqarts/laravel-scavenger)
[![Latest Unstable Version](https://poser.pugx.org/reliqarts/laravel-scavenger/v/unstable?format=flat-square)](//packagist.org/packages/reliqarts/laravel-scavenger)
[![Maintainability](https://api.codeclimate.com/v1/badges/10ad2ea9c016876c78bc/maintainability)](https://codeclimate.com/github/reliqarts/laravel-scavenger/maintainability)

## Top Features

Scavenger provides the following features and more out-the-box.

- Ease of use
    - Scavenger is super-easy to configure. Simply publish the config file and set your targets.
- Scrape data from multiple sources at once.
- Convert scraped data into usable Laravel model objects.
    - eg. You may scrape an article, have it converted into an object of your choice, and save it in your database. Immediately available to your viewers.
- You can easily perform one or more operations, on each property, of any scraped entity.
    - eg. You may call a paraphrase service from a model or package of your choice on data attributes before saving them to your database.
- Data integrity constraints
    - Scavenger uses a hashing algorithm of your choice to maintain data integrity. This hash is used to ensure that one scrap (source article) is not converted to multiple output objects (model duplicates).
- Console Command
    - Once Scavenger is configured, a simple artisan command launches the seeker. Since this is a console command it is more efficient and timeouts are less likely to occur.
    - Artisan command: `php artisan scavenger:seek`
- Schedule ready
    - Scavenger can easily be set to scrape on a schedule. Hence, creating a somewhat autonomous website is super easy!
- SERP
    - Scavenger can be used to flexibly scrape Search Engine Result Pages.

## Installation

1. Install via composer; in your terminal: 
   ```
   composer require reliqarts/laravel-scavenger
   ``` 
   or require in *composer.json*:
   ```json
   {
       "require": {
           "reliqarts/laravel-scavenger": "^3.1"
       }
   }
   ```
   then run `composer update` in your terminal to pull it in.
   
 2. (Optional) Publish package resources and configuration:
   
   ```
   php artisan vendor:publish --provider="ReliqArts\Scavenger\ServiceProvider"
   ``` 
   
   You may opt to publish only configuration by using the `scavenger-config` tag:
   
   ```
   php artisan vendor:publish --provider="ReliqArts\Scavenger\ServiceProvider" --tag="scavenger-config"
   ``` 
   
   or only the migrations via the `scavenger-migrations` tag:
      
   ```
   php artisan vendor:publish --provider="ReliqArts\Scavenger\ServiceProvider" --tag="scavenger-migrations"
   ``` 

## Configuration

Scavenger is highly configurable. Once configured, the settings will be used for every scrape.


### Structure

Below is an example of a typical config file structure, with comments explaining each setting.

```php
<?php

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
        'rooms' => [
            'example' => true,
            'serp' => false,
            'model' => 'App\\Room',
            'source' => 'http://myroomslistingsite.1demo/section/rooms',
            'search' => [
                // keywords
                'keywords' => ['professional'],
                // form markup
                'form' => [
                    // search form selector (important)
                    'selector' => '#form',
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
            'pager' => [
                // link (a tag) selector
                'selector' => 'div.content #page a.pagingnav',
            ],
            // max. number of pages to scrape (0 is unlimited)
            'pages' => 0,
            // content markup: actual data to be scraped
            'markup' => [
                'title' => 'div.content section > table tr h3',
                // inside: content to be found upon clicking title link
                '__inside' => [
                    'title' => '#ad-title > h1 > a',
                    'body' => 'article .adcontent > p[align="LEFT"]:last-of-type',
                    // focus: focus detail on the following section
                    '__focus' => 'section section > .content #ad-detail > article',
                ],
                // wrapper/item/result: wrapping selector for each item on single page.
                // If inside special key is set this key becomes invalid (i.e. inside takes preference)
                '__result' => null,
            ],
            // split single attributes into multiple based on regex
            'dissect' => [
                'body' => [
                    'email' => '(([eE]mail)*:*\s*\w+\@(\s*\w)*\.(net|com))',
                    'phone' => '((([cC]all|[[tT]el|[Pp][Hh](one)*)[:\d\-,\sDL\/]*\d)|(\d{3}\-?\d{4}))',
                    'beds' => '([\d]+[\d\.\/\s]*[^\w]*([Bb]edroom|b\/r|[Bb]ed)s?)',
                    'baths' => '([\d]+[\d\.\/\s]*[^\w]*([Bb]athroom|bth|[Bb]ath)s?)',
                    // retain:  whether details should be left in source attribute after extraction
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
            // remap entity attributes to model properties (optional)
            'remap' => [
                'title' => null,
                'body' => null,
            ],
            // scraps containing any of these words will be rejected (optional)
            'bad_words' => [
                'office',
            ],
        ],

        // Google SERP example:
        'google' => [
            'example' => true,
            'serp' => true,
            'model' => 'App\\GoogleResult',
            'source' => 'https://www.google.com',
            'search' => [
                'keywords' => ['dog'],
                'form' => [
                    'selector' => 'form[name="f"]',
                    'keyword_input_name' => 'q',
                ],
            ],
            'pages' => 2,
            'pager' => [
                'selector' => '#foot > table > tr > td.b:last-child a',
            ],
            'markup' => [
                '__result' => 'div.g',
                'title' => 'h3 > a',
                'description' => '.st',
                // the 'link' and 'position' attributes make use of some of Scavengers available properties
                'link' => '__link',
                'position' => '__position',
            ],
        ],

        // Bing SERP example:
        'bing' => [
            'example' => true,
            'serp' => true,
            'model' => 'App\\BingResult',
            'source' => 'https://www.bing.com',
            'search' => [
                'keywords' => ['dog'],
                'form' => [
                    'selector' => 'form#sb_form',
                    'keyword_input_name' => 'q',
                ],
            ],
            'pages' => 3,
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
        ],
    ],
];

```

#### Target Breakdown

The `targets` array contains a list of entities (to be scraped from) keyed by a unique target identifier. The structure is as follows.

- `model`: Laravel DB model to create from target.
- `source`: Source URL to scrape.
- `search`: Search settings. Use if a search is to be performed before target data is shown. (optional)
    - `keywords`: Array of keywords to search for.
    - `keyword_input`: Keyword input text markup.
    - `form_markup`: CSS selector for search form.
    - `submit_button_text`: The text on the form's submit button.
- `pager`: Next link CSS selector. To skip to next page.
- `markup`: Array of attributes to scrape from main list. `[attributeName => CSS selector]`
    - `__inside`: Sub markup for detail page. Markup for page which shows when article title is clicked/opened. (optional)
- `dissect`: Split compound attributes into smaller attributes via REGEX. (optional)
- `preprocess`: Array of attributes which need to be preprocessed. `[attributeName => callable]` (optional)
- `remap`: Array of attributes which need to be renamed in order to be saved as target objects. `[attributeName => newName]` (optional)
- `bad_words`: Any scrap found containing these words will be discarded. (optional)

## Glossary of Terms
The following words may appear in context above.

- `Daemon`: User instance to be used by the Scavenger service.
- `Scrap`: Scraped data before being converted to the target object.
- `Target`: Configured source-model mapping for a single entity. 
- `Target Object`: Eloquent model object to be generated from scrape. 
----

### Acknowledgements

This library is heavily inspired by, and dependent on, the [Guzzle](https://github.com/guzzle/guzzle)
library, although several concepts may have been adjusted.
