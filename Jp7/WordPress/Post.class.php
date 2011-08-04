<?php

class Jp7_WordPress_Post extends Jp7_WordPress_RecordAbstract {
	const PK = 'ID';
	
	public function getUrl() {		
		return $this->guid;
	}
	
}