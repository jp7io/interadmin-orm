<?php

abstract class Jp7_Box_BoxAbstract {
	public $record;
	
	public function __construct(InterAdmin $record) {
		$this->record = $record;
		$this->record->params = unserialize($this->record->params);
	}
	
	public function prepareData() {
		
	}
	
}