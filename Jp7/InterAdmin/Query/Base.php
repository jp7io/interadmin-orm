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
	
	public function where($column, $operator = null, $value = null) {
		if (is_array($column)) {
			// Hash = [a => 1, b => 2]
			if (array_key_exists(0, $column)) {
				throw new \InvalidArgumentException("Invalid column.");
			}
			return $this->_whereHash($column);
		}
		if (func_num_args() == 2) {
			list($value, $operator) = array($operator, '=');
		} elseif ($this->invalidOperatorAndValue($operator, $value)) {
			throw new \InvalidArgumentException("Value must be provided.");
		}
		if (!in_array(strtolower($operator), $this->operators, true)) {
			throw new \InvalidArgumentException("Invalid operator.");
		}
		
		$this->options['where'][] = $this->_parseComparison($column, $operator, $value);
		
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
	
	public function whereExists($relationship, $conditions = null, $_not = false) {
		$where = [];
		foreach ($conditions as $key => $value) {
			$where[] = $this->_parseComparison($relationship . '.' . $key, '=', $value);
		}
		
		$this->options['where'][] = ($_not ? 'NOT ' : '') . "EXISTS (" .
			$relationship . " WHERE " . implode(' AND ', $where) . 
		")";
		return $this;
	}
	
	public function whereNotExists($relationship, $conditions = null) {
		return $this->whereExists($relationship, $conditions, true);
	}
	
	protected function _whereHash($hash, $reverse = false) {
		$where = array();
		foreach ($hash as $key => $value) {
			$where[] = $this->_parseComparison($key, '=', $value);
		}
		$this->options['where'] = array_merge($this->options['where'], $where);
		return $this;
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
	
	protected function invalidOperatorAndValue($operator, $value) {
		$isOperator = in_array($operator, $this->operators);

		return ($isOperator && $operator != '=' && is_null($value));
	}
	
	protected function _escapeParam($value) {
		if (is_string($value)) {
			$value = "'" . addslashes($value) . "'";
		}
		if (is_null($value)) {
			$value = 'NULL';
		}
		return $value;
	}
	
	public function fields($_) {
		$fields = is_array($_) ? $_ : func_get_args();
		$this->options['fields'] = array_merge($this->options['fields'], $fields);
		return $this;
	}
	
	public function join($alias, InterAdminTipo $tipo, $on) {
		$this->options['joins'][$alias] = array('INNER', $tipo, $on);
		return $this;
	}
	
	public function leftJoin($alias, InterAdminTipo $tipo, $on) {
		$this->options['joins'][$alias] = array('LEFT', $tipo, $on);
		return $this;
	}
	
	public function rightJoin($alias, InterAdminTipo $tipo, $on) {
		$this->options['joins'][$alias] = array('RIGHT', $tipo, $on);
		return $this;
	}
	
	public function limit($offset, $rows = null) {
		$limit = $offset . (is_null($rows) ? '' : ',' . $rows);
		$this->options['limit'] = $limit;
		return $this;
	}
	
	public function group($group) {
		$this->options['group'] = $group;
		return $this;
	}
	
	public function order($_) {
		$order = func_get_args();
		$this->options['order'] = implode(',', $order);
		return $this;
	}

	public function debug($debug = true) {
		$this->options['debug'] = (bool) $debug;
		return $this;
	}
	
	public function usePublishedFilters($filters = true) {
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