# GNews API PHP Client

A PHP client for [GNews API](https://gnews.io), designed to be simple and easy to use.

## Documentation

- [GNews API Documentation](https://docs.gnews.io/)

## Installation

```bash
composer require gnews-io/gnews-io-php
```

## Usage

### Initialization

```php
<?php
require_once 'vendor/autoload.php';

$client = new \GNews\GNews('YOUR_API_KEY');
```

### Search Endpoint

```php
// Search for articles
$articles = $client->search('bitcoin', [
    'lang' => 'en',                   // Optional, languages of articles
    'country' => 'us',                // Optional, country of origin of the source
    'max' => 10,                      // Optional, maximum number of articles to be returned
    'from' => '2025-01-01T00:00:00Z', // Optional, minimum publication date (included)
    'to' => '2025-12-31T23:59:59Z',   // Optional, maximum publication date (included)
    // ..., any additional parameter specified in the documentation (see https://docs.gnews.io)
]);

echo "---\n";
foreach ($articles as $article) {
    echo $article->getTitle() . "\n";
    echo $article->getSourceName() . "\n";
    echo $article->getUrl() . "\n";
    // ...
    echo "---\n";
}
```

### Top Headlines Endpoint

```php
// Get the top headlines
$articles = $client->getTopHeadlines([
    'category' => 'technology'        // Optional, desired category
    'lang' => 'en',                   // Optional, languages of articles
    'country' => 'us',                // Optional, country of origin of the source
    'max' => 10,                      // Optional, maximum number of articles to be returned
    // ..., any additional parameter specified in the documentation (see https://docs.gnews.io)
]);

echo "---\n";
foreach ($articles as $article) {
    echo $article->getTitle() . "\n";
    echo $article->getSourceName() . "\n";
    echo $article->getUrl() . "\n";
    // ...
    echo "---\n";
}
```

## Response Format

The API results are returned in an ArticleCollection object:

### ArticleCollection

| Method               | Description                                      |
| -------------------- | ------------------------------------------------ |
| `getTotalArticles()` | Returns the total number of available articles   |
| `getArticles()`      | Returns an array of `Article` objects            |
| `count()`            | Returns the number of articles in the collection |
| Array access         | You can access articles with `$articles[0]`      |
| Iteration            | You can use `foreach($articles as $article)`     |

### Article

| Method               | Description                                                                  |
| -------------------- | ---------------------------------------------------------------------------- |
| `getId()`            | Get the article ID (also accessible via `->id`)                              |
| `getTitle()`         | Get the article title (also accessible via `->title`)                        |
| `getDescription()`   | Get the article description (also accessible via `->description`)            |
| `getContent()`       | Get the article content (also accessible via `->content`)                    |
| `getUrl()`           | Get the article URL (also accessible via `->url`)                            |
| `getImage()`         | Get the article image URL (also accessible via `->image`)                    |
| `getPublishedAt()`   | Get the publication date (also accessible via `->publishedAt`)               |
| `getSource()`        | Get the complete source information array (also accessible via `->source`)   |
| `getSourceId()`      | Get the source ID (also accessible via `->source['id']`)                     |
| `getSourceName()`    | Get the source name (also accessible via `->source['name']`)                 |
| `getSourceUrl()`     | Get the source URL (also accessible via `->source['url']`)                   |
| `getSourceCountry()` | Get the source country of origin (also accessible via `->source['country']`) |

## Error Handling

The library throws errors in the following cases:

- Missing API key during initialization
- Network errors
- API request timeouts
- API error responses
