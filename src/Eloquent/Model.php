<?php namespace duxet\Rethinkdb\Eloquent;

use duxet\Rethinkdb\Query\Builder as QueryBuilder;

class Model extends \Illuminate\Database\Eloquent\Model {

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    protected function getDateFormat()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * Get a new query builder instance for the connection.
     *
     * @return Builder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();
        // Check the connection type
        if ($connection instanceof \duxet\Rethinkdb\Connection)
        {
            return new QueryBuilder($connection);
        }
        return parent::newBaseQueryBuilder();
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \duxet\Rethinkdb\Query\Builder $query
     * @return \duxet\Rethinkdb\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

}