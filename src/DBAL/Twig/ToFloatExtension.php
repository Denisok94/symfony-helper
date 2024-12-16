<?php

namespace Denisok94\SymfonyHelper\DBAL\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 *
 */
class ToFloatExtension extends AbstractExtension
{
    /**
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('to_float', [$this, 'toFloat']),
        ];
    }

    public function toFloat($number, int $decimals = 0, string $decPoint = ',', string $thousandsSep = ' '): string
    {
        $float = number_format($number, $decimals, $decPoint, $thousandsSep);
        // if ($decimals > 0) {
        //     $myArray = explode(',', $float);
        //     if ($myArray[1] == 0) $float = $myArray[0];
        // }
        return $float;
    }
}

