<?php

namespace CedricKassen\UserNavigation\Filament\Server\Pages;

use CedricKassen\UserNavigation\Filament\Components\NavigationRepeater;
use CedricKassen\UserNavigation\Models\NavigationDefault;
use CedricKassen\UserNavigation\Models\NavigationPreference;
use CedricKassen\UserNavigation\Navigation\NavigationLayout;
use CedricKassen\UserNavigation\Navigation\ServerNavigationItems;
use CedricKassen\UserNavigation\Navigation\UserNavigationManager;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Navigation\NavigationGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;

/**
 * @property Schema $form
 */
class CustomizeNavigation extends Page
{
    use InteractsWithForms;

    protected static ?string $slug = 'customize-navigation';

    // Reached through the user menu, so it can't be hidden by the user.
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.server.pages.server-form-page';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->fillForm();
    }

    public function getTitle(): string
    {
        return trans('user-navigation::strings.title');
    }

    protected function fillForm(): void
    {
        /** @var NavigationGroup[] $groups */
        $groups = UserNavigationManager::withoutPreferences(fn () => Filament::getCurrentPanel()->getNavigation());

        $entries = ServerNavigationItems::fromItems(
            collect($groups)->flatMap(fn (NavigationGroup $group) => collect($group->getItems())->all())->all()
        );

        $layout = NavigationLayout::resolve(array_column($entries, 'key'), NavigationDefault::current(), NavigationPreference::forUser(user()));

        $this->form->fill(['items' => NavigationRepeater::state($entries, $layout)]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                NavigationRepeater::make()
                    ->helperText(trans('user-navigation::strings.helper')),
            ]);
    }

    /** @return Action[] */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('reset')
                ->label(trans('user-navigation::strings.reset'))
                ->tooltip(trans('user-navigation::strings.reset'))
                ->icon('tabler-restore')
                ->color('gray')
                ->requiresConfirmation()
                ->modalDescription(trans('user-navigation::strings.reset_confirmation'))
                ->action(function () {
                    NavigationPreference::forUser(user())?->delete();

                    $this->redirect(static::getUrl());
                }),
            Action::make('save')
                ->label(trans('user-navigation::strings.save'))
                ->tooltip(trans('user-navigation::strings.save'))
                ->icon('tabler-device-floppy')
                ->keyBindings(['mod+s'])
                ->action('save'),
        ];
    }

    public function save(): void
    {
        $layout = NavigationRepeater::parse($this->form->getState()['items'] ?? []);
        $default = NavigationLayout::resolve($layout['keys'], NavigationDefault::current(), null);

        NavigationPreference::firstOrNew(['user_id' => user()->id])->storeOverrides($layout['keys'], $layout['hidden'], $default);

        Notification::make()
            ->title(trans('user-navigation::strings.saved'))
            ->success()
            ->send();

        // The sidebar isn't re-rendered by Livewire, so the page is reloaded to show the new navigation.
        $this->redirect(static::getUrl());
    }
}
