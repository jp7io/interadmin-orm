<?php

abstract class Jp7_WordPress_RecordAbstract extends Jp7_WordPress_BaseAbstract {
	public function __construct($db) {
		$this->_db = $db;
	}
}