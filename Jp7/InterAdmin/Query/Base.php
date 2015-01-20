<?php

namespace Jp7\Interadmin\Query;
use InterAdminTipo, InterAdminAbstract, BadMethodCallException;

abstract class Base {
	protected $provider;
	protected $options;
	
	public function __construct(InterAdminAbstract $provider) {
		$this->provider = $provider;
		$this->options = array(
			'fields' => array(),
			'where' => array()
		);
	}
	
	public function where($_) {
		$args = func_get_args();
		if (count($args) > 1) {
			// Prepared statement: email LIKE ?
			return $this->_wherePreparedStatement($args);
		}
		$where = $args[0];
		if (is_array($where) && !array_key_exists(0, $where)) {
			// Hash = [a => 1, b => 2]
			return $this->_whereHash($where);
		}
		// normal where
		if (is_scalar($where)) {
			$where = array($where);
		}
		$this->options['where'] = array_merge($this->options['where'], $where);
		return $this;
	}
	
	public function whereNot(array $hash) {
		return $this->_whereHash($hash, true);
	}
	
	protected function _wherePreparedStatement($args) {
		$format = array_shift($args);
		if (strpos($format, '?') === false) {
			throw new BadMethodCallException('Expected a prepared statement such as: "email LIKE ?". Got ' . var_export(func_get_args(), true) . ' instead.');
		}
		$format = str_replace('?', '%s', $format);
		
		$where = array_map([$this, '_escapeParam'], $args);
		
		array_unshift($where, $format);
			
		$where = array(call_user_func_array('sprintf', $where));
		
		$this->options['where'] = array_merge($this->options['where'], $where);
		return $this;
	}
	
	protected function _whereHash($hash, $reverse = false) {
		$where = array();
		foreach ($hash as $key => $value) {
			$where[] = $this->_parseComparison($key, $value, $reverse);
		}
		$this->options['where'] = array_merge($this->options['where'], $where);
		return $this;
	}
	
	protected function _parseComparison($key, $value, $reverse) {
		if (is_array($value)) {
			$escaped = array_map([$this, '_escapeParam'], $value);
			$operator = ($reverse ? 'NOT IN' : 'IN');
			return "$key $operator (" . implode(',', $escaped) . ")";
		}
		if (is_bool($value) && $this->_isChar($key)) {
			$operator = ($value && !$reverse ? "<>" : "=");
			return "$key $operator ''";
		}
		if (is_null($value)) {
			$operator = ($reverse ? 'IS NOT' : 'IS');
		} else {
			$operator = ($reverse ? '<>' : '=');
		}
		return "$key $operator " . $this->_escapeParam($value);		
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