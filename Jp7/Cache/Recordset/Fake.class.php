<?php

class Jp7_Cache_Recordset_Fake {
	public $rows;
	
	public function __construct($rows) {
		$this->rows = $rows;
	}
	
	public function FetchNextObj() {
		return array_shift($this->rows);
	}
	
	public function Close() {
		unset($this->rows);	
	}
}