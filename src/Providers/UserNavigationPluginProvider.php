<?php

namespace CedricKassen\UserNavigation\Providers;

use CedricKassen\UserNavigation\Navigation\UserNavigationManager;
use Filament\Navigation\NavigationManager;
use Illuminate\Support\ServiceProvider;

class UserNavigationPluginProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Filament binds its navigation manager in its own provider, so it is replaced on boot to make sure it isn't overwritten again.
        $this->app->scoped(NavigationManager::class, fn () => new UserNavigationManager());
    }
}
