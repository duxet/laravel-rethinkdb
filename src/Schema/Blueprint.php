<?php

namespace duxet\RethinkDB\Schema;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Grammars\Grammar;
use r;

class Blueprint extends \Illuminate\Database\Schema\Blueprint
{
    /**
     * Create a new schema blueprint.
     *
     * @param Connection $connection
     * @param string     $table
     */
    public function __construct(Connection $connection, $table)
    {
        $this->connection = $connection;
        $this->table = $table;
    }

    /**
     * Execute the blueprint against the database.
     *
     * @param \Illuminate\Database\Connection              $connection
     * @param \Illuminate\Database\Schema\Grammars\Grammar $grammar
     *
     * @return void
     */
    public function build(Connection $connection, Grammar $grammar)
    {
    }

    /**
     * Indicate that the table needs to be created.
     *
     * @return bool
     */
    public function create()
    {
        $conn = $this->connection->getConnection();
        $db = r\db($this->connection->getDatabaseName());
        $db->tableCreate($this->table)->run($conn);
    }

    /**
     * Indicate that the collection should be dropped.
     *
     * @return bool
     */
    public function drop()
    {
        $conn = $this->connection->getConnection();
        $db = r\db($this->connection->getDatabaseName());
        $db->tableDrop($this->table)->run($conn);
    }

    /**
     * Specify an index for the collection.
     *
     * @param string $column
     * @param mixed  $options
     *
     * @return Blueprint
     */
    public function index($columns, $name = NULL, $algorithm = NULL)
    {
        $conn = $this->connection->getConnection();
        $db = r\db($this->connection->getDatabaseName());
        $db->table($this->table)->indexCreate($column)
            ->run($conn);

        return $this;
    }
}
