<?php

namespace GNews\Tests;

use GNews\Article;
use GNews\GNews;
use GNews\GNewsException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use JsonException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

class GNewsTest extends TestCase
{
    private const API_KEY = 'test-api-key';
    private GNews $gnews;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();
        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        $this->gnews = new GNews(self::API_KEY);

        // Injects the mocked HTTP client into the GNews instance.
        $reflectionClass = new ReflectionClass(GNews::class);
        $property = $reflectionClass->getProperty('httpClient');
        $property->setAccessible(true);
        $property->setValue($this->gnews, $client);
    }

    /**
     * @throws ReflectionException
     * @throws GNewsException
     */
    public function testConstructor(): void
    {
        $gnews = new GNews(self::API_KEY, 'v3', 11000);

        $this->assertEquals('v3', $gnews->version);
        $this->assertEquals(11000, $gnews->timeout);
    }

    /**
     * @throws GNewsException
     * @throws JsonException
     */
    public function testSearchArticles(): void
    {
        $responseData = [
            'totalArticles' => 1,
            'articles' => [
                ['title' => 'Test Article']
            ]
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($responseData, JSON_THROW_ON_ERROR))
        );

        $result = $this->gnews->search('test query', ['lang' => 'fr']);

        $this->assertEquals(1, $result->getTotalArticles());
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Article::class, $result[0]);
        $this->assertEquals('Test Article', $result[0]->title);
    }

    /**
     * @throws GNewsException
     * @throws JsonException
     */
    public function testGetTopHeadlines(): void
    {
        $responseData = [
            'totalArticles' => 2,
            'articles' => [
                ['title' => 'Headline 1'],
                ['title' => 'Headline 2']
            ]
        ];

        $this->mockHandler->append(
            new Response(200, [], json_encode($responseData, JSON_THROW_ON_ERROR))
        );

        $result = $this->gnews->getTopHeadlines(['country' => 'fr']);

        $this->assertEquals(2, $result->getTotalArticles());
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Article::class, $result[0]);
        $this->assertEquals('Headline 1', $result[0]->title);
        $this->assertEquals('Headline 2', $result[1]->title);
    }

    /**
     * @throws JsonException
     */
    public function testApiError(): void
    {
        $errorResponse = [
            'errors' => ['API rate limit exceeded']
        ];

        $this->mockHandler->append(
            new Response(429, [], json_encode($errorResponse, JSON_THROW_ON_ERROR))
        );

        $this->expectException(GNewsException::class);
        $this->expectExceptionMessage('API rate limit exceeded');

        $this->gnews->getTopHeadlines();
    }

    /**
     * @throws GNewsException
     */
    public function testRealSearchArticles(): void
    {
        // Vérifie si une clé API est disponible dans l'environnement
        $apiKey = self::API_KEY;

        if ($apiKey === 'test-api-key') {
            $this->markTestSkipped("No real API key available for integration tests");
        }

        $gnews = new GNews($apiKey);

        $result = $gnews->search('php programming', ['lang' => 'en', 'max' => 3]);

        $this->assertIsInt($result->getTotalArticles());

        if (count($result) > 0) {
            $article = $result[0];
            $this->assertInstanceOf(Article::class, $article);
            $this->assertNotEmpty($article->getTitle());
            $this->assertIsString($article->getDescription());
            $this->assertIsString($article->getUrl());
        }
    }

    /**
     * @throws GNewsException
     */
    public function testRealGetTopHeadlines(): void
    {
        $apiKey = self::API_KEY;

        if ($apiKey === 'test-api-key') {
            $this->markTestSkipped("No real API key available for integration tests");
        }

        $gnews = new GNews($apiKey);

        $result = $gnews->getTopHeadlines(['country' => 'fr', 'max' => 3]);

        $this->assertIsInt($result->getTotalArticles());

        if (count($result) > 0) {
            $article = $result[0];
            $this->assertInstanceOf(Article::class, $article);
            $this->assertNotEmpty($article->getTitle());
            $this->assertIsString($article->getDescription());
            $this->assertIsString($article->getUrl());
        }
    }
}