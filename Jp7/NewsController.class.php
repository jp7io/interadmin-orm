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
			$this->view->record = $this->record;
		} else {
			$news = $newsTipo->getInterAdmins(array(
				'fields' => array('titulo', 'date_publish')
			));
				
			$this->view->news = $news;
		}
	}
}