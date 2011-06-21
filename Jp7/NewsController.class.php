<?php
// Necessário para herdar métodos padrão
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_NewsController extends __Controller_Action {
	
	public function indexAction() {
		$id = $this->_getParam('id');
		// Irá cachear uma página diferente para cada registro
		Jp7_Cache_Output::getInstance()->start((string) $id);
		
		$newsTipo = self::getTipo();
		
		if ($id) {
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
			$pagination = new Pagination(array(
				'records' => $newsTipo->getInterAdminsCount(),
				'next_char' => 'Próxima',
				'back_char' => 'Anterior',
				'show_first_and_last' => true 
			));
			
			$this->view->introductionItens = array();
			if ($pagination->page == 1) {
				// Introdução na primeira página
				if ($introductionTipo = $newsTipo->getFirstChildByModel('Introduction')) {
					$this->view->introductionItens = $introductionTipo->getInterAdmins(array(
						'fields' => '*'
					));
				}
			}
			
			$this->view->news = $newsTipo->getInterAdmins(array(
				'fields' => array('*', 'date_publish'),
				'limit'=> $pagination
			));
			$this->view->pagination = $pagination;
		}
	}
}