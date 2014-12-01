<?php

namespace Jp7\Interadmin;

class Options extends BaseOptions {
	
	protected function _isChar($field) {
		$aliases = array_flip($this->tipo->getCamposAlias());
		if (isset($aliases[$field])) {
			return strpos($aliases[$field], 'char_') === 0;
		} else {
			return strpos($field, 'char_') === 0;
		}
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
		if ($retorno instanceof self) {
			$this->options = InterAdmin::mergeOptions($this->options, $retorno->getOptionsArray());
			return $this;
		}
		throw new Exception('Unsupported method ' . $method_name);
		return $retorno;
	}
	
}