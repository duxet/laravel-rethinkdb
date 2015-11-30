# Migration

## Create a migration file

You can easily create a migration file using the same commands which you have used in laravel like for example :

`php artisan make:migrate Users --create`

which will create a migration file for you to create the users table.

## Configure your Schema Blueprint

Usually laravel migration file is using 

`use Illuminate\Database\Schema\Blueprint`

but here you should replace that with the package Schema Blueprint class like :

`use duxet\Rethinkdb\Schema\Blueprint;`

## Running the migrations

Nothing will change here, you will keep using the same laravel commands which you are used to execute to run the migration.

## Example of Laravel Users Migration file

This is an example of how the laravel Users Migration file has become

	<?php

	// use Illuminate\Database\Schema\Blueprint;
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
