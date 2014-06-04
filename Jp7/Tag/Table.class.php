<?php

class Jp7_Tag_Table extends Jp7_Tag_Container {

	public function tr($attrs = array()) {
		$tr = new Jp7_Tag_Tr($attrs);
		$this->add($tr);
		return $tr;
	}
}