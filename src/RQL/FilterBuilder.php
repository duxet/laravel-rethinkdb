<?php

namespace duxet\Rethinkdb\RQL;

use r;

class FilterBuilder
{
    public function __construct($document)
    {
        $this->document = $document;
    }

    public function compileWheres($wheres)
    {
        $chain = null;

        foreach ($wheres as $i => &$where) {
            $method = 'build'.$where['type'].'filter';
            $filter = self::{$method}
            ($where);

            if (!$chain) {
                $chain = $filter;
            }

            // Wrap the where with an $or operator.
            if ($where['boolean'] == 'or') {
                $chain = $chain->rOr($filter);
            } // If there are more wheres, then wrap existing filters with and
            elseif ($chain && count($wheres) > 1) {
                $chain = $chain->rAnd($filter);
            }
        }

        return $chain;
    }

    protected function buildBasicFilter($where)
    {
        $operator = isset($where['operator']) ? $where['operator'] : '=';
        $operator = strtolower($operator);

        // != is same as <>, so just use <>
        if ($operator == '!=') {
            $operator = '<>';
        }

        $value = isset($where['value']) ? $where['value'] : null;
        $field = $this->getField($where['column']);

        switch ($operator) {
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
                if (!starts_with($value, '%')) {
                    $regex = '^'.$regex;
                }
                if (!ends_with($value, '%')) {
                    $regex = $regex.'$';
                }
                $match = $field->match('(?i)'.$regex);

                return $field->typeOf()->eq('STRING')->rAnd($match);
            default:
                return $field->eq($value);
        }
    }

    protected function buildBetweenFilter($where)
    {
        $row = $this->getField($where['column']);
        $values = $where['values'];
        if ($where['not']) {
            $or = $row->ge($values[1]);

            return $row->le($values[0])->rOr($or);
        } else {
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
        $field = $this->getField($where['column']);
        $values = array_values($where['values']);

        return r\expr($values)->contains($field);
    }

    protected function buildNotInFilter($where)
    {
        return $this->buildInFilter($where)->not();
    }

    protected function buildNestedFilter($where)
    {
        return $where['query']->buildFilter($this->document);
    }

    protected function getField($name)
    {
        $document = $this->document;

        return $document($name);
    }
}
