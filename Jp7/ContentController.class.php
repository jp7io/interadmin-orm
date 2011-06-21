<?php
// Necessário para herdar métodos padrão
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_ContentController extends __Controller_Action {
	
	public function indexAction() {
		$id = $this->_getParam('id');
		// Irá cachear uma página diferente para cada registro
		Jp7_Cache_Output::getInstance()->start((string) $id);
		
		$contentTipo = self::getTipo();
		
		if ($id) {
			$this->record = $contentTipo->getInterAdminById($id, array(
				'fields' => array('*')
			));
			if (!$this->record) {
				$this->_redirect($contentTipo->getUrl());
			}
			$this->record->subitens = $this->record->getSubitens(array(
				'fields' => array('*')
			));
			/*
			$this->record->files = $this->record->getArquivosParaDownload(array(
				'fields' => array('name', 'file')
			));
			*/
		} else {
			// Introdução
			if ($introductionTipo = $contentTipo->getFirstChildByModel('Introduction')) {
				$this->view->introductionItens = $introductionTipo->getInterAdmins(array(
					'fields' => '*'
				));
			}
			
			$this->view->records = $contentTipo->getInterAdmins(array(
				'fields' => array('*')
			));
			
			foreach ($this->view->records as $record) {
				if (!$record->text) {
					$record->subitens = $record->getSubitens(array(
						'fields' => array('*')
					));
				} else {
					$record->subitens = array();	
				}
			}
		}
	}
}