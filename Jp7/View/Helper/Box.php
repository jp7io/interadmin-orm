<?php

class Jp7_View_Helper_Box extends Zend_View_Helper_Abstract {
	
	public function Box($id_box, $params = array()) {
		$classe = Jp7_Box_Manager::get($id_box);
		if (!$classe) {
			throw new Exception('Unknown box: ' . $id_box);
		}
		
		$fakeRecord = new InterAdmin();
		$fakeRecord->id_box = $id_box;
		$fakeRecord->params = $params;
		
		$box = new $classe($fakeRecord);
		if (!$box instanceof Jp7_Box_BoxAbstract) {
			throw new Exception('Expected an instance of Jp7_Box_BoxAbstract, received a ' . get_class($box) . '.');
		}
		$box->prepareData();
		
		return $this->view->partial('boxes/' . $id_box . '.phtml', $box);
	}
}
