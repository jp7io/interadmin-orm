<?php

namespace Jp7\Interadmin\Query;

use Illuminate\Database\Query\Expression;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Jp7\Interadmin\Type;
use Jp7\Interadmin\RecordAbstract;
use BadMethodCallException;
use Jp7\Interadmin\Collection;

abstract class BaseQuery
{
    /**
     * @var RecordAbstract
     */
    protected $provider;
    /**
     * @var array
     */
    protected $options;
    protected $or = false;
    protected $prefix = '';

    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'between', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
    ];

    protected $typeChars = [
        'mostrar',
        'language',
        'menu',
        'busca',
        'restrito',
        'admin',
        'editar',
        'unico',
        'versoes',
        'hits',
        'tags',
        'tags_list',
        'tags_tipo',
        'tags_registros',
        'publish_tipo',
        'visualizar',
        'deleted_tipo',
    ];

    protected $initialOptions = [
            'fields' => [],
            'where' => [],
            'having' => [],
            'with' => [],
            'order' => null,
            'group' => null,
            'limit' => null,
            'bindings' => []
        ];

    public function __construct(RecordAbstract $provider)
    {
        $this->provider = $provider;
        $this->options = $this->initialOptions;
    }

    public abstract function count();

    public abstract function build(array $attributes = []);

    public abstract function create(array $attributes = []);

    public abstract function find($id);

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
        if (env('APP_DEBUG') && env('APP_ENV') === 'local') {
            trigger_error('all() is deprecated, use get() instead', E_USER_DEPRECATED);
        }
        return $this->get();
    }

    abstract protected function providerFind($options);

    /**
     * @return RecordAbstract|null
     */
    public function first()
    {
        if (func_num_args() > 0) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 0.');
        }

        return $this->providerFind(['limit' => 1] + $this->options)->first();
    }

    public function firstOrFail()
    {
        $result = $this->first();
        if (!$result) {
            throw new ModelNotFoundException('Unable to find first record.');
        }
        return $result;
    }

    /**
     * Find a model by its primary key or return fresh model instance.
     *
     * @param  mixed  $id
     * @return RecordAbstract
     */
    public function findOrNew($id)
    {
        $instance = $this->find($id);
        if ($instance) {
            return $instance;
        }
        return $this->build();
    }


    /**
     * Get the first record matching the attributes or instantiate it.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return RecordAbstract
     */
    public function firstOrNew(array $attributes, array $values = [])
    {
        $instance = $this->where($attributes)->first();
        if ($instance) {
            return $instance;
        }
        return $this->build($attributes + $values);
    }

    /**
     * Get the first record matching the attributes or create it.
     *
     * @param  array  $attributes
     * @param  array  $values
     * @return RecordAbstract
     */
    public function firstOrCreate(array $attributes, array $values = [])
    {
        $instance = $this->where($attributes)->first();
        if ($instance) {
            return $instance;
        }
        return $this->create($attributes + $values);
    }

    /**
     * Get a single column's value from the first result of a query.
     *
     * @param  string  $column
     * @return mixed
     */
    public function value($column)
    {
        $result = $this->select($column)->first();
        if ($result) {
            return $result->{$column};
        }
    }

    /**
     * @return Collection
     */
    public function get()
    {
        if (func_num_args() > 0) {
            throw new BadMethodCallException('Wrong number of arguments, received '.func_num_args().', expected 0.');
        }

        return $this->providerFind($this->options);
    }

    public function pluck($column, $key = null)
    {
        $array = $this->providerFind([
                'fields' => array_filter([$column, $key]),
            ] + $this->options);

        return jp7_collect(array_pluck($array, $column, $key));
    }

    /**
     * List to be used on json, with {key: 1, value: 'Lorem'}.
     */
    public function jsonList($column, $key)
    {
        $items = $this->providerFind([
                'fields' => array_filter([$column, $key]),
            ] + $this->options);

        return $items->jsonList($column, $key);
    }

    /**
     * Set deleted = 'S' and update the records.
     *
     * @return int
     */
    public function delete()
    {
        $records = $this->get();
        foreach ($records as $record) {
            $record->delete();
        }
        return count($records);
    }

    /**
     * Remove permanently from the database.
     */
    public function forceDelete()
    {
        $records = $this->get();
        foreach ($records as $record) {
            $record->forceDelete();
        }
        return count($records);
    }

    public function restore()
    {
        $records = $this->get();
        foreach ($records as $record) {
            $record->restore();
        }
        return count($records);
    }

    /**
     * @deprecated use pluck() instead
     */
    public function lists($column, $key = null)
    {
        return $this->pluck($column, $key);
    }

    /**
     * @return static
     */
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
                $where = ($last ? $last.' OR ' : '').$where;
            }
            $this->options['where'][] = $where;
        }

        return $this;
    }

    public function whereRaw($where)
    {
        return $this->_addWhere($where);
    }

    /**
     * @return static
     */
    public function whereIn($column, $values, $_not = false)
    {
        if ($values instanceof Arrayable) {
            $values = $values->toArray();
        }
        $values = array_map([$this, '_escapeParam'], $values);
        if ($values) {
            $where = $this->prefix.$column.($_not ? ' NOT' : '').' IN ('.implode(',', $values).')';
        } else {
            $where = ($_not ? '1' : '0').' = 1';
        }
        return $this->_addWhere($where);
    }

    public function whereFindInSet($column, $value)
    {
        $value = $this->_escapeParam($value);
        $where = 'FIND_IN_SET ('.$value.', '.$this->prefix.$column.')';

        return $this->_addWhere($where);
    }

    public function whereNotIn($column, $values)
    {
        return $this->whereIn($column, $values, true);
    }

    public function has($relationship)
    {
        return $this->whereHas($relationship, '1 = 1');
    }

    public function doesntHave($relationship)
    {
        return $this->whereDoesntHave($relationship, '1 = 1');
    }
    
    public function whereHas($relationship, $conditions = null, $_not = false)
    {
        try {
            $type = $this->provider->getRelationshipData($relationship)['tipo'];
        } catch (\InvalidArgumentException $e) {
            if ($relationship !== 'tags') {
                throw $e;
            }
            // Temporario para tags
            $type = $this->provider;
        }

        $relWhere = $this->_parseConditions($conditions, $type, $relationship);

        $where = ($_not ? 'NOT ' : '').'EXISTS ('.
            $relationship.' WHERE '.implode(' AND ', $relWhere).
        ')';

        return $this->_addWhere($where);
    }

    public function whereYear($column, $operator, $value = null)
    {
        $where = $this->_parseComparison('YEAR('.$this->prefix.$column.')', $operator, $value);

        return $this->_addWhere($where);
    }

    public function whereMonth($column, $operator, $value = null)
    {
        $where =  $this->_parseComparison('MONTH('.$this->prefix.$column.')', $operator, $value);

        return $this->_addWhere($where);
    }

    public function whereDay($column, $operator, $value = null)
    {
        $where =  $this->_parseComparison('DAY('.$this->prefix.$column.')', $operator, $value);

        return $this->_addWhere($where);
    }

    public function whereDate($column, $operator, $value = null)
    {
        $where =  $this->_parseComparison('DATE('.$this->prefix.$column.')', $operator, $value);

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
            $innerQuery->_addWhere($innerQuery->_whereHash($conditions));
            return $innerQuery->getOptionsArray()['where'];
        } elseif ($conditions instanceof \Closure) {
            return [$innerQuery->_whereClosure($conditions)]; 
        }
        throw new \InvalidArgumentException('Invalid conditions.');
    }

    protected function _parseComparison($column, $operator, $value)
    {
        if (!in_array(strtolower($operator), $this->operators, true)) {
            if (is_null($value)) {
                // short circuit operator
                list($operator, $value) = ['=', $operator];
            } else {
                throw new \InvalidArgumentException('Invalid operator: '.$operator);
            }
        }
        if (is_bool($value) && $this->_isChar($column)) {
            if ($operator !== '=') {
                if (in_array($operator , ['<>', '!='])) {
                    $operator = '=';
                    $value = !$value;
                } else {
                    throw new \InvalidArgumentException('Invalid operator for boolean: '.$operator);
                }
            }
            $operator = ($value ? '<>' : '=');
            $value = '';
        } elseif (is_null($value) && in_array($operator, ['=', '<>', '!='])) {
            $operator = 'IS' . ($operator === '=' ? '' : ' NOT');
        }

        return $this->prefix.$column.' '.$operator.' '.$this->_escapeParam($value);
    }

    protected function _isChar($field)
    {
        if (str_contains($field, '.')) {
            list($relationship, $field) = explode('.', $field);

            if (isset($this->options['joins'][$relationship])) {
                $joinType = $this->options['joins'][$relationship][1];
                $data = [
                    'tipo' => $joinType,
                    'has_type' => $joinType === Type::class
                ];
            } else {
                $data = $this->provider->getRelationshipData($relationship);
            }
            if ($data['has_type']) {
                return in_array($field, $this->typeChars);
            }
            $type = $data['tipo'];
        } elseif ($this instanceof TypeQuery) {
            return in_array($field, $this->typeChars);
        } elseif (in_array($field, ['deleted', 'publish'])) {
            return true;
        } else {
            $type = $this->provider;
        }

        $aliases = array_flip($type->getCamposAlias());
        if (isset($aliases[$field])) {
            return strpos($aliases[$field], 'char_') === 0;
        } else {
            return strpos($field, 'char_') === 0;
        }
    }

    protected function _resolveType($var)
    {
        if (is_string($var)) {
            if ($var === Type::class) {
                return $var;
            }
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

        return is_null($value) && $isOperator && !in_array($operator, ['=', '<>', '!=']);
    }

    protected function _escapeParam($value)
    {
        if (is_object($value)) {
            if ($value instanceof Expression) {
                return $value;
            }
            $value = $value->__toString();
        }
        if (is_string($value) && !ctype_digit($value)) {
            $binding = ':val'.count($this->options['bindings']);
            $this->options['bindings'][$binding] = $value;
            $value = $binding;
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
     * @param bool                  $_typeless  Whether to filter by 'id_tipo' or not
     *
     * @return static
     */
    public function join($alias, $className, $conditions, $_joinType = 'INNER', $_typeless = false)
    {
        $type = $this->_resolveType($className);
        $joinOn = $this->_parseConditions($conditions, $type, $alias)[0];
        $this->options['joins'][$alias] = [$_joinType, $type, $joinOn, $_typeless];

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

    public function typelessJoin($alias, $className, $conditions)
    {
        return $this->join($alias, $className, $conditions, 'INNER', true);
    }

    public function typelessLeftJoin($alias, $className, $conditions)
    {
        return $this->join($alias, $className, $conditions, 'LEFT', true);
    }

    public function typelessRightJoin($alias, $className, $conditions)
    {
        return $this->join($alias, $className, $conditions, 'RIGHT', true);
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

    public function havingRaw($sql)
    {
        $this->options['having'][] = $sql;

        return $this;
    }

    /**
     * @return static
     */
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

    public function getOptionsArray($replaceBinding = true)
    {
        $array = $this->options;
        if ($replaceBinding && $this->options['bindings']) { 
            foreach ($array['where'] as &$where) {
                $where = RecordAbstract::replaceBindings($array['bindings'], $where);
            }
            foreach ($array['joins'] ?? [] as &$join) {
                $join = RecordAbstract::replaceBindings($array['bindings'], $join);
            }
            unset($array['bindings']);
        }
        return $array;
    }

    public function with($_)
    {
        $this->options['with'] = array_merge($this->options['with'], func_get_args());

        return $this;
    }

    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page')
    {
        $totalItems = $this->count();

        $page = LengthAwarePaginator::resolveCurrentPage($pageName) ?: 1;

        $items = $this->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return new LengthAwarePaginator($items->all(), $totalItems, $perPage, $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }

    /**
     * Chunk the results of the query.
     *
     * @param  int  $count
     * @param  callable  $callback
     * @return bool
     */
    public function chunk($count, callable $callback)
    {
        $page = 1;
        do {
            $results = $this->forPage($page, $count)->get();
            $countResults = $results->count();
            if ($countResults == 0) {
                break;
            }
            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            if ($callback($results) === false) {
                return false;
            }
            $page++;
        } while ($countResults == $count);
        return true;
    }

    /**
     * Set the limit and offset for a given page.
     *
     * @param  int  $page
     * @param  int  $perPage
     * @return static
     */
    public function forPage($page, $perPage = 15)
    {
        return $this->skip(($page - 1) * $perPage)->take($perPage);
    }
}
