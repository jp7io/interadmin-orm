<?php

namespace Jp7\Interadmin;
use InterAdminTipo, InterAdminAbstract, BadMethodCallException;

abstract class BaseOptions {
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
			$where = $this->_wherePreparedStatement($args);
		} else {
			$where = $args[0];
			if (!is_array($where)) {
				$where = array($where);
			} elseif (!is_numeric(key($where))) {
				// Hash = [a => 1, b => 2]
				$where = $this->_whereHash($where);
			}
		}
		$this->options['where'] = array_merge($this->options['where'], $where);
		return $this;  
	}
	
	public function whereNot(array $hash) {
		$where = $this->_whereHash($hash, true);
		
		$this->options['where'] = array_merge($this->options['where'], $where);
		return $this;
	}
	
	protected function _wherePreparedStatement($args) {
		$format = array_shift($args);
		if (strpos($format, '?') === false) {
			throw new BadMethodCallException('Expected a prepared statement such as: "email LIKE ?". Got ' . var_export(func_get_args(), true) . ' instead.');
		}
		$format = str_replace('?', '%s', $format);
		
		$where = array_map([$this, '_escapeParam'], $args);
		
		array_unshift($where, $format);
			
		return array(call_user_func_array('sprintf', $where));
	}
	
	protected function _whereHash($hash, $reverse = false) {
		$where = array();
		foreach ($hash as $key => $value) {
			if (is_array($value)) {
				$escaped = array_map([$this, '_escapeParam'], $value);
				$operator = ($reverse ? 'NOT IN' : 'IN');
				$where[] = "$key $operator (" . implode(',', $escaped) . ")";
			} elseif (is_bool($value) && $this->_isChar($key)) {
				$operator = ($value && !$reverse ? "<>" : "=");
				$where[] = "$key $operator ''";
			} else {
				$operator = ($reverse ? '<>' : '=');
				$where[] = "$key $operator " . $this->_escapeParam($value);
			}
		}
		return $where;
	}
		
	protected function _escapeParam($value) {
		if (is_string($value)) {
			$value = "'" . addslashes($value) . "'";
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