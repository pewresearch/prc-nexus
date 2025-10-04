# GNews.io PHP Client

A PHP client for the GNews.io API, designed to be simple and easy to use.

## Documentation

- [GNews.io API Documentation](https://gnews.io/docs/v4#introduction)

## Installation

```bash
composer require gnews-io/gnews-io-php
```

## Usage

### Quick Examples

```php
<?php
require_once 'vendor/autoload.php';

$client = new \GNews\GNews('YOUR_API_KEY');

$articles = $client->search('bitcoin');
// $articles = $client->getTopHeadlines(['category' => 'technology']);

foreach ($articles as $article) {
    echo $article->getTitle() . "\n";
    echo $article->getSourceName() . "\n";
    echo $article->getUrl() . "\n";
}
```

## API Methods

### Search Articles

Search for articles with a specific query.

```php
$client = new \GNews\GNews('YOUR_API_KEY');

$articles = $client->search('bitcoin', [
    'lang' => 'en',
    'country' => 'us',
    'max' => 10,
    'in' => 'title,description',      // Where to search (title, description, content)
    'nullable' => null,               // Specify the attributes that you allow to return null values
    'sortby' => 'publishedAt',        // or 'relevance'
    'from' => '2025-01-01T00:00:00Z',
    'to' => '2025-01-31T23:59:59Z',
    'page' => 1,                      // Paid plan only
    'expand' => 'content',            // Paid plan only : get the full content of the article
]);
```

### Get Top Headlines

Get top headlines, optionally filtered by category.

```php
$client = new \GNews\GNews('YOUR_API_KEY');

$articles = $client->getTopHeadlines([
    'category' => 'technology',       // Optional: general, world, nation, business, technology, entertainment, sports, science, health
    'lang' => 'en',
    'country' => 'us',
    'max' => 10,
    'nullable' => '',                 // Specify the attributes that you allow to return null values
    'from' => '2025-01-01T00:00:00Z',
    'to' => '2025-01-31T23:59:59Z',
    'q' => 'bitcoin',
    'page' => 1,                      // Paid plan only
    'expand' => 'content',            // Paid plan only : get the full content of the article
]);

```

## Parameters

| Parameter  | Type    | Description                                                                       |
| ---------- | ------- | --------------------------------------------------------------------------------- |
| `lang`     | string  | Language of the articles (two-letter ISO 639-1 code)                              |
| `country`  | string  | Country of the articles (two-letter ISO 3166-1 code)                              |
| `max`      | integer | Maximum number of articles to return (1-100)                                      |
| `category` | string  | Category of the articles (top headlines only)                                     |
| `sortby`   | string  | Sorting method: 'publishedAt' or 'relevance' (search only)                        |
| `from`     | string  | Start date for search (ISO 8601 format, search only)                              |
| `to`       | string  | End date for search (ISO 8601 format, search only)                                |
| `in`       | string  | Where to search: 'title', 'description', 'content' or a combination (search only) |
| `nullable` | boolean | Whether to include null values in the query params                                |
| `page`     | int     | Control the pagination of the results (paid plan only)                            |
| `expand`   | boolean | Whether to get full article content (paid plan only)                              |

## Response Format

All API methods return promises that resolve to objects with the following structure:

### ArticleCollection

| Method               | Description                                      |
| -------------------- | ------------------------------------------------ |
| `getTotalArticles()` | Returns the total number of available articles   |
| `getArticles()`      | Returns an array of `Article` objects            |
| `count()`            | Returns the number of articles in the collection |
| Array access         | You can access articles with `$articles[0]`      |
| Iteration            | You can use `foreach($articles as $article)`     |

### Article

| Method             | Description                                                                |
| ------------------ | -------------------------------------------------------------------------- |
| `getTitle()`       | Get the article title (also accessible via `->title`)                      |
| `getDescription()` | Get the article description (also accessible via `->description`)          |
| `getContent()`     | Get the article content (also accessible via `->content`)                  |
| `getUrl()`         | Get the article URL (also accessible via `->url`)                          |
| `getImage()`       | Get the article image URL (also accessible via `->image`)                  |
| `getPublishedAt()` | Get the publication date (also accessible via `->publishedAt`)             |
| `getSource()`      | Get the complete source information array (also accessible via `->source`) |
| `getSourceName()`  | Get the source name (also accessible via `->source['name']`)               |
| `getSourceUrl()`   | Get the source URL (also accessible via `->source['url']`)                 |

## Error Handling

The library throws errors in the following cases:

- Missing API key during initialization
- Network errors
- API request timeouts
- API error responses
