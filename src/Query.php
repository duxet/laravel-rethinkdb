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
        $this->query = r\Db($this->connection->getDatabaseName());
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
        if ($method == 'run')
        {
            return $this->query->run($this->connection->getConnection());
        }
        else
        {
            $this->query = call_user_func_array([$this->query, $method], $parameters);
        }

        return $this;
    }

}