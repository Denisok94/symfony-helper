<?php

namespace Denisok94\SymfonyHelper\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

/**
 * Выдать ошибки/исключения в формате Json
 * Class JsonErrorController
 * @package Denisok94\SymfonyHelper\Controller
 */
class JsonErrorController
{
    /**
     * @param Throwable $e
     * @param LoggerInterface $logger
     * @return JsonResponse
     */
    public function show(Throwable $exception, LoggerInterface $logger): JsonResponse
    {
        $e = $exception;
        $logger->critical(sprintf("%s(%s:%s)", $e->getMessage(), $e->getFile(), $e->getLine()));
        $code = $e->getCode() >= 200 ? $e->getCode() : 400;
        return new JsonResponse(["error" => [
            'code' => $e->getCode(),
            'message' => $e->getMessage()
        ]], $code);
    }
}
