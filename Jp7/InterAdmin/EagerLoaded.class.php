<?php

namespace Jp7\Interadmin;

use InterAdminTipo, BadMethodCallException;

class EagerLoaded {
	
	protected $data;
	protected $tipo;
	protected $debug;
	
	public function __construct(InterAdminTipo $tipo, $data) {
		$this->data = $data;
		$this->tipo = $tipo;
	}
	
	public function all() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		if ($this->debug) {
			krumo('Eager loading ' . count($this->data) . ' children records.');
		}
		return $this->data;
	}
	
	public function count() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		if ($this->debug) {
			krumo('Counting eager loaded children records.');
		}
		return count($this->data);
	}
	
	public function first() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		if ($this->debug) {
			krumo('Returning first eager loaded children record.');
		}
		return reset($this->data);
	}
	
	public function debug($debug = true) {
		$this->debug = $debug;
		return $this;
	}
	
	public function __call($method_name, $params) {
		$target = $this->tipo;
		if ($this->debug) {
			$target = $this->tipo->debug();
		}
		return call_user_method_array($method_name, $target, $params);
	}
	
}