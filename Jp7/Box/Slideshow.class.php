<?php

class Jp7_Box_Slideshow extends Jp7_Box_BoxAbstract {
   
	public function prepareData() {
		$this->items = array();
		if ($tipo = $this->view->tipo) {
			if ($slideshowTipo = $tipo->getFirstChildByModel('Slideshow')) {
				$this->view->headScript()->appendFile('/_default/js/jquery/jquery.jp7.js');
				
				$this->items = $slideshowTipo->getInterAdmins(array(
					'fields' => '*'
				));
			}
		}
	}
}