<?php

class Jp7_Tag_Table extends Jp7_Tag_Container {

	public function tr($attrs = array()) {
		$tr = new Jp7_Tag_Tr($attrs);
		$this->add($tr);
		return $tr;
	}
	
	public function rotate() {
		$trs = $this->getItems();
		
		$out = array();
		foreach ($trs as $col_i => $row) {
			foreach ($row->getItems() as $row_i => $cell) {
				if (!$out[$row_i]) {
					$out[$row_i] = new Jp7_Tag_Tr();
				}
				$out[$row_i]->add($cell);
			}
		}
		
		$this->setItems($out);
	}
}