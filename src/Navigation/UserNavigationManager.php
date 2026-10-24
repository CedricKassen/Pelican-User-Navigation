<?php

namespace CedricKassen\UserNavigation\Navigation;

use CedricKassen\UserNavigation\Models\NavigationDefault;
use CedricKassen\UserNavigation\Models\NavigationPreference;
use Closure;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationManager;

/**
 * Applies the admin default and the user's preference to the navigation of panels the plugin is registered on.
 */
class UserNavigationManager extends NavigationManager
{
    private static bool $withoutPreferences = false;

    private bool $layoutLoaded = false;

    private ?NavigationDefault $default = null;

    private ?NavigationPreference $preference = null;

    /**
     * Runs the callback with the navigation as the panel would render it without the admin default and user preferences.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function withoutPreferences(Closure $callback): mixed
    {
        $previous = self::$withoutPreferences;
        self::$withoutPreferences = true;

        try {
            return $callback();
        } finally {
            self::$withoutPreferences = $previous;
        }
    }

    /** @return array<NavigationGroup> */
    public function get(): array
    {
        $groups = parent::get();

        if (self::$withoutPreferences || !$this->panel->hasPlugin('user-navigation')) {
            return $groups;
        }

        if (!$this->layoutLoaded) {
            $this->default = NavigationDefault::current();
            $this->preference = NavigationPreference::forUser(user());
            $this->layoutLoaded = true;
        }

        if (!$this->default && !$this->preference) {
            return $groups;
        }

        foreach ($groups as $group) {
            $items = collect($group->getItems());
            $keys = $items->map(fn (NavigationItem $item) => $item->getKey())->values()->all();

            $layout = NavigationLayout::resolve($keys, $this->default, $this->preference);

            $group->items(
                $items
                    ->reject(fn (NavigationItem $item) => in_array($item->getKey(), $layout['hidden'], true))
                    ->sortBy(fn (NavigationItem $item) => array_search($item->getKey(), $layout['order'], true))
                    ->values()
            );
        }

        return array_values(array_filter($groups, fn (NavigationGroup $group) => filled($group->getItems())));
    }
}
