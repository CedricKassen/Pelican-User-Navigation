<?php

namespace CedricKassen\UserNavigation;

use App\Contracts\Plugins\HasPluginSettings;
use CedricKassen\UserNavigation\Filament\Components\NavigationRepeater;
use CedricKassen\UserNavigation\Filament\Server\Pages\CustomizeNavigation;
use CedricKassen\UserNavigation\Models\NavigationDefault;
use CedricKassen\UserNavigation\Models\NavigationPreference;
use CedricKassen\UserNavigation\Navigation\NavigationLayout;
use CedricKassen\UserNavigation\Navigation\ServerNavigationItems;
use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Panel;

class UserNavigationPlugin implements HasPluginSettings, Plugin
{
    public function getId(): string
    {
        return 'user-navigation';
    }

    public function register(Panel $panel): void
    {
        $id = str($panel->getId())->title();

        $panel->discoverPages(plugin_path($this->getId(), "src/Filament/$id/Pages"), "CedricKassen\\UserNavigation\\Filament\\$id\\Pages");

        if ($panel->getId() === 'server') {
            $panel->userMenuItems([
                Action::make('customize_navigation')
                    ->label(fn () => trans('user-navigation::strings.title'))
                    ->icon('tabler-layout-sidebar')
                    ->url(fn () => CustomizeNavigation::getUrl()),
            ]);
        }
    }

    public function boot(Panel $panel): void {}

    public function getSettingsFormData(): array
    {
        $entries = ServerNavigationItems::all();
        $layout = NavigationLayout::resolve(array_column($entries, 'key'), NavigationDefault::current(), null);

        return [
            'items' => NavigationRepeater::state($entries, $layout),
            'reset_user_order' => false,
        ];
    }

    public function getSettingsForm(): array
    {
        return [
            NavigationRepeater::make()
                ->helperText(trans('user-navigation::strings.settings.helper')),
            Toggle::make('reset_user_order')
                ->label(trans('user-navigation::strings.settings.reset_user_order'))
                ->helperText(trans('user-navigation::strings.settings.reset_user_order_helper')),
        ];
    }

    public function saveSettings(array $data): void
    {
        $layout = NavigationRepeater::parse($data['items'] ?? []);

        (NavigationDefault::current() ?? new NavigationDefault())->storeLayout($layout['keys'], $layout['hidden']);

        if ($data['reset_user_order'] ?? false) {
            NavigationPreference::query()->update(['order' => null]);
        }

        Notification::make()
            ->title(trans('user-navigation::strings.settings.saved'))
            ->success()
            ->send();
    }
}
