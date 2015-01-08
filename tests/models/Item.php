<?php

use \duxet\Rethinkdb\Eloquent\Model;

class Item extends Model {

    protected $table = 'items';
    protected static $unguarded = true;

    public function user()
    {
        return $this->belongsTo('User');
    }

    public function scopeSharp($query)
    {
        return $query->where('type', 'sharp');
    }

}
