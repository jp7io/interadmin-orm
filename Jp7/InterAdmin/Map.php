<?php

namespace Jp7\Interadmin;

class Map implements \ArrayAccess, \Iterator, \Countable {

	private $values;
	private $keys;

	public function __construct() {
		$this->keys = array();
		$this->values = array();
	}

	public function offsetExists($offset) {
		return in_array($offset, $this->keys, true);
	}

	public function offsetGet($offset) {
		$index = array_search($offset, $this->keys, true);
		if ($index !== false) {
			return $this->values[$index];
		}
		return null;
	}

	public function offsetSet($offset, $value) {
		$index = array_search($offset, $this->keys, true);
		if ($index !== false) {
			$this->values[$index] = $value;
			return;
		}
		$this->keys[] = $offset;
		$this->values[] = $value;
	}

	public function offsetUnset($offset) {
		$index = array_search($offset, $this->keys, true);
		if ($index !== false) {
			unset($this->keys[$index]);
			unset($this->values[$index]);
		}
	}

	public function rewind() {
		reset($this->keys);
		reset($this->values);
 	}

	public function current() {
		return current($this->values);
	}

	public function key() {
		return current($this->keys);
	}

	public function next() {
		next($this->keys);
		return next($this->values);
	}

	public function valid() {
		return $this->current() !== false;
	}	

	public function count() {
		return count($this->keys);
	}
}