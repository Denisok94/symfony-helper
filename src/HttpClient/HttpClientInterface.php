<?php

namespace Denisok94\SymfonyHelper\HttpClient;

use Unirest\Response;

/**
 * Interface HttpClientInterface
 * @package Denisok94\SymfonyHelper\HttpClient
 */
interface HttpClientInterface
{
    public const ERROR_RESPONSE_CODES = [
        \Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST,
        \Symfony\Component\HttpFoundation\Response::HTTP_UNAUTHORIZED
    ];

    public const SUCCESS_RESPONSE_CODES = [
        \Symfony\Component\HttpFoundation\Response::HTTP_OK,
        \Symfony\Component\HttpFoundation\Response::HTTP_CREATED,
        \Symfony\Component\HttpFoundation\Response::HTTP_ACCEPTED,
        \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT
    ];

    public const SERVER_ERROR_CODES = [
        \Symfony\Component\HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR,
        \Symfony\Component\HttpFoundation\Response::HTTP_BAD_GATEWAY,
        \Symfony\Component\HttpFoundation\Response::HTTP_GATEWAY_TIMEOUT
    ];

    /**
     * @param string $endpoint
     * @param mixed $data
     * @param array $headers
     * @return Response
     */
    public function post(string $endpoint, $data, array $headers = []): Response;

    /**
     * @param string $endpoint
     * @param string $json
     * @param array $headers
     * @return Response
     */
    public function postJson(string $endpoint, string $json, array $headers = []): Response;

    /**
     * @param string $endpoint
     * @param array $parameters
     * @param array $headers
     * @return Response
     */
    public function get(string $endpoint, array $parameters = [], array $headers = []): Response;

    /**
     * @param string $endpoint
     * @param array $parameters
     * @param array $headers
     * @return Response
     */
    public function getJson(string $endpoint, array $parameters = [], array $headers = []): Response;

    /**
     * @param string $endpoint
     * @param null $data
     * @param array $headers
     * @return Response
     */
    public function delete(string $endpoint, $data = null, array $headers = []): Response;

    /**
     * @param string $endpoint
     * @param string|null $json
     * @param array $headers
     * @return Response
     */
    public function deleteJson(string $endpoint, string $json = null, array $headers = []): Response;

    /**
     * @param string $endpoint
     * @param null $data
     * @param array $headers
     * @return Response
     */
    public function patch(string $endpoint, $data = null, array $headers = []): Response;

    /**
     * @param string $endpoint
     * @param string|null $json
     * @param array $headers
     * @return Response
     */
    public function patchJson(string $endpoint, string $json = null, array $headers = []): Response;
}
