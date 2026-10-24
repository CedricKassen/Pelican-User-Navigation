<?php

namespace CedricKassen\UserNavigation\Navigation;

use CedricKassen\UserNavigation\Filament\Components\NavigationRepeater;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Throwable;

/**
 * Lists the items of the server navigation without a server, for the plugin settings in the admin panel.
 * This includes items that are only shown for some servers (e.g. pages for certain eggs).
 */
class ServerNavigationItems
{
    /** @return array<int, array{key: string, label: string, icon: ?string}> */
    public static function all(): array
    {
        $panel = Filament::getPanel('server');
        $previousPanel = Filament::getCurrentPanel();

        // Labels and icons are read with the server panel as current panel, as they would be when rendering it.
        Filament::setCurrentPanel($panel);

        try {
            $entries = [];

            /** @var class-string<Page> $page */
            foreach ($panel->getPages() as $page) {
                $entries[] = self::read(fn () => $page::shouldRegisterNavigation() ? [
                    'key' => $page,
                    'label' => $page::getNavigationLabel(),
                    'icon' => $page::getNavigationIcon(),
                    'sort' => $page::getNavigationSort(),
                ] : null);
            }

            /** @var class-string<resource> $resource */
            foreach ($panel->getResources() as $resource) {
                $entries[] = self::read(fn () => $resource::shouldRegisterNavigation() && $resource::hasPage('index') ? [
                    'key' => $resource,
                    'label' => $resource::getNavigationLabel(),
                    'icon' => $resource::getNavigationIcon(),
                    'sort' => $resource::getNavigationSort(),
                ] : null);
            }

            foreach ($panel->getNavigationItems() as $item) {
                $entries[] = self::read(fn () => [
                    'key' => $item->getKey(),
                    'label' => $item->getLabel(),
                    'icon' => $item->getIcon(),
                    'sort' => $item->getSort(),
                ]);
            }
        } finally {
            Filament::setCurrentPanel($previousPanel);
        }

        return collect($entries)
            ->filter()
            // Same fallback as NavigationItem::getSort(), so the order matches the rendered navigation.
            ->sortBy(fn (array $entry) => $entry['sort'] ?? -1)
            ->map(fn (array $entry) => [
                'key' => $entry['key'],
                'label' => NavigationRepeater::labelText($entry['label']),
                'icon' => NavigationRepeater::iconName($entry['icon']),
            ])
            ->values()
            ->all();
    }

    /**
     * Converts the items of a built navigation into entries.
     *
     * @param  NavigationItem[]  $items
     * @return array<int, array{key: string, label: string, icon: ?string}>
     */
    public static function fromItems(array $items): array
    {
        return array_map(fn (NavigationItem $item) => [
            'key' => $item->getKey(),
            'label' => NavigationRepeater::labelText($item->getLabel()),
            'icon' => NavigationRepeater::iconName($item->getIcon()),
        ], array_values($items));
    }

    /**
     * Some pages expect a server when reading their navigation details, those are skipped.
     *
     * @param  callable(): ?array<string, mixed>  $callback
     * @return ?array<string, mixed>
     */
    private static function read(callable $callback): ?array
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
