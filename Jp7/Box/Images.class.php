<?php

class Jp7_Box_Images extends Jp7_Box_BoxAbstract {
    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	$this->images = array();
		$this->view->headScript()->appendFile('/_default/js/jquery/jquery.jp7.js');
		$this->view->headScript()->appendFile('/_default/js/jquery/jquery.lightbox-0.5.js');
		$this->view->headLink()->appendStylesheet('/_default/js/jquery/themes/jquery.lightbox-0.5.css');
		
		if ($this->view->record) {
			try {
				$this->images = $this->view->record->getImagens(array(
					'fields' => array('*')
				));
			} catch (Exception $e) {
				// Do nothing, method getImagens doesnt exist
			}
		} elseif ($this->view->tipo) {
			if ($imagesTipo = $this->$view->tipo->getFirstChildByModel('Images')) {
				$this->images = $imagesTipo->getInterAdmins(array(
					'fields' => '*'
				));
			}
		}
    }
    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle() {
        return 'Imagens';
    }
}