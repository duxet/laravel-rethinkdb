<?php namespace duxet\Rethinkdb\Query;

use r;
use duxet\Rethinkdb\Connection;
use duxet\Rethinkdb\Query;
use duxet\Rethinkdb\RQL\FilterBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Symfony\Component\VarDumper\Cloner\Cursor;

class Builder extends QueryBuilder
{

    /**
     * The query instance.
     *
     * @var Query
     */
    protected $query;

    /**
     * The r\Table instance.
     *
     * @var r\Table
     */
    protected $table;

    /**
     * All of the available clause operators.
     *
     * @var array
     */
    protected $operators = array(
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'between', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*',
        'contains', 'exists', 'type', 'mod', 'size'
    );

    /**
     * Create a new query builder instance.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->query = new Query($this->connection);
    }

    /**
     * Set the collection which the query is targeting.
     *
     * @param  string $table
     * @return Builder
     */
    public function from($table)
    {
        if ($table) {
            $this->table = r\table($table);
            $this->query->table($table);
        }
        return parent::from($table);
    }

    /**
     * Execute the query as a fresh "select" statement.
     *
     * @param  array $columns
     * @return array|static[]
     */
    public function getFresh($columns = array())
    {
        $this->compileOrders();
        $this->compileWheres();

        if ($this->offset)    $this->query->skip($this->offset);
        if ($this->limit)     $this->query->limit($this->limit);
        if ($this->columns)   $columns = $this->columns;

        if ( ! empty($columns) && $columns[0] != '*')
        {
            $this->query->pluck($columns);
        }

        $results = $this->query->run();
        if (is_object($results)) {
            $results = $results->toArray();
        }

        if (isset($results['$reql_type$'])
            && $results['$reql_type$'] === 'GROUPED_DATA')
        {
            return $results['data'];
        }

        return $results;
    }

    /**
     * Compile orders into query.
     *
     */
    public function compileOrders()
    {
        if (!$this->orders) return;

        foreach($this->orders as $order) {
            $column = $order['column'];
            $direction = $order['direction'];

            $compiled = strtolower($direction) == 'asc'
                ? r\asc($column) : r\desc($column);

            // Use index as field if needed
            if ($order['index'])
            {
                $compiled = ['index' => $compiled];
            }

            $this->query->orderBy($compiled);
        }
    }

    /**
     * Insert a new record into the database.
     *
     * @param  array $values
     * @return bool
     */
    public function insert(array $values)
    {
        $this->compileWheres();
        $result = $this->query->insert($values);
        return (0 == (int) $result['errors']);
    }

    /**
     * Insert a new record and get the value of the primary key.
     *
     * @param  array $values
     * @param  string $sequence
     * @return int
     */
    public function insertGetId(array $values, $sequence = null)
    {
        $this->compileWheres();
        $result = $this->query->insert($values);

        if (0 == (int) $result['errors']) {
            if (isset($values['id'])) return $values['id'];

            // Return id
            return current($result['generated_keys']);
        }
    }

    /**
     * Update a record in the database.
     *
     * @param  array $values
     * @param  array $options
     * @return int
     */
    public function update(array $values, array $options = array())
    {
        return $this->performUpdate($values, $options);
    }

    /**
     * Perform an update query.
     *
     * @param  array $query
     * @param  array $options
     * @return int
     */
    protected function performUpdate($query, array $options = array())
    {
        $this->compileWheres();
        $result = $this->query->update($query)->run();

        if (0 == (int)$result['errors']) {
            return $result['replaced'];
        }

        return 0;
    }

    /**
     * Delete a record from the database.
     *
     * @param  mixed $id
     * @return int
     */
    public function delete($id = null)
    {
        // If an ID is passed to the method, we will set the where clause to check
        // the ID to allow developers to simply and quickly remove a single row
        // from their database without manually specifying the where clauses.
        if (!is_null($id)) {
            $this->where('id', '=', $id);
        }
        $this->compileWheres();
        return $this->query->delete()->run();
    }

    /**
     * Compile the where array to filter chain.
     *
     * @return array
     */
    protected function compileWheres()
    {
        // Wheres to compile
        $wheres = $this->wheres;

        // If there is nothing to do, then return
        if (!$wheres) return;

        $this->query->filter(function($document) use($wheres) {
            $builder = new FilterBuilder($document);
            return $builder->compileWheres($wheres);
        });
    }

