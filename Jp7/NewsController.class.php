<?php
// Necessário para herdar métodos padrão
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_NewsController extends __Controller_Action {
	
	public function indexAction() {
		$newsTipo = self::getTipo();
		
		if ($id = $this->_getParam('id')) {
			$this->record = $newsTipo->getInterAdminById($id,array(
				'fields' => array('*', 'date_publish')
			));
			if (!$this->record) {
				$this->_redirect($newsTipo->getUrl());
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
			if ($introductionTipo = $newsTipo->getFirstChildByModel('Introduction')) {
				$this->view->introductionItens = $introductionTipo->getInterAdmins(array(
					'fields' => '*'
				));
			}
			
			$this->view->news = $newsTipo->getInterAdmins(array(
				'fields' => array('*', 'date_publish')
			));
		}
	}
}