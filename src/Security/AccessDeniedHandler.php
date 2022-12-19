<?php

namespace Denisok94\SymfonyHelper\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

/**
 * Class AccessDeniedHandler
 * 
 * https://github.com/symfony/symfony-docs/blob/4.4/security/access_denied_handler.rst
 * @package Denisok94\SymfonyHelper\Security
 */
class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    /**
     *
     * @param Request $request
     * @param AccessDeniedException $accessDeniedException
     * @return JsonResponse|null
     */
    public function handle(Request $request, AccessDeniedException $accessDeniedException): ?JsonResponse
    {
        // ...
        return new JsonResponse(['code' => 403, 'message' => 'Access Denied'], 403);
    }
}
