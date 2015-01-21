<?php

namespace Jp7\Interadmin;
use BadMethodCallException;

class TypelessQuery extends Query {

	/**
	 * @return InterAdmin[]
	 */
	public function all() {
		if (func_num_args() > 0) throw new BadMethodCallException('Wrong number of arguments, received ' . func_num_args() . ', expected 0.');
		return $this->provider->deprecatedTypelessFind($this->options);
	}
	
	public function first() {
		return $this->provider->deprecatedTypelessFind($this->options + array('limit' => 1))->first();
	}
	
	public function count() {
		throw new BadMethodCallException('Not implemented.');
	}
	
	public function find($id) {
		throw new BadMethodCallException('Not implemented.');
	}
	
	public function findOrFail($id) {
		throw new BadMethodCallException('Not implemented.');
	}
	
}