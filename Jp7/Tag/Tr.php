<?php

class Jp7_Tag_Tr extends Jp7_Tag_Container {
	public function td($value, $attrs = array()) {
		$td = new Jp7_Tag_Td($value, $attrs);
		$this->add($td);
		return $td;
	}
	public function th($value, $attrs = array()) {
		$th = new Jp7_Tag_Th($value, $attrs);
		$this->add($th);
		return $th;
	}
}