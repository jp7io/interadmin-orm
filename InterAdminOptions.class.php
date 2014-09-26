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
	
	public function where($where) {
		if (!is_array($where)) {
			$where = array($where);
		}
		$this->options['where'] = array_merge($this->options['where'], $where);
		return $this;  
	}
		
	public function fields($_) {
		$fields = func_get_args();
		$this->options['fields'] = array_merge($this->options['fields'], $fields);
		return $this;
	}
	
	public function limit($limit) {
		$this->options['limit'] = $limit;
		return $this;
	}
	
	public function group($group) {
		$this->options['group'] = $group;
		return $this;
	}
	
	public function order($order) {
		$this->options['order'] = $order;
		return $this;
	}
	
	public function __call($method_name, $params) {
		$last = count($params) - 1;
		if (is_array($params[$last])) {
			$params[$last] = InterAdmin::mergeOptions($this->options, $params[$last]);
		} else {
			$params[] = $this->options;
		}
		
		return call_user_method_array($method_name, $this->provider, $params);
	}
	
}