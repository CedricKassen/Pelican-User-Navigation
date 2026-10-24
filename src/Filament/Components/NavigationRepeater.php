<?php

namespace CedricKassen\UserNavigation\Filament\Components;

use BackedEnum;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Enums\Alignment;
use Illuminate\Contracts\Support\Htmlable;

/**
 * The reorderable list of navigation items with a visibility toggle, used by the user page and the plugin settings.
 */
class NavigationRepeater
{
    public static function make(string $name = 'items'): Repeater
    {
        return Repeater::make($name)
            ->hiddenLabel()
            ->table([
                TableColumn::make(trans('user-navigation::strings.item')),
                TableColumn::make(trans('user-navigation::strings.visible'))
                    ->alignment(Alignment::Center)
                    ->width('8rem'),
            ])
            ->schema([
                // Hidden fields don't take a table column, but have to come before the visible ones.
                Hidden::make('key'),
                TextEntry::make('label')
                    ->hiddenLabel()
                    ->icon(fn (Get $get) => $get('icon')),
                Toggle::make('visible')
                    ->hiddenLabel(),
            ])
            ->reorderable()
            ->addable(false)
            ->deletable(false);
    }

    /**
     * @param  array<int, array{key: string, label: string, icon: ?string}>  $entries  in panel order
     * @param  array{order: string[], hidden: string[]}  $layout
     * @return array<int, array{key: string, label: string, icon: ?string, visible: bool}>
     */
    public static function state(array $entries, array $layout): array
    {
        return collect($entries)
            ->sortBy(fn (array $entry) => array_search($entry['key'], $layout['order'], true))
            ->map(fn (array $entry) => [...$entry, 'visible' => !in_array($entry['key'], $layout['hidden'], true)])
            ->values()
            ->all();
    }

    /**
     * @param  array<mixed>  $items  the dehydrated repeater state
     * @return array{keys: string[], hidden: string[]}
     */
    public static function parse(array $items): array
    {
        $items = collect($items)
            ->filter(fn ($item) => is_array($item) && is_string($item['key'] ?? null))
            ->values();

        return [
            'keys' => $items->pluck('key')->all(),
            'hidden' => $items->reject(fn (array $item) => (bool) ($item['visible'] ?? true))->pluck('key')->all(),
        ];
    }

    public static function iconName(mixed $icon): ?string
    {
        return match (true) {
            $icon instanceof BackedEnum => (string) $icon->value,
            is_string($icon) => $icon,
            default => null,
        };
    }

    public static function labelText(string|Htmlable $label): string
    {
        return $label instanceof Htmlable ? strip_tags($label->toHtml()) : $label;
    }
}
