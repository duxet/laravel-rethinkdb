<?php namespace duxet\Rethinkdb;

class Table {

    /**
     * The connection instance.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * The RethinkDB Table instance.
     *
     * @var \r\Table
     */
    protected $table;

    /**
     * Constructor.
     *
     * @param Connection $connection
     * @param \r\Table $table
     */
    public function __construct(Connection $connection, \r\Table $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * Handle dynamic method calls.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $query = new Query($this->connection, $this);

        call_user_func_array([$query, $method], $parameters);

        return $query;
    }

}