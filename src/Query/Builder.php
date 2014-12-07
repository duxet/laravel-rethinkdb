<?php namespace duxet\Rethinkdb\Query;

use r;
use duxet\Rethinkdb\Connection;
use duxet\Rethinkdb\Query;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Builder extends QueryBuilder
{

    /**
     * The query instance.
     *
     * @var Query
     */
    protected $query;

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
        if ( ! empty($columns) && $columns[0] != '*')
        {
            $this->query = $this->query->pluck($columns);
        }

        $results = $this->query->run()->toNative();

        return $results;
    }

    /**
     * Insert a new record into the database.
     *
     * @param  array $values
     * @return bool
     */
    public function insert(array $values)
    {
        $result = $this->query->insert($values)->run()->toNative();
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
        $result = $this->query->insert($values)->run()->toNative();

        if (0 == (int) $result['errors']) {
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
        $result = $this->query->update($query)->run()->toNative();

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
        return $this->query->delete()->run()->toNative();
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  mixed $value
     * @param  string $boolean
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // If the column is an array, we will assume it is an array of key-value pairs
        // and can add them each as a where clause. We will maintain the boolean we
        // received when the method was called and pass it into the nested where.
        if (is_array($column)) {
            return $this->whereNested(function ($query) use ($column) {
                foreach ($column as $key => $value) {
                    $query->where($key, '=', $value);
                }
            }, $boolean);
        }
        // Here we will make some assumptions about the operator. If only 2 values are
        // passed to the method, we will assume that the operator is an equals sign
        // and keep going. Otherwise, we'll require the operator to be passed in.
        if (func_num_args() == 2) {
            list($value, $operator) = array($operator, '=');
        } elseif ($this->invalidOperatorAndValue($operator, $value)) {
            throw new \InvalidArgumentException("Value must be provided.");
        }
        // If the columns is actually a Closure instance, we will assume the developer
        // wants to begin a nested where statement which is wrapped in parenthesis.
        // We'll add that Closure to the query then return back out immediately.
        if ($column instanceof Closure) {
            return $this->whereNested($column, $boolean);
        }
        // If the given operator is not found in the list of valid operators we will
        // assume that the developer is just short-cutting the '=' operators and
        // we will set the operators to '=' and set the values appropriately.
        if (!in_array(strtolower($operator), $this->operators, true)) {
            list($value, $operator) = array($operator, '=');
        }
        // If the value is a Closure, it means the developer is performing an entire
        // sub-select within the query and we will need to compile the sub-select
        // within the where clause to get the appropriate query record results.
        if ($value instanceof Closure) {
            return $this->whereSub($column, $operator, $value, $boolean);
        }

        $operator = strtolower($operator);

        switch ($operator)
        {
            case '>':
                $this->query = $this->query->filter(function($x) use ($column, $value) {
                    return $x($column)->gt($value);
                });
                break;
            case '>=':
                $this->query = $this->query->filter(function($x) use ($column, $value) {
                    return $x($column)->ge($value);
                });
                break;
            case '<':
                $this->query = $this->query->filter(function($x) use ($column, $value) {
                    return $x($column)->lt($value);
                });
                break;
            case '<=':
                $this->query = $this->query->filter(function($x) use ($column, $value) {
                    return $x($column)->le($value);
                });
                break;
            case '<>':
                $this->query = $this->query->filter(function($x) use ($column, $value) {
                    return $x($column)->ne($value);
                });
                break;
            case '!=':
                $this->query = $this->query->filter(function($x) use ($column, $value) {
                    return $x($column)->ne($value);
                });
                break;
            case 'contains':
                $this->query = $this->query->filter(function($x) use ($column, $value) {
                    return $x($column)->contains($value);
                });
                break;
            case 'exists':
                $this->query = $this->query->filter(function($x) use ($column, $value) {
                    $x = $x->hasFields($column);
                    if (!$value) $x = $x->not();
                    return $x;
                });
                break;
            case 'type':
                $this->query = $this->query->filter(function($x) use ($column, $value) {
                    return $x($column)->typeOf()->eq(strtoupper($value));
                });
                break;
            case 'mod':
                $this->query = $this->query->filter(function($x) use ($column, $value) {
                    $mod = $x($column)->mod((int) $value[0])->eq((int) $value[1]);
                    return $x($column)->typeOf()->eq('NUMBER')->rAnd($mod);
                });
                break;
            case 'size':
                $this->query = $this->query->filter(function($x) use ($column, $value) {
                    $size = $x($column)->count()->eq((int) $value);
                    return $x($column)->typeOf()->eq('ARRAY')->rAnd($size);
                });
                break;
            case 'regexp':
                $this->query = $this->query->filter(function($x) use ($column, $value) {
                    $match = $x($column)->match($value);
                    return $x($column)->typeOf()->eq('STRING')->rAnd($match);
                });
                break;
            case 'not regexp':
                $this->query = $this->query->filter(function($x) use ($column, $value) {
                    $match = $x($column)->match($value)->not();
                    return $x($column)->typeOf()->eq('STRING')->rAnd($match);
                });
                break;
            case 'like':
                $this->query = $this->query->filter(function($x) use ($column, $value) {
                    $regex = str_replace('%', '', $value);

                    // Convert like to regular expression.
                    if ( ! starts_with($value, '%')) $regex = '^' . $regex;
                    if ( ! ends_with($value, '%'))   $regex = $regex . '$';

                    $match = $x($column)->match('(?i)'. $regex);
                    return $x($column)->typeOf()->eq('STRING')->rAnd($match);
                });
                break;
            default:
                $this->query = $this->query->filter([$column => $value]);
        }


        return $this;
    }

    /**
     * Run a truncate statement on the table.
     *
     * @return void
     */
    public function truncate()
    {
        $result = $this->query->delete()->run()->toNative();
        return (0 == (int) $result['errors']);
    }

    /**
     * Set the "offset" value of the query.
     *
     * @param  int  $value
     * @return $this
     */
    public function offset($value)
    {
        $this->query = $this->query->skip(max(0, $value));
        return $this;
    }

    /**
     * Set the "limit" value of the query.
     *
     * @param  int $value
     * @return $this
     */
    public function limit($value)
    {
        if ($value > 0) {
            $this->query = $this->query->limit($value);
        }
        return $this;
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

        $result = $this->query->update([
            $column => r\row($column)->{$operation}($value)
        ])->run()->toNative();

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
        $result = $this->query->update([
            $column => r\row($column)->difference([$value])
        ])->run()->toNative();

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
     * Add an "order by" clause to the query.
     *
     * @param  string  $column
     * @param  string  $direction
     * @return $this
     */
    public function orderBy($column, $direction = 'asc')
    {
        $direction = strtolower($direction) == 'asc'
            ? r\asc($column) : r\desc($column);
        $this->query = $this->query->orderBy([$direction]);
        return $this;
    }

    /**
     * Set the columns to be selected.
     *
     * @param  array  $columns
     * @return $this
     */
    public function select($columns = array('*'))
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $this->query = $this->query->pluck($columns);
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
        $result = $this->query->count()->run()->toNative();
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
        $result = $this->query->sum($column)->run()->toNative();
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
        $result = $this->query->min($column)
            ->getField($column)->run()->toNative();
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
        $result = $this->query->max($column)
            ->getField($column)->run()->toNative();
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
        $result = $this->query->avg($column)->run()->toNative();
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

        $result = $this->query->replace(function($doc) use ($columns) {
            return $doc->without($columns);
        })->run()->toNative();

        return (0 == (int) $result['errors']);
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