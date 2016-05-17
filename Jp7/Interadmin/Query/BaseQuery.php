<?php

namespace Jp7\Interadmin\Query;

use Illuminate\Database\Query\Expression;
use Illuminate\Pagination\LengthAwarePaginator;
use Jp7\Interadmin\Type;
use Jp7\Interadmin\RecordAbstract;
use BadMethodCallException;

abstract class BaseQuery
{
    protected $provider;
    protected $options;
    protected $or = false;
    protected $prefix = '';

    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'between', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
    ];

    public function __construct(RecordAbstract $provider)
    {
        $this->provider = $provider;
        $this->options = [
            'fields' => [],
            'where' => [],
            'order' => null,
            'group' => null,
            'limit' => null,
        ];
    }

    public function __call($method_name, $params)
    {
        if (starts_with($method_name, 'or')) {
            $original = lcfirst(substr($method_name, 2));
            if (method_exists($this, $original)) {
                $this->or = true;

                return call_user_func_array([$this, $original], $params);
            }
        }
        throw new BadMethodCallException('Unsupported method '.$method_name);
    }

    /**
     * @deprecated use get() instead.
     * @return Collection
     */
    public function all()
    {
        if (env('APP_DEBUG')) {
            trigger_error('all() is deprecated, use get() instead', E_USER_DEPRECATED);
        }
        return $this->get();
    }

    public function where($column, $operator = null, $value = null)
    {
        if (is_array($column)) {
            // Hash = [a => 1, b => 2]
            $where = $this->_whereHash($column);
        } elseif ($column instanceof \Closure) {
            $where = $this->_whereClosure($column);
        } else {
            if (func_num_args() == 2) {
                list($value, $operator) = [$operator, '='];
            } elseif ($this->invalidOperatorAndValue($operator, $value)) {
                throw new \InvalidArgumentException('Value must be provided.');
            }
            if (!in_array(strtolower($operator), $this->operators, true)) {
                if (is_null($value)) {
                    // short circuit operator
                    list($operator, $value) = ['=', $operator];
                } else {
                    throw new \InvalidArgumentException('Invalid operator.');
                }
            }
            if (str_contains($column, ' ') || str_contains($column, '(')) {
                throw new BadMethodCallException('Invalid column.');
            }
            $where = $this->_parseComparison($column, $operator, $value);
        }

        return $this->_addWhere($where);
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        $this->or = true;

        return $this->where($column, $operator, $value);
    }

    protected function _addWhere($where)
    {
        if ($where) {
            if ($this->or) {
                $this->or = false;

                $last = array_pop($this->options['where']);
                $where = $last.' OR '.$where;
            }
            $this->options['where'][] = $where;
        }

        return $this;
    }

    public function whereRaw($where)
    {
        return $this->_addWhere($where);
    }

    public function whereIn($column, $values)
    {
        $values = array_map([$this, '_escapeParam'], $values);
        $where = $column.' IN ('.implode(',', $values).')';

        return $this->_addWhere($where);
    }

    public function whereFindInSet($column, $value)
    {
        $value = $this->_escapeParam($value);
        $where = 'FIND_IN_SET ('.$value.', '.$column.')';

        return $this->_addWhere($where);
    }

    public function whereNotIn($column, $values)
    {
        $values = array_map([$this, '_escapeParam'], $values);
        $where = $column.' NOT IN ('.implode(',', $values).')';

        return $this->_addWhere($where);
    }

    public function has($relationship)
    {
        return $this->whereHas($relationship, '1 = 1');
    }

    public function whereHas($relationship, $conditions = null, $_not = false)
    {
        try {
            $type = $this->provider->getRelationshipData($relationship)['tipo'];
        } catch (\InvalidArgumentException $e) {
            // Temporario para tags
            $type = $this->provider;
        }

        $relWhere = $this->_parseConditions($conditions, $type, $relationship);

        $where = ($_not ? 'NOT ' : '').'EXISTS ('.
            $relationship.' WHERE '.implode(' AND ', $relWhere).
        ')';

        return $this->_addWhere($where);
    }

    public function whereYear($column, $value)
    {
        $where = $this->_parseComparison('YEAR('.$column.')', '=', $value);

        return $this->_addWhere($where);
    }

    public function whereMonth($column, $value)
    {
        $where =  $this->_parseComparison('MONTH('.$column.')', '=', $value);

        return $this->_addWhere($where);
    }

    public function whereDay($column, $value)
    {
        $where =  $this->_parseComparison('DAY('.$column.')', '=', $value);

        return $this->_addWhere($where);
    }

    public function whereDoesntHave($relationship, $conditions = null)
    {
        return $this->whereHas($relationship, $conditions, true);
    }

    protected function _whereHash($hash, $reverse = false)
    {
        if (array_key_exists(0, $hash)) {
            throw new \InvalidArgumentException('Invalid column.');
        }
        $where = [];
        foreach ($hash as $key => $value) {
            $where[] = $this->_parseComparison($key, '=', $value);
        }
        if ($where) {
            return '('.implode(' AND ', $where).')';
        }
    }

    protected function _whereClosure($closure)
    {
        $innerQuery = new static($this->provider);
        $innerQuery->prefix = $this->prefix;
        $closure($innerQuery);

        if ($where = $innerQuery->getOptionsArray()['where']) {
            return '('.implode(' AND ', $where).')';
        }
    }

    protected function _parseConditions($conditions, $type, $relationship)
    {
        if (is_string($conditions)) {
            return [$conditions]; // TODO remove support
        }

        $innerQuery = new static($type);
        $innerQuery->prefix = $relationship.'.';

        if (is_array($conditions)) {
            return [$innerQuery->_whereHash($conditions)];
        } elseif ($conditions instanceof \Closure) {
            return [$innerQuery->_whereClosure($conditions)];
        }
        throw new \InvalidArgumentException('Invalid conditions.');
    }

    protected function _parseComparison($column, $operator, $value)
    {
        if (is_bool($value) && $this->_isChar($column)) {
            if ($operator != '=') {
                throw new \InvalidArgumentException('Invalid operator.');
            }
            $operator = ($value ? '<>' : '=');
            $value = '';
        } elseif (is_null($value) && $operator == '=') {
            $operator = 'IS';
        }

        return $this->prefix.$column.' '.$operator.' '.$this->_escapeParam($value);
    }

    protected function _resolveType($var)
    {
        if (is_string($var)) {
            return call_user_func([$var, 'type']);
        }
        if ($var instanceof Type) {
            return $var;
        }
        throw new BadMethodCallException('Expected class name or Type, got: '.gettype($var));
    }

    protected function invalidOperatorAndValue($operator, $value)
    {
        $isOperator = in_array($operator, $this->operators);

        return ($isOperator && $operator != '=' && is_null($value));
    }

    protected function _escapeParam($value)
    {
        if (is_object($value)) {
            if ($value instanceof Expression) {
                return $value;
            }
            $value = $value->__toString();
        }
        if (is_string($value)) {
            $value = \DB::connection()->getPdo()->quote($value);
        }
        if (is_null($value)) {
            $value = 'NULL';
        }

        return $value;
    }

    public function select($_)
    {
        $fields = is_array($_) ? $_ : func_get_args();
        $this->options['fields'] = array_merge($this->options['fields'], $fields);

        return $this;
    }

    /**
     * Aplica join na query.
     *
     * @param string                $alias
     * @param string|Type           $className
     * @param array                 $conditions
     * @param string                $_joinType  ex.: INNER, LEFT, RIGHT
     *
     * @return \Jp7\Interadmin\Query\BaseQuery
     */
    public function join($alias, $className, $conditions, $_joinType = 'INNER')
    {
        $type = $this->_resolveType($className);
        $joinOn = $this->_parseConditions($conditions, $type, $alias)[0];
        $this->options['joins'][$alias] = [$_joinType, $type, $joinOn];

        return $this;
    }

    public function leftJoin($alias, $className, $conditions)
    {
        return $this->join($alias, $className, $conditions, 'LEFT');
    }

    public function rightJoin($alias, $className, $conditions)
    {
        return $this->join($alias, $className, $conditions, 'RIGHT');
    }

    public function skip($offset)
    {
        if (!is_numeric($offset)) {
            throw new BadMethodCallException('Offset must be numeric.');
        }
        $this->options['skip'] = $offset;

        return $this;
    }

    public function take($limit)
    {
        if (!is_numeric($limit)) {
            throw new BadMethodCallException('Limit must be numeric.');
        }
        $this->options['limit'] = $limit;

        return $this;
    }

    public function limit($limit)
    {
        return $this->take($limit);
    }

    /**
     * Determine if any rows exist for the current query.
     *
     * @return bool
     */
    public function exists()
    {
        $limit = $this->options['limit'];

        $result = $this->limit(1)->count() > 0;

        $this->options['limit'] = $limit;

        return $result;
    }

    public function groupBy($column)
    {
        if (str_contains($column, ' ') || str_contains($column, '(')) {
            throw new BadMethodCallException('Invalid column.');
        }
        $this->options['group'] = implode(',', array_filter([$this->options['group'], $column]));

        return $this;
    }

    public function groupByRaw($group)
    {
        $this->options['group'] = implode(',', array_filter([$this->options['group'], $group]));

        return $this;
    }

    public function orderBy($column, $direction = 'asc')
    {
        if (str_contains($column, ' ') || str_contains($column, '(')) {
            throw new BadMethodCallException('Use orderByRaw instead.');
        }
        $order = $column.' '.$direction;

        return $this->orderByRaw($order);
    }

    public function orderByRaw($order)
    {
        $this->options['order'] = implode(',', array_filter([$this->options['order'], $order]));

        return $this;
    }

    public function debug($debug = true)
    {
        $this->options['debug'] = (bool) $debug;

        return $this;
    }

    public function published($filters = true)
    {
        $this->options['use_published_filters'] = (bool) $filters;

        return $this;
    }

    public function getOptionsArray()
    {
        return $this->options;
    }

    public function with($_)
    {
        $this->options['with'] = func_get_args();

        return $this;
    }

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page')
    {
        $totalItems = $this->count();

        $page = LengthAwarePaginator::resolveCurrentPage($pageName) ?: 1;
        
        $items = $this->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->all();

        return new LengthAwarePaginator($items->all(), $totalItems, $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }
}
