<?php

namespace timolake\LivewireTables;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use timolake\LivewireTables\Commands\MakeLivewireTableCommand;
use timolake\LivewireTables\Commands\ScaffoldLivewireTableCommand;
use timolake\LivewireTables\Tests\Tables\PostTable;

class LivewireTablesServiceProvider extends ServiceProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
         $this->loadViewsFrom(__DIR__.'/../resources/views', 'livewire-tables');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();

            $this->commands([
                MakeLivewireTableCommand::class,
                ScaffoldLivewireTableCommand::class,
            ]);
        }

        Livewire::component('post-table', PostTable::class);

    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/livewire-tables.php', 'livewire-tables');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['livewire-tables'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole()
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/livewire-tables.php' => config_path('livewire-tables.php'),
        ], 'livewire-tables');

        // Publishing the views.
        $this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/livewire-tables'),
        ], 'livewire-tables.views');

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/timolake'),
        ], 'livewire-tables.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/timolake'),
        ], 'livewire-tables.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
