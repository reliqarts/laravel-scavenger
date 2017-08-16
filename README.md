# Laravel Scavenger

A highly flexible Laravel 5.x scraper package.

## Top Features

Scavenger provides the following features and more out of the box.

- Ease of use
    - Scavenger is super-easy to configure. Simple publish the config file and set your targets.
- Scrape data from multiple sources at once.
- Convert scraped data into useable Laravel model objects.
    - eg. You may scrape an article and have it converted into an object of your choice and saved in your database. Immediately available to your viewers.
- You can easily perform one or more operations to each property of any scraped entity.
    - eg. You may call a paraphrase service from a model or package of your choice on data attributes before saving them to your database.
- Data integrity constraints
    - Scavanger uses a hashing algorithm of your choice to maintain data integrity. This hash is used to ensure that one scrap (source article) is not converted to multiple output objects (model duplicates).