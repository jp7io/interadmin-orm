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
	
	public function all($options = array()) {
		return $this->provider->find(InterAdmin::mergeOptions($options, $this->options));
	}
	
	public function first($options = array()) {
		return $this->provider->findFirst(InterAdmin::mergeOptions($options, $this->options));
	}
}