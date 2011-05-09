<?php

class Jp7_Box_Slideshow extends Jp7_Box_BoxAbstract {
   
	public function prepareData() {
		$view = Zend_Layout::getMvcInstance()->getView();
		
		$this->items = array();
		if ($tipo = $view->tipo) {
			if ($slideshowTipo = $tipo->getFirstChildByModel('Slideshow')) {
				$view->headScript()->appendFile('/_default/js/jquery/jquery.jp7.js');
				
				$this->items = $slideshowTipo->getInterAdmins(array(
					'fields' => '*'
				));
			}
		}
	}
}