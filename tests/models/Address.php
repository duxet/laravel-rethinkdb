<?php

use \duxet\Rethinkdb\Eloquent\Model;

class Address extends Model {

    protected static $unguarded = true;

    public function addresses()
    {
        return $this->embedsMany('Address');
    }

}
