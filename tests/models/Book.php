<?php

use \duxet\Rethinkdb\Eloquent\Model;

class Book extends Model
{
    protected $table = 'books';
    protected static $unguarded = true;
    protected $primaryKey = 'title';

    public function author()
    {
        return $this->belongsTo('User', 'author_id');
    }
}
