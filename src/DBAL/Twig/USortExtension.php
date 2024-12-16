<?php

namespace Denisok94\SymfonyHelper\DBAL\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 *
 */
class USortExtension extends AbstractExtension
{
    /**
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('usort', [$this, 'usort']),
        ];
    }
    public function usort($array): array
    {
        uasort($array, function ($item1,  $item2) {
            if ($item1 == $item2) return 0;
            return $item1 < $item2 ? -1 : 1;
        });
        return $array;
    }
}
