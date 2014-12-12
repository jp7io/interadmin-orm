<?php
// Necessário para herdar métodos padrão
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_FilesController extends __Controller_Action {
	
	public function indexAction() {
		Jp7_Cache_Output::getInstance()->start();
		
		$filesTipo = self::getTipo();
		
		$this->view->records = $filesTipo->find(array(
			'fields' => array('name', 'file')
		));
			
		// Introdução
		if ($introductionTipo = $filesTipo->getFirstChildByModel('Introduction')) {
			$this->view->introductionItens = $introductionTipo->find(array(
				'fields' => '*'
			));
		}
	}
}