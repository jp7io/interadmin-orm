<?php
class Jp7_Session_Facade implements ArrayAccess {
	private $_data = array();
	public function __construct($session) {
		trigger_error('Experimental - do not use', E_USER_WARNING);
		$this->_data = $session;
		session_write_close();
	}
	public function offsetSet($offset, $value) {
		if (is_null($offset)) {
			$this->_data[] = $value;
		} else {
			$this->_data[$offset] = $value;
		}
		$this->syncData();
	}
	public function offsetExists($offset) {
		return isset($this->_data[$offset]);
	}
	public function offsetUnset($offset) {
		unset($this->_data[$offset]);
		$this->syncData();
	}
	public function offsetGet($offset) {
		return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
	}
	private function syncData() {
		session_start();
		$_SESSION = $this->_data;
		session_write_close();
		$_SESSION = $this;
	}
}