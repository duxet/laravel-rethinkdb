<?php namespace duxet\Rethinkdb;

use r;

class Query {

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
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $autoRun = ['count', 'sum', 'insert'];

        $this->query = call_user_func_array([$this->query, $method], $parameters);

        if (in_array($method, $autoRun))
        {
            return $this->run()->toNative();
        }

        return $this;
    }

    public function run()
    {
        $start = microtime(true);

        $connection = $this->connection->getConnection();
        $result = $this->query->run($connection);

        $query = strval($this->query);
        $time = $this->connection->getElapsedTime($start);
        $this->connection->logQuery($query, [], $time);

        return $result;
    }

}