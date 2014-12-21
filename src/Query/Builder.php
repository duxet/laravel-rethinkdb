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
        $this->compileWheres();

        if ($this->offset)    $this->query->skip($this->offset);
        if ($this->limit)     $this->query->limit($this->limit);
        if ($this->columns)   $columns = $this->columns;

        if ( ! empty($columns) && $columns[0] != '*')
        {
            $this->query->pluck($columns);
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
        $this->compileWheres();
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
        $this->compileWheres();
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
        $this->compileWheres();
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
        $this->compileWheres();
        return $this->query->delete()->run()->toNative();
    }

    /**
     * Compile the where array to filter chain.
     *
     * @return array
     */
    protected function compileWheres()
    {
        // The wheres to compile.
        $wheres = $this->wheres;

        // If there is nothing to do, then return
        if (!$wheres) return;

        $filters = null;

        foreach ($wheres as $i => &$where)
        {
            $method = 'build' . $where['type'] .'filter';
            $filter = $this->{$method}($where);

            if (!$filters) $filters = $filter;

            // Wrap the where with an $or operator.
            if ($where['boolean'] == 'or')
            {
                $filters = $filters->rOr($filter);
            }
            // If there is more wheres, then wrap existing filters with and
            else if ($filters && count($wheres) > 1)
            {
                $filters = $filters->rAnd($filter);
            }
        }

        $this->query->filter($filters, null);
    }

    protected function buildBasicFilter($where)
    {
        $operator = isset($where['operator']) ? $where['operator'] : '=';
        $operator = strtolower($operator);

        // != is same as <>, so just use <>
        if ($operator == '!=') $operator = '<>';

        $column = $where['column'];
        $value = isset($where['value']) ? $where['value'] : null;
        $field = r\row($column);

        switch ($operator)
        {
            case '>':
                return $field->gt($value);
            case '>=':
                return $field->ge($value);
            case '<':
                return $field->lt($value);
            case '<=':
                return $field->le($value);
            case '<>':
                return $field->ne($value);
            case 'contains':
                return $field->contains($value);
            case 'exists':
                $field = $field->rDefault(null);
                return ($value) ? $field : $field->not();
            case 'type':
                return $field->typeOf()->eq(strtoupper($value));
            case 'mod':
                $mod = $field->mod((int) $value[0])->eq((int) $value[1]);
                return $field->typeOf()->eq('NUMBER')->rAnd($mod);
            case 'size':
                $size = $field->count()->eq((int) $value);
                return $field->typeOf()->eq('ARRAY')->rAnd($size);
            case 'regexp':
                $match = $field->match($value);
                return $field->typeOf()->eq('STRING')->rAnd($match);
            case 'not regexp':
                $match = $field->match($value)->not();
                return $field->typeOf()->eq('STRING')->rAnd($match);
            case 'like':
                $regex = str_replace('%', '', $value);
                // Convert like to regular expression.
                if ( ! starts_with($value, '%')) $regex = '^' . $regex;
                if ( ! ends_with($value, '%'))   $regex = $regex . '$';
                $match = $field->match('(?i)'. $regex);
                return $field->typeOf()->eq('STRING')->rAnd($match);
            default:
                return $field->eq($value);
        }
    }

    protected function buildBetweenFilter($where)
    {
        $row = r\row($where['column']);
        $values = $where['values'];

        if ($where['not'])
        {
            $or = $row->ge($values[1]);
            return $row->le($values[0])->rOr($or);
        }
        else
        {
            $and = $row->le($values[1]);
            return $row->ge($values[0])->rAnd($and);
        }
    }

    protected function buildNullFilter($where)
    {
        $where['operator'] = '=';
        $where['value'] = null;
        return $this->buildBasicFilter($where);
    }

    protected function buildNotNullFilter($where)
    {
        return $this->buildNullFilter($where)->not();
    }

    protected function buildInFilter($where)
    {
        $column = $where['column'];
        $values = array_values($where['values']);

        $contains = function($x) use($values, $column) {
            return r\expr($values)->contains($x($column));
        };

        return $contains;
    }

    protected function buildNotInFilter($where)
    {
        $column = $where['column'];
        $values = array_values($where['values']);

        $contains = function($x) use($values, $column) {
            return r\expr($values)->contains($x($column))->not();
        };

        return $contains;
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
        $this->compileWheres();
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
        $this->query->orderBy([$direction]);
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
        $this->compileWheres();
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
        $this->compileWheres();
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
        $this->compileWheres();
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
        $this->compileWheres();
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

        $this->compileWheres();
        $result = $this->query->replace(function($doc) use ($columns) {
            return $doc->without($columns);
        })->run()->toNative();

        return (0 == (int) $result['errors']);
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