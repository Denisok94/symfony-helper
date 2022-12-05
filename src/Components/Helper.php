<?php

namespace Denisok94\SymfonyHelper\Components;

use denisok94\helper\Helper as BaseHelper;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Helper
 * @package Denisok94\SymfonyHelper\Components
 */
class Helper extends BaseHelper
{
    /**
     * @param Request $request
     * @return bool
     */
    public static function isJsonRequest(Request $request): bool
    {
        $acceptHeader = AcceptHeader::fromString($request->headers->get('Accept'));
        if (!$acceptHeader->has('application/json'))
        {
            $contentHeader = AcceptHeader::fromString($request->headers->get('Content-Type'));
            return $contentHeader->has('application/json');
        }
        return true;
    }

}
