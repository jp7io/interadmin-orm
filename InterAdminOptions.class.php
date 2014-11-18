<?php

class InterAdminOptions {
	private $tipo;
	private $options;
	
	public function __construct(InterAdminTipo $tipo) {
		$this->tipo = $tipo;
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
	
	protected function _isChar($field) {
		$aliases = array_flip($this->tipo->getCamposAlias());
		if (isset($aliases[$field])) {
			return strpos($aliases[$field], 'char_') === 0;
		} else {
			return strpos($field, 'char_') === 0;
		}
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
	
	public function join($alias, $tipo, $on) {
		$this->options['joins'][$alias] = array('INNER', $tipo, $on);
		return $this;
	}
	
	public function leftJoin($alias, $tipo, $on) {
		$this->options['joins'][$alias] = array('LEFT', $tipo, $on);
		return $this;
	}
	
	public function rightJoin($alias, $tipo, $on) {
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
	
	public function all() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		return $this->tipo->find($this->options);
	}
	
	public function first() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		return $this->tipo->findFirst($this->options);
	}
	
	public function count() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		return $this->tipo->count($this->options);
	}
	
	public function find($id) {
		if (func_num_args() != 1) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 1.');
		if (!is_string($id) && !is_int($id)) {
			throw new BadMethodCallException('Wrong argument on find(). If you´re trying to get records, use all() instead of find().');
		}
		return $this->tipo->find($id, $this->options);
	}
	
	public function findFirst() {
		throw new BadMethodCallException('Use first() instead of findFirst().');
	}
	
	public function __call($method_name, $params) {
		$last = count($params) - 1;
		if (is_array($params[$last])) {
			$params[$last] = InterAdmin::mergeOptions($this->options, $params[$last]);
		} else {
			$params[] = $this->options;
		}
		
		$retorno = call_user_method_array($method_name, $this->tipo, $params);
		if ($retorno instanceof InterAdminOptions) {
			$this->options = InterAdmin::mergeOptions($this->options, $retorno->getOptionsArray());
			return $this;
		}
		return $retorno;
	}
	
}