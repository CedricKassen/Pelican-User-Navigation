<?php

namespace CedricKassen\UserNavigation\Navigation;

use CedricKassen\UserNavigation\Models\NavigationDefault;
use CedricKassen\UserNavigation\Models\NavigationPreference;

class NavigationLayout
{
    /**
     * Resolves the order and hidden items for the given keys (in panel order). The admin default is applied on top
     * of the panel order and the user's changes on top of that. Items the user's order doesn't contain yet
     * (e.g. from a newly installed plugin) stay behind the item they follow in the default.
     *
     * @param  string[]  $keys
     * @return array{order: string[], hidden: string[]}
     */
    public static function resolve(array $keys, ?NavigationDefault $default, ?NavigationPreference $preference): array
    {
        $order = self::mergeOrder(array_values(array_intersect($default->order ?? [], $keys)), $keys);

        if ($preference?->order) {
            $order = self::mergeOrder(array_values(array_intersect($preference->order, $keys)), $order);
        }

        $hidden = array_values(array_filter($keys, fn (string $key) => match (true) {
            in_array($key, $preference->shown ?? [], true) => false,
            in_array($key, $preference->hidden_items ?? [], true) => true,
            default => in_array($key, $default->hidden_items ?? [], true),
        }));

        return ['order' => $order, 'hidden' => $hidden];
    }

    /**
     * Places the keys of $previous that are missing in $keys right after the key that preceded them in $previous,
     * so items that aren't part of $keys keep their relative position.
     *
     * @param  string[]  $keys
     * @param  string[]  $previous
     * @return string[]
     */
    public static function mergeOrder(array $keys, array $previous): array
    {
        $merged = array_values($keys);

        foreach (array_values($previous) as $index => $key) {
            if (in_array($key, $merged, true)) {
                continue;
            }

            $position = 0;

            for ($i = $index - 1; $i >= 0; $i--) {
                $predecessor = array_search($previous[$i], $merged, true);

                if ($predecessor !== false) {
                    $position = $predecessor + 1;

                    break;
                }
            }

            array_splice($merged, $position, 0, [$key]);
        }

        return $merged;
    }
}
