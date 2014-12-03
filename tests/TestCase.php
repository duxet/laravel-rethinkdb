<?php

class TestCase extends Orchestra\Testbench\TestCase {

    /**
     * Get package providers.
     *
     * @return array
     */
    protected function getPackageProviders()
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
    }

}
