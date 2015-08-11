<?php

namespace duxet\RethinkDB\Schema;

use Closure;
use duxet\Rethinkdb\Connection;
use duxet\Rethinkdb\Schema\Blueprint;
use r;

class Builder extends \Illuminate\Database\Schema\Builder
{
    /**
     * Create a new database Schema manager.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Determine if the given table exists.
     *
     * @param string $table
     *
     * @return bool
     */
    public function hasTable($table)
    {
        $conn = $this->connection->getConnection();
        $db = r\db($this->connection->getDatabaseName());
        $tables = $db->tableList()->run($conn);

        return in_array($table, $tables);
    }

    /**
     * Create a new table on the schema.
     *
     * @param string  $table
     * @param Closure $callback
     *
     * @return bool
     */
    public function create($table, Closure $callback = null)
    {
        $blueprint = $this->createBlueprint($table);
        $blueprint->create();
        if ($callback) {
            $callback($blueprint);
        }
    }

    /**
     * Drop a table from the schema.
     *
     * @param string $table
     *
     * @return bool
     */
    public function drop($table)
    {
        $blueprint = $this->createBlueprint($table);

        return $blueprint->drop();
    }

    /**
     * Modify a table on the schema.
     *
     * @param string  $table
     * @param Closure $callback
     *
     * @return bool
     */
    public function table($table, Closure $callback)
    {
        $blueprint = $this->createBlueprint($table);
        if ($callback) {
            $callback($blueprint);
        }
    }

    /**
     * Create a new command set with a Closure.
     *
     * @param string  $table
     * @param Closure $callback
     *
     * @return Blueprint
     */
    protected function createBlueprint($table, Closure $callback = null)
    {
        return new Blueprint($this->connection, $table);
    }
}