    public function buildFilter($document)
    {
        $builder = new FilterBuilder($document);
        return $builder->compileWheres($this->wheres);
    }

    /**
     * Run a truncate statement on the table.
     *
     * @return void
     */
    public function truncate()
    {
        $result = $this->query->delete()->run();
        return (0 == (int) $result['errors']);
    }

    /**
     * Append one or more values to an array.
     *
     * @param  mixed   $column
     * @param  mixed   $value
     * @param  bool    $unique
     * @return bool
     */
    public function push($column, $value = null, $unique = false)
    {
        $operation = $unique ? 'merge' : 'append';

        $this->compileWheres();
        $result = $this->query->update([
            $column => r\row($column)->{$operation}($value)
        ])->run();

        return (0 == (int) $result['errors']);
    }

    /**
     * Remove one or more values from an array.
     *
     * @param  mixed   $column
     * @param  mixed   $value
     * @return bool
     */
    public function pull($column, $value = null)
    {
        $this->compileWheres();
        $result = $this->query->update([
            $column => r\row($column)->difference([$value])
        ])->run();

        return (0 == (int) $result['errors']);
    }

    /**
     * Force the query to only return distinct results.
     *
     * @var    string   $column
     * @return Builder
     */
    public function distinct($column = null)
    {
        if ($column) $column = ['index' => $column];

        $this->query = $this->query->distinct($column);

        return $this;
    }

    /**
     * Retrieve the "count" result of the query.
     *
     * @param  string  $columns
     * @return int
     */
    public function count($columns = null)
    {
        $this->compileWheres();
        $result = $this->query->count();
        return (int) $result;
    }

    /**
     * Retrieve the sum of the values of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
    public function sum($column)
    {
        $this->compileWheres();
        $result = $this->query->sum($column);
        return $result;
    }

    /**
     * Retrieve the minimum value of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
    public function min($column)
    {
        $this->compileWheres();
        $result = $this->query->min($column)
            ->getField($column)->rDefault(null)
            ->run();
        return $result;
    }
    /**
     * Retrieve the maximum value of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
    public function max($column)
    {
        $this->compileWheres();
        $result = $this->query->max($column)
            ->getField($column)->rDefault(null)
            ->run();
        return $result;
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * @param  string  $column
     * @return mixed
     */
    public function avg($column)
    {
        $this->compileWheres();
        $result = $this->query->avg($column)
            ->rDefault(null)->run();
        return $result;
    }

    /**
     * Remove one or more fields.
     *
     * @param  mixed $columns
     * @return int
     */
    public function drop($columns)
    {
        if ( ! is_array($columns)) $columns = array($columns);

        $this->compileWheres();
        $result = $this->query->replace(function($doc) use ($columns) {
            return $doc->without($columns);
        })->run();

        return (0 == (int) $result['errors']);
    }

    /**
     * Add a "group by" clause to the query.
     *
     * @param  array|string  $column,...
     * @return $this
     */
    public function groupBy()
    {
        foreach (func_get_args() as $arg)
        {
            $this->query->group($arg)->ungroup()->map(function($doc) {
                return $doc('reduction')->nth(0);
            });
        }
        return $this;
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param  string  $column
     * @param  string  $direction
     * @param  bool    $index
     * @return $this
     */
    public function orderBy($column, $direction = 'asc', $index = false)
    {
        $property = $this->unions ? 'unionOrders' : 'orders';
        $direction = strtolower($direction) == 'asc' ? 'asc' : 'desc';
        $this->{$property}[] = compact('column', 'direction', 'index');
        return $this;
    }

    /**
     * Add a where between statement to the query.
     *
     * @param  string  $column
     * @param  array   $values
     * @param  string  $boolean
     * @param  bool  $not
     * @return Builder
     */
    public function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        $type = 'between';
        $this->wheres[] = compact('column', 'type', 'boolean', 'values', 'not');
        return $this;
    }

    /**
     * Handle dynamic method calls into the method.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ($method == 'unset')
        {
            return call_user_func_array(array($this, 'drop'), $parameters);
        }
        return parent::__call($method, $parameters);
    }
}
