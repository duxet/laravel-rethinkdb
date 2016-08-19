<?php

namespace duxet\Rethinkdb;

use r;

class Query
{
    /**
     * The connection instance.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * The \r\ValuedQuery instance.
     *
     * @var \r\ValuedQuery
     */
    protected $query;

    /**
     * Constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->query = r\Db($connection->getDatabaseName());
    }

    /**
     * Handle dynamic method calls.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $autoRun = ['count', 'sum', 'insert'];

        $query = call_user_func_array([$this->query, $method], $parameters);

        if (in_array($method, $autoRun)) {
            return $this->run($query);
        }

        $this->query = $query;

        return $this;
    }

    public function run($query = null)
    {
        $start = microtime(true);
        $query = $query ?: $this->query;

        $connection = $this->connection->getConnection();
        $result = $query->run($connection);

        $query = strval($this->query);
        $time = $this->connection->getElapsedTime($start);
        $this->connection->logQuery($query, [], $time);

        return $this->nativeArray($result);
    }

    private function nativeArray($val)
    {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $val[$k] = $this->nativeArray($v);
            }

            return $val;
        } elseif (is_object($val) && $val instanceof \ArrayObject) {
            return $val->getArrayCopy();
        } else {
            return $val;
        }
    }
}
