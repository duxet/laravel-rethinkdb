# Installation

## Requirements.

1. Rethinkdb : You need to make sure that you have installed [rethinkdb](http://www.rethinkdb.com) successfully, you can reffer to rethinkdb [documentation](https://rethinkdb.com/docs/) for the full instruction of how to install rethinkdb.

1. Laravel 5.2 : this package was designed to work with [laravel](http://laravel.com) 5.2, so it will not work with laravel 4.x.

## Installation

To fully install this package you will have either to add it manually to your `composer.json` file, or you can execute the following command :

`composer require "duxet/laravel-rethinkdb:dev-master"`

This will install the package and all the required package for it to work.

## Service Provider

After you install the library you will need to add the `Service Provider` file to your `app.php` file like :

`duxet\Rethinkdb\RethinkdbServiceProvider::class,`

inside your `providers` array.

## Database configuration

Now that you have the service provider setup, you will need to add the following configuration array at the end of your database connections array like :

        'rethinkdb' => [
            'name'      => 'rethinkdb',
            'driver'    => 'rethinkdb',
            'host'      => env('DB_HOST', 'localhost'),
            'port'      => env('DB_PORT', 28015),
            'database'  => env('DB_DATABASE', 'homestead'),            
        ]

After you add it, you can just configure your enviroment file to be something like :

	DB_HOST=localhost
	DB_DATABASE=homestead
	DB_CONNECTION=rethinkdb

but you can always updatr your `DB_HOST` to point to the IP where you have installed rethinkdb.