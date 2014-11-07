<?php

class InterAdminOptions {
	private $provider;
	private $options;
	
	public function __construct($provider) {
		$this->provider = $provider;
		$this->options = array(
			'fields' => array(),
			'where' => array()
		);
	}
	
	public function where($_) {
		$where = func_get_args();
		if (count($where) > 1) {
			// Prepared statement: email LIKE ?
			$format = array_shift($where);
			$format = str_replace('?','%s', $format);
			
			$where = array_map([$this, '_escapeParam'], $where);
			array_unshift($where, $format);
			
			$where = array(call_user_func_array('sprintf', $where));
		} else {
			$where = $where[0];
			if (is_array($where)) {
				if (!is_numeric(key($where))) {
					// Hash = [a => 1, b => 2]
					$original = $where;
					$where = array();
					foreach ($original as $key => $value) {
						if (is_array($value)) {
							$escaped = array_map([$this, '_escapeParam'], $value);
							$where[] = "$key IN (" . implode(',', $escaped) . ")";
						} elseif (is_bool($value)) {
							$where[] = "$key " . ($value ? "<> ''" : "= ''");
						} else {
							$where[] = "$key = " . $this->_escapeParam($value);
						}					
					}
				}
			} else {
				$where = array($where);
			}
		}
		$this->options['where'] = array_merge($this->options['where'], $where);
		return $this;  
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
		$this->options['debug'] = (bool)$debug;
		return $this;
	}
	
	public function usePublishedFilters($filters = true) {
		$this->options['use_published_filters'] = (bool) $debug;	
		return $this;
	}
	
	public function getOptionsArray() {
		return $this->options;
	}
	
	public function __call($method_name, $params) {
		$last = count($params) - 1;
		if (is_array($params[$last])) {
			$params[$last] = InterAdmin::mergeOptions($this->options, $params[$last]);
		} else {
			$params[] = $this->options;
		}
		
		$retorno = call_user_method_array($method_name, $this->provider, $params);
		if ($retorno instanceof InterAdminOptions) {
			$this->options = InterAdmin::mergeOptions($this->options, $retorno->getOptionsArray());
			return $this;
		}
		return $retorno;
	}
	
}