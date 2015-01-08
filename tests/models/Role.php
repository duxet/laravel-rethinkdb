<?php

use \duxet\Rethinkdb\Eloquent\Model;

class Role extends Model {

    protected $table = 'roles';
    protected static $unguarded = true;

    public function user()
    {
        return $this->belongsTo('User');
    }

}
