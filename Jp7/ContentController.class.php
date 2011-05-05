<?php
// Necessário para herdar métodos padrão
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_ContentController extends __Controller_Action {
	
	public function indexAction() {
		$contentTipo = self::getTipo();
		
		if ($id = $this->_getParam('id')) {
			$this->record = $contentTipo->getInterAdminById($id, array(
				'fields' => array('*', 'date_publish')
			));
			if (!$this->record) {
				$this->_redirect($contentTipo->getUrl());
			}
			$this->record->subitens = $this->record->getSubItens(array(
				'fields' => array('*', 'date_publish')
			));
		} else {
			$this->view->records = $contentTipo->getInterAdmins(array(
				'fields' => array('*', 'date_publish')
			));
		}
	}
}