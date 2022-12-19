<?php

namespace Denisok94\SymfonyHelper\HttpClient;

use Psr\Log\LoggerInterface;
use Unirest\Request;
use Unirest\Response;

/**
 * Class HttpClient
 * @package Denisok94\SymfonyHelper\HttpClient
 */
class HttpClient implements HttpClientInterface
{
    /** @var string */
    private $apiUrl;
    /** @var LoggerInterface|null */
    private $logger;

    /**
     * ApiHttpClient constructor.
     * @param string $apiUrl
     * @param LoggerInterface|null $logger
     */
    public function __construct(string $apiUrl, ?LoggerInterface $logger = null)
    {
        $this->apiUrl = $apiUrl;
        $this->logger = $logger;
    }

    /**
     * @param string $endpoint
     * @param string $json
     * @param array $headers
     * @return Response
     */
    public function postJson(string $endpoint, string $json, array $headers = []): Response
    {
        $headers = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ], $headers);

        if ($this->logger) {
            $this->logger->info('POST json: ' . $this->apiUrl . $endpoint);
            $this->logger->info($json);
        }

        $response = Request::post($this->apiUrl . $endpoint, $headers, $json);
        if ($this->logger) {
            $this->logger->info(print_r($response, true));
        }

        return $response;
    }

    /**
     * @param string $endpoint
     * @param mixed $data
     * @param array $headers
     * @return Response
     */
    public function post(string $endpoint, $data, array $headers = []): Response
    {
        if ($this->logger) {
            $this->logger->info('POST: ' . $this->apiUrl . $endpoint);
            $this->logger->info(print_r($data, true));
        }

        $response = Request::post($this->apiUrl . $endpoint, $headers, $data);
        if ($this->logger) {
            $this->logger->info(print_r($response, true));
        }

        return $response;
    }

    /**
     * @param string $endpoint
     * @param string $json
     * @param array $headers
     * @return Response
     */
    public function putJson(string $endpoint, string $json, array $headers = []): Response
    {
        $headers = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ], $headers);

        if ($this->logger) {
            $this->logger->info('PUT json: ' . $this->apiUrl . $endpoint);
            $this->logger->info($json);
        }

        $response = Request::put($this->apiUrl . $endpoint, $headers, $json);
        if ($this->logger) {
            $this->logger->info(print_r($response, true));
        }

        return $response;
    }

    /**
     * @param string $endpoint
     * @param mixed $data
     * @param array $headers
     * @return Response
     */
    public function put(string $endpoint, $data, array $headers = []): Response
    {
        if ($this->logger) {
            $this->logger->info('PUT: ' . $this->apiUrl . $endpoint);
            $this->logger->info(print_r($data, true));
        }

        $response = Request::put($this->apiUrl . $endpoint, $headers, $data);
        if ($this->logger) {
            $this->logger->info(print_r($response, true));
        }

        return $response;
    }

    /**
     * @param string $endpoint
     * @param array $parameters
     * @param array $headers
     * @return Response
     */
    public function getJson(string $endpoint, array $parameters = [], array $headers = []): Response
    {
        $headers = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ], $headers);

        if ($this->logger) {
            $this->logger->info('GET json: ' . $this->apiUrl . $endpoint);
        }

        $response = Request::get($this->apiUrl . $endpoint, $headers, $parameters);
        if ($this->logger) {
            $this->logger->info(print_r($response, true));
        }

        return $response;
    }

    /**
     * @param string $endpoint
     * @param array $parameters
     * @param array $headers
     * @return Response
     */
    public function get(string $endpoint, array $parameters = [], array $headers = []): Response
    {
        array_walk_recursive($parameters, function (&$item, $key) {
            $item = urlencode($item);
        });

        if ($this->logger) {
            $this->logger->info('GET: ' . $this->apiUrl . $endpoint);
            $this->logger->info(print_r($parameters, true));
        }

        $response = Request::get($this->apiUrl . $endpoint, $headers, $parameters);
        if ($this->logger) {
            $this->logger->info(print_r($response, true));
        }

        return $response;
    }

    /**
     * @param string $endpoint
     * @param string|null $json
     * @param array $headers
     * @return Response
     */
    public function deleteJson(string $endpoint, string $json = null, array $headers = []): Response
    {
        $headers = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ], $headers);

        if ($this->logger) {
            $this->logger->info('DELETE json: ' . $this->apiUrl . $endpoint);
            $this->logger->info($json);
        }

        $response = Request::delete($this->apiUrl . $endpoint, $headers, $json);
        if ($this->logger) {
            $this->logger->info(print_r($response, true));
        }

        return $response;
    }

    /**
     * @param string $endpoint
     * @param mixed|null $data
     * @param array $headers
     * @return Response
     */
    public function delete(string $endpoint, $data = null, array $headers = []): Response
    {
        if ($this->logger) {
            $this->logger->info('DELETE: ' . $this->apiUrl . $endpoint);
            $this->logger->info(print_r($data, true));
        }

        $response = Request::delete($this->apiUrl . $endpoint, $headers, $data);
        if ($this->logger) {
            $this->logger->info(print_r($response, true));
        }

        return $response;
    }

    /**
     * @param string $endpoint
     * @param string|null $json
     * @param array $headers
     * @return Response
     */
    public function patchJson(string $endpoint, string $json = null, array $headers = []): Response
    {
        $headers = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ], $headers);

        if ($this->logger) {
            $this->logger->info('PATCH json: ' . $this->apiUrl . $endpoint);
            $this->logger->info($json);
        }

        $response = Request::patch($this->apiUrl . $endpoint, $headers, $json);
        if ($this->logger) {
            $this->logger->info(print_r($response, true));
        }

        return $response;
    }

    /**
     * @param string $endpoint
     * @param mixed|null $data
     * @param array $headers
     * @return Response
     */
    public function patch(string $endpoint, $data = null, array $headers = []): Response
    {
        if ($this->logger) {
            $this->logger->info('PATCH: ' . $this->apiUrl . $endpoint);
            $this->logger->info(print_r($data, true));
        }

        $response = Request::patch($this->apiUrl . $endpoint, $headers, $data);
        if ($this->logger) {
            $this->logger->info(print_r($response, true));
        }

        return $response;
    }
}
