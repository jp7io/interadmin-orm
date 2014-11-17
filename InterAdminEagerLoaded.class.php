<?php

class InterAdminEagerLoaded {
	
	protected $data;
	protected $tipo;
	
	public function __construct(InterAdminTipo $tipo, $data) {
		$this->data = $data;
		$this->tipo = $tipo;
	}
	
	public function all() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		return $this->data;
	}
	
	public function count() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		return count($this->data);
	}
	
	public function first() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		return reset($this->data);
	}
	
	public function __call($method_name, $params) {
		return call_user_method_array($method_name, $this->tipo, $params);
	}
	
}