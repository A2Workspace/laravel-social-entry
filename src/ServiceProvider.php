<?php

namespace A2Workspace\SocialEntry;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->publishes([
            __DIR__ . '/../publishes/config.php' => config_path('social-entry.php')
        ], 'social-entry-config');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // $this->app->alias('social-entry', SocialEntry::class);

        $this->commands([
            // Commands\InstallCommand::class,
        ]);

        $this->booting(function () {
            $this->registerLineSocialiteEventListener();
        });
    }

    /**
     * See: https://github.com/SocialiteProviders/Line#add-provider-event-listener
     *
     * @return void
     */
    private function registerLineSocialiteEventListener()
    {
        Event::listen(
            \SocialiteProviders\Manager\SocialiteWasCalled::class,
            \SocialiteProviders\Line\LineExtendSocialite::class . '@handle',
        );
    }
}
