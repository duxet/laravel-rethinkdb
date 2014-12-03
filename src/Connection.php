<?php namespace duxet\Rethinkdb;

use \r;
use duxet\Rethinkdb\Query\Builder as QueryBuilder;

class Connection extends \Illuminate\Database\Connection {

    /**
     * The RethinkDB database handler.
     *
     * @var MongoDB
     */
    protected $db;
    /**
     * The RethinkDB connection handler.
     *
     * @var r\Connection
     */
    protected $connection;

    /**
     * Create a new database connection instance.
     *
     * @param  array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->database = $config['database'];

        $port = isset($config['port']) ? $config['port'] : 28015;

        // Create the connection
        $this->connection = r\connect($config['host'],
            $port, $this->database);

        // Select database
        // $this->db = r\db($config['database'])->run($this->connection);
    }

    /**
     * Begin a fluent query against a database table.
     *
     * @param  string  $table
     * @return QueryBuilder
     */
    public function table($table)
    {
        $query = new QueryBuilder($this);
        return $query->from($table);
    }

    /**
     * Get a RethinkDB connection.
     *
     * @return \r\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the elapsed time since a given starting point.
     *
     * @param  int    $start
     * @return float
     */
    public function getElapsedTime($start)
    {
        return parent::getElapsedTime($start);
    }

}