<?php

class Jp7_Box_Images extends Jp7_Box_BoxAbstract {
    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	$view = Zend_Layout::getMvcInstance()->getView();
			
		$this->images = array();	
    	if ($tipo = $view->tipo) {
			$view->headScript()->appendFile('/_default/js/jquery/jquery.jp7.js');
			$view->headScript()->appendFile('/_default/js/jquery/jquery.lightbox-0.5.js');
			$view->headLink()->appendStylesheet('/_default/js/jquery/themes/jquery.lightbox-0.5.css');
			
			$imagesTipo = $tipo->getFirstChildByModel('Images');
			$this->images = $imagesTipo->getInterAdmins(array(
				'fields' => '*'
			));
		}
    }
    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle() {
        return 'Imagens';
    }
}