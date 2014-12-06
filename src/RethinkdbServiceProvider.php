<?php namespace duxet\Rethinkdb;

use Illuminate\Support\ServiceProvider;

class RethinkdbServiceProvider extends ServiceProvider {

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->resolving('db', function($db)
        {
            $db->extend('rethinkdb', function($config)
            {
                return new Connection($config);
            });
        });
    }

}
