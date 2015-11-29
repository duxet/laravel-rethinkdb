<?php

namespace duxet\Rethinkdb;

use duxet\Rethinkdb\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use duxet\Rethinkdb\Migrations\MigrationCreator;
use duxet\Rethinkdb\Console\Migrations\MigrateMakeCommand;

class RethinkdbServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        Model::setConnectionResolver($this->app['db']);
        Model::setEventDispatcher($this->app['events']);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->resolving('db', function ($db) {
            $db->extend('rethinkdb', function ($config) {
                return new Connection($config);
            });
        });

        $this->app->singleton('command.rethink-migrate.make', function ($app) {
            
            $creator = new MigrationCreator($app['files']);
            $composer = $app['composer'];
            
            return new MigrateMakeCommand($creator, $composer);
        });

        $this->commands('command.rethink-migrate.make');
    }

    public function provides()
    {
        return [ 'command.rethink-migrate.make', ];
    }
}
