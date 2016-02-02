# Migration

## Create a Migration File

You can easily create a migration file using the following command which will create a migration file for you to create the users table and use the package schema instead of Laravel schema:

`php artisan make:rethink-migration Users --create`

Please note that you can use the same options that you use in `make:migration` with `make:rethink-migration`, as its based on laravel `make:migration`


## Running The Migrations

Nothing will change here, you will keep using the same laravel commands which you are used to execute to run the migration.

## Example of Laravel Users Migration file

This is an example of how the laravel Users Migration file has become

	<?php

	use duxet\Rethinkdb\Schema\Blueprint;
	use Illuminate\Database\Migrations\Migration;

	class CreateUsersTable extends Migration
	{
	    /**
	     * Run the migrations.
	     *
	     * @return void
	     */
	    public function up()
	    {
	        Schema::create('users', function (Blueprint $table) {
	            $table->increments('id');
	            $table->string('name');
	            $table->string('email')->unique();
	            $table->string('password', 60);
	            $table->rememberToken();
	            $table->timestamps();
	        });
	    }

	    /**
	     * Reverse the migrations.
	     *
	     * @return void
	     */
	    public function down()
	    {
	        Schema::drop('users');
	    }
	}
