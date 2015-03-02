<?php

namespace Jp7\Interadmin\Query;
use InterAdminTipo, InterAdminAbstract, BadMethodCallException;

abstract class Base {
	protected $provider;
	protected $options;
	
	protected $operators = array(
		'=', '<', '>', '<=', '>=', '<>', '!=',
		'like', 'not like', 'between', 'ilike',
		'&', '|', '^', '<<', '>>',
		'rlike', 'regexp', 'not regexp',
	);
	
	public function __construct(InterAdminAbstract $provider) {
		$this->provider = $provider;
		$this->options = array(
			'fields' => array(),
			'where' => array()
		);
	}
	
	public function where($column, $operator = null, $value = null, $_or = false) {
		if (is_array($column)) {
			// Hash = [a => 1, b => 2]
			$where = $this->_whereHash($column);
		} elseif ($column instanceof \Closure) {
			$where = $this->_whereClosure($column);
		} else {
			if (func_num_args() == 2) {
				list($value, $operator) = array($operator, '=');
			} elseif ($this->invalidOperatorAndValue($operator, $value)) {
				throw new \InvalidArgumentException("Value must be provided.");
			}
			if (!in_array(strtolower($operator), $this->operators, true)) {
				if (is_null($value)) {
					// short circuit operator
					list($operator, $value) = ['=', $operator];
				} else {
					throw new \InvalidArgumentException("Invalid operator.");
				}
			}
			$where = $this->_parseComparison($column, $operator, $value);
		}
		$this->_addWhere($where, $_or);
		return $this;
	}

	protected function _addWhere($where, $or) {
		if ($where) {
			if ($or) {
				$last = array_pop($this->options['where']);
				$where = $last . ' OR ' . $where;
			}
			$this->options['where'][] = $where;
		}
	}

	public function orWhere($column, $operator = null, $value = null) {
		return $this->where($column, $operator, $value, true);
	}

	public function whereRaw($where) {
		$this->options['where'][] = $where;
		return $this;
	}
	
	public function whereIn($column, $values) {
		$values = array_map([$this, '_escapeParam'], $values);
		$this->options['where'][] = $column . ' IN (' . implode(',', $values) . ')';
		return $this;
	}
	
	public function whereFindInSet($column, $value) {
		$value = $this->_escapeParam($value);
		$this->options['where'][] = ' FIND_IN_SET (' . $value . ', ' . $column . ')';
		return $this;
	}
	
	public function whereNotIn($column, $values) {
		$values = array_map([$this, '_escapeParam'], $values);
		$this->options['where'][] = $column . ' NOT IN (' . implode(',', $values) . ')';
		return $this;
	}
	
	public function has($relationship) {
		return $this->whereHas($relationship, '1 = 1');
	}

	public function whereHas($relationship, $conditions = null, $_not = false) {
		$where = $this->_parseConditions($conditions, $relationship . '.');
		
		$this->options['where'][] = ($_not ? 'NOT ' : '') . "EXISTS (" .
			$relationship . " WHERE " . implode(' AND ', $where) . 
		")";
		return $this;
	}
	
	public function whereDoesntHave($relationship, $conditions = null) {
		return $this->whereHas($relationship, $conditions, true);
	}
	
	protected function _whereHash($hash, $reverse = false) {
		if (array_key_exists(0, $hash)) {
			throw new \InvalidArgumentException("Invalid column.");
		}
		$where = array();
		foreach ($hash as $key => $value) {
			$where[] = $this->_parseComparison($key, '=', $value);
		}
		if ($where) {
			return '(' . implode(' AND ', $where) . ')';
		}
	}

	protected function _whereClosure($closure) {
		$innerQuery = new static($this->provider);
		$closure($innerQuery);

		if ($where = $innerQuery->getOptionsArray()['where']) {
			return '(' . implode(' AND ', $where) . ')';
		}
	}
	
