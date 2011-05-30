<?php
// Necessário para herdar métodos padrão
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_OfficesController extends __Controller_Action {
	
	public function indexAction() {
		$officesTipo = self::getTipo();
		
		$this->view->headScript()->appendFile('http://maps.google.com/maps/api/js?sensor=true');
		$this->view->headScript()->appendFile('/_default/js/jquery/jquery.jp7.js');
		
		if ($id = $this->_getParam('id')) {
			$this->record = $officesTipo->getInterAdminById($id, array(
				'fields' => array('*', 'state' => array('sigla'))
			));
			if (!$this->record) {
				$this->_redirect($officesTipo->getUrl());
			}
		} else {
			// Introdução
			if ($introductionTipo = $officesTipo->getFirstChildByModel('Introduction')) {
				$this->view->introductionItens = $introductionTipo->getInterAdmins(array(
					'fields' => '*'
				));
			}
			
			$this->view->records = $officesTipo->getInterAdmins(array(
				'fields' => array('*', 'state' => array('sigla'))
			));
		}
	}
}