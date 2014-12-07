<?php

use \duxet\Rethinkdb\Eloquent\Model;

class User extends Model {

    protected $dates = ['birthday', 'entry.date'];
    protected static $unguarded = true;

}