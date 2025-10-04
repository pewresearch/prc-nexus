<?php

namespace GNews;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class GNews
{
    /**
     * @var string GNews API key
     */
    protected string $apiKey;

    /**
     * @var string GNews API base URL
     */
    protected string $baseUrl = 'https://gnews.io/api/';

    /**
     * @var string GNews API version
     */
    public string $version = 'v4';

    /**
     * @var int Request timeout in milliseconds
     */
    public int $timeout = 10000;

    /**
     * @var Client HTTP client
     */
    protected Client $httpClient;

    /**
     * GNews constructor.
     *
     * @param string $apiKey GNews API key
     * @param string $version GNews API version
     * @param int $timeout Request timeout in milliseconds
     * @throws GNewsException
     */
    public function __construct(string $apiKey, string $version = 'v4', int $timeout = 10000)
    {
        $this->apiKey = $apiKey;
        $this->httpClient = new Client();

        if (!in_array($version, ['v3', 'v4'], true)) {
            throw new GNewsException('Invalid API version. Supported versions are v3 and v4.');
        }

        $this->version = $version;
        $this->timeout = $timeout;
    }

    /**
     * Search for news articles.
     *
     * @param string $query Search query
     * @param array $params Additional parameters
     * @return ArticleCollection Search results
     * @throws GNewsException If the API request fails
     */
    public function search(string $query, array $params = []): ArticleCollection
    {
        $requestParams = $this->prepareParams($params);
        $requestParams['q'] = $query;

        $response = $this->makeRequest('/search', $requestParams);
        return new ArticleCollection($response);
    }

    /**
     * Get top headlines.
     *
     * @param array $params Additional parameters
     * @return ArticleCollection Headlines
     * @throws GNewsException If the API request fails
     */
    public function getTopHeadlines(array $params = []): ArticleCollection
    {
        $requestParams = $this->prepareParams($params);

        $response = $this->makeRequest('/top-headlines', $requestParams);
        return new ArticleCollection($response);
    }

    /**
     * Prepare parameters for API request.
     *
     * @param array $params User-provided parameters
     * @return array Prepared parameters
     */
    protected function prepareParams(array $params): array
    {
        $requestParams = ['apikey' => $this->apiKey];

        // Optional parameters that can be passed directly
        $optionalParams = [
            'lang', 'country', 'max', 'category', 'sortby', 'from', 'to', 'in', 'nullable', 'page', 'expand'
        ];

        foreach ($optionalParams as $param) {
            if (isset($params[$param])) {
                $requestParams[$param] = $params[$param];
            }
        }

        return $requestParams;
    }

    /**
     * Make a request to the GNews API.
     *
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @return array API response data
     * @throws GNewsException If the API request fails
     */
    protected function makeRequest(string $endpoint, array $params = []): array
    {
        $url = $this->baseUrl . $this->version . $endpoint;

        try {
            $response = $this->httpClient->request('GET', $url, [
                'query' => $params,
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            if ($statusCode !== 200) {
                throw new GNewsException(
                    isset($body['errors']) ? $body['errors'][0] : 'Unknown error occurred',
                    $statusCode
                );
            }

            return $body;
        } catch (GuzzleException $e) {
            throw new GNewsException('HTTP request failed: ' . $e->getMessage(), $e->getCode(), $e);
        } catch (\JsonException $e) {
            throw new GNewsException('Failed to decode JSON response: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
