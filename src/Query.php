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
        $this->connection = $connection->getConnection();
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
        $autoRun = ['count', 'sum', 'avg'];

        if (in_array($method, $autoRun))
        {
            $query = call_user_func_array([$this->query, $method], $parameters);
            return $query->run($this->connection)->toNative();
        }
        else
        {
            $this->query = call_user_func_array([$this->query, $method], $parameters);
        }

        return $this;
    }

    public function run()
    {
        return $this->query->run($this->connection);
    }

}