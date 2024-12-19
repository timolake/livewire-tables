<?php

namespace timolake\LivewireTables\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as orchestra;

abstract class TestCase extends orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            function ( string $modelName) {
                return 'timolake\\LivewireTables\\Factories\\'.class_basename($modelName).'Factory';
            }
        );

        Factory::guessModelNamesUsing(
            function (Factory $factory) {
                $modelName = Str::replaceLast('Factory', '', class_basename(get_class($factory)));
                return 'timolake\\LivewireTables\\Tests\\Models\\'.$modelName;
            }
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LivewireServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        Schema::dropAllTables();

        $migration = include __DIR__.'/database/migrations/20241218_094800_create_posts_table.php';
        $migration->up();

        $migration = include __DIR__.'/database/migrations/20241218_094800_create_users_table.php';
        $migration->up();

        $migration = include __DIR__.'/database/migrations/20241218_094800_create_comments_table.php';
        $migration->up();

        $migration = include __DIR__.'/database/migrations/20241218_094800_create_tags_table.php';
        $migration->up();
        
        $migration = include __DIR__.'/database/migrations/20241218_094800_create_colors_table.php';
        $migration->up();

        View::addLocation(__DIR__ . "/../resources/views");
        View::addLocation(__DIR__ . "/views");


    }


}