	protected function _parseConditions($conditions, $prefix = '') {
		$where = [];
		if (is_string($conditions)) {
			$where[] = $conditions;
		} else {
			foreach ($conditions as $key => $value) {
				$where[] = $this->_parseComparison($prefix . $key, '=', $value);
			}
		}
		return $where;
	}
	
	protected function _parseComparison($column, $operator, $value) {
		if (is_bool($value) && $this->_isChar($column)) {
			if ($operator != '=') {
				throw new \InvalidArgumentException("Invalid operator.");
			}
			$operator = ($value ? '<>' : '=');
			$value = '';
		} elseif (is_null($value) && $operator == '=') {
			$operator = 'IS';
		}
		return $column . ' ' . $operator . ' ' . $this->_escapeParam($value);		
	}
	
	protected function _resolveType($var) {
		if (is_string($var)) {
			return call_user_func([$var, 'type']);
		}
		if ($var instanceof InterAdminTipo) {
			return $var;
		}
		throw new BadMethodCallException('Expected class name or InterAdminTipo, got: ' . gettype($var));
	}
	
	protected function invalidOperatorAndValue($operator, $value) {
		$isOperator = in_array($operator, $this->operators);

		return ($isOperator && $operator != '=' && is_null($value));
	}
	
	protected function _escapeParam($value) {
		if (is_object($value)) {
			$value = $value->__toString();
		}
		if (is_string($value)) {
			$value = "'" . addslashes($value) . "'";
		}
		if (is_null($value)) {
			$value = 'NULL';
		}
		return $value;
	}
	
	public function select($_) {
		$fields = is_array($_) ? $_ : func_get_args();
		$this->options['fields'] = array_merge($this->options['fields'], $fields);
		return $this;
	}
	
	public function join($alias, $className, $conditions, $_joinType = 'INNER') {
		$type = $this->_resolveType($className);
		$joinOn = $this->_parseConditions($conditions, $alias . '.')[0];
		$this->options['joins'][$alias] = array($_joinType, $type, $joinOn);
		return $this;
	}
	
	public function leftJoin($alias, $className, $conditions) {
		return $this->join($alias, $className, $conditions, 'LEFT');
	}
	
	public function rightJoin($alias, $className, $conditions) {
		return $this->join($alias, $className, $conditions, 'RIGHT');
	}
	
	public function skip($offset) {
		if (!is_numeric($offset)) {
			throw new BadMethodCallException('Offset must be numeric.');
		}
		$this->options['skip'] = $offset;
		return $this;
	}

	public function take($limit) {
		return $this->limit($limit);
	}

	public function limit($limit) {
		if (!is_numeric($limit)) {
			throw new BadMethodCallException('Limit must be numeric.');
		}
		$this->options['limit'] = $limit;
		return $this;
	}
	
	public function groupBy($column) {
		if (str_contains($column, ' ') || str_contains($column, '(')) {
			throw new BadMethodCallException('Invalid column.');
		}
		if (!isset($this->options['group'])) {
			$this->options['group'] = null;
		}
		$this->options['group'] = implode(',', array_filter([$this->options['group'], $column]));
		return $this;
	}
	
	public function orderBy($column, $direction = 'asc') {
		if (str_contains($column, ' ') || str_contains($column, '(')) {
			throw new BadMethodCallException('Use orderByRaw instead.');
		}
		$order = $column . ' ' . $direction;
		return $this->orderByRaw($order);
	}

	public function orderByRaw($order) {
		if (!isset($this->options['order'])) {
			$this->options['order'] = null;
		}
		$this->options['order'] = implode(',', array_filter([$this->options['order'], $order]));
		return $this;
	}

	public function debug($debug = true) {
		$this->options['debug'] = (bool) $debug;
		return $this;
	}
	
	public function published($filters = true) {
		$this->options['use_published_filters'] = (bool) $filters;	
		return $this;
	}
	
	public function getOptionsArray() {
		return $this->options;
	}
	
	public function with($_) {
		$this->options['with'] = func_get_args();
		return $this;
	}	
}