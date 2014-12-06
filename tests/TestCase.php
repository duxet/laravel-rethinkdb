<?php

class TestCase extends Orchestra\Testbench\TestCase {

    /**
     * Get package providers.
     *
     * @param  Illuminate\Foundation\Application    $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            'duxet\Rethinkdb\RethinkdbServiceProvider',
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  Illuminate\Foundation\Application    $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // load custom config
        $config = require 'config/database.php';

        // set mongodb as default connection
        $app['config']->set('database.default', 'rethinkdb');

        // overwrite database configuration
        $app['config']->set('database.connections.rethinkdb',
            $config['connections']['rethinkdb']);

        // overwrite cache configuration
        $app['config']->set('cache.driver', 'array');

        // FIXME: There should be better way of doing this.
        if (!Schema::hasTable('items')) Schema::create('items');
        if (!Schema::hasTable('users')) Schema::create('users');
    }

}
