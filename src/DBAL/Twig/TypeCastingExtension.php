<?php

namespace Denisok94\SymfonyHelper\DBAL\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class TypeCastingExtension
 */
class TypeCastingExtension extends AbstractExtension
{
    /**
     * @return array|TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('int', function ($value) {
                return (int) $value;
            }),
            new TwigFilter('int_up', function ($value) {
                return (int) ceil($value);
            }),
            new TwigFilter('int_down', function ($value) {
                return (int) floor($value);
            }),
            new TwigFilter('float', function ($value) {
                return (float) $value;
            }),
            new TwigFilter('string', function ($value) {
                return (string) $value;
            }),
            new TwigFilter('bool', function ($value) {
                return (bool) $value;
            }),
            new TwigFilter('array', function (object $value) {
                return (array) $value;
            }),
            new TwigFilter('object', function (array $value) {
                return (object) $value;
            }),
            new TwigFilter('i', function ($value) {
                return (int) $value;
            }),
            new TwigFilter('f', function ($value) {
                return (float) $value;
            }),
            new TwigFilter('s', function ($value) {
                return (string) $value;
            }),
            new TwigFilter('b', function ($value) {
                return (bool) $value;
            }),
            new TwigFilter('a', function (object $value) {
                return (array) $value;
            }),
            new TwigFilter('o', function (array $value) {
                return (object) $value;
            }),
        ];
    }
}
