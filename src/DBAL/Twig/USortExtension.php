<?php

namespace Denisok94\SymfonyHelper\DBAL\Twig;

use Twig\Extension\AbstractExtension;
use App\Model\Response\RecommendedWidget\Widgets\WidgetItem;
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
            if ($item1 instanceof WidgetItem && $item2 instanceof WidgetItem) {
                $v1 = $item1->getValue();
                $v2 = $item2->getValue();
                if ($v1 == $v2) return 0;
                if (!is_array($v1)) {
                    // error_log("$v1, $v2");
                    return $v1 < $v2 ? -1 : 1;
                } else {
                    $v1 = end($v1);
                    $v2 = end($v2);
                    // error_log("$v1, $v2");
                    if ($v1 == $v2) return 0;
                    return $v1 < $v2 ? -1 : 1;
                }
            } else {
                if ($item1 == $item2) return 0;
                return $item1 < $item2 ? -1 : 1;
            }
        });
        return $array;
    }
}
