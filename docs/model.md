# Model

## Create a Model Class

You can easily create a model class using the following command which will create it for you and use the package model instead of Laravel model:

`php artisan make:rethink-model News`

Please note that you can use the same options that you use in `make:model` with `make:rethink-model`, as its based on laravel `make:model`

## Example of Laravel News Model Class

This is an example of how the laravel model class has become

	<?php

	namespace App;

	use \duxet\Rethinkdb\Eloquent\Model;

	class News extends Model
	{
	    //
	}
