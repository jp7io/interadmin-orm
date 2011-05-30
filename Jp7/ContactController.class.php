<?php
// Necessário para herdar métodos padrão
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_ContactController extends __Controller_Action {
	
	public function indexAction() {
		$this->view->headScript()->appendFile('/_default/js/jquery/jquery.maskedinput-1.2.2.min.js');
		
		$contactTipo = self::getTipo();
		// Introdução
		if ($introductionTipo = $contactTipo->getFirstChildByModel('Introduction')) {
			$this->view->introductionItens = $introductionTipo->getInterAdmins(array(
				'fields' => '*'
			));
		}
		
		// Formulário
		$record = null;
		
		// Recebeu POST
		if ($this->getRequest()->isPost()) {
			// Salvando registro
			$attributes = @array_map('reset', $_POST);
			
			$record = $contactTipo->createInterAdmin();
			$record->setAttributesSafely($attributes);
			$record->save();
			
			// Utilizado para preparar o email, não tem jeito melhor, por enquanto
			try {
				$this->_sendEmail($record);
				$this->_redirect($contactTipo->getUrl() . '/ok');
			} catch (Exception $e) {
				$this->view->errorMessage = 'Problema ao enviar a mensagem. Por favor, tente novamente.';
			}
		}
		
		// Construindo HTML do form
		$this->view->form = $this->_getFormHtml($contactTipo->getCampos(), $record);
		
	}
	
	protected function _sendEmail($record) {
		$contactTipo = self::getTipo();
		$config = Zend_Registry::get('config');
		
		$recipientsTipo = $contactTipo->getFirstChildByModel('ContactRecipients');
		
		$formHelper = new Jp7_Form();
		$mail = $formHelper->createMail($record, array(
			'subject' => 'Site ' . $config->name . ' - Contato',
			'title' => 'Contato',
			'recipientsTipo' => $recipientsTipo
		));
		$mail->setFrom($record->email, $record->name);
		$mail->send();
	}
	
	protected function _getFormHtml($campos, $record) {
		ob_start();
		foreach ($campos as $campo) {
			if ($campo['form']) {
				// Para usar o alias ao invés do nome do campo
				$campo['tipo_de_campo'] = $campo['tipo'];
				if ($record) {
					$campo['value'] = $record->{$campo['nome_id']};
				} else {
					$campo['value'] = null;
				}
				$campo['tipo'] = $campo['nome_id'];
				
				$field = new InterAdminField($campo);
				echo $field->getHtml();
			}
		}
		return ob_get_clean();
	}
	
	public function okAction() {
		$this->view->title = 'Mensagem enviada com sucesso!';
		$this->view->message = 'Agradecemos o seu contato.<br />Por favor, aguarde nosso retorno em breve.';
	}
}