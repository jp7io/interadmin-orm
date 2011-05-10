<?php
// Necessário para herdar métodos padrão
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_ContactController extends __Controller_Action {
	
	public function indexAction() {
		$this->view->headScript()->appendFile('/_default/js/jquery/jquery.maskedinput-1.2.2.min.js');
		
		$contactTipo = self::getTipo();
		$this->view->records = $contactTipo->getInterAdmins(array(
			'fields' => '*'
		));
		$this->view->form = '';
		
		if ($formTipo = $contactTipo->getFirstChildByModel('ContactReceived')) {
			$record = null;
			
			// recebeu POST
			if ($this->getRequest()->isPost()) {
				// Salvando registro
				$attributes = @array_map('reset', $_POST);
				
				$record = $formTipo->createInterAdmin();
				$record->setAttributesSafely($attributes);
				$record->save();
				
				// Utilizado para preparar o email, não tem jeito melhor, por enquanto
				try {
					$this->_sendEmail($record);
					$this->_redirect($contactTipo->getUrl() . '/ok');
				} catch (Exception $e) {
					$this->_redirect($contactTipo->getUrl() . '/ok?error=1');
				}
				// Fim do fluxo
			}
			
			// Construindo HTML do form
			$this->view->form = $this->_getFormHtml($formTipo, $record);
		}
	}
	
	protected function _sendEmail($record) {
		$contactTipo = self::getTipo();
		$config = Zend_Registry::get('config');
		
		$formHelper = new Jp7_Form();
		$mail = $formHelper->createMail($record, array(
			'subject' => 'Site ' . $config->name . ' - Contato',
			'title' => 'Contato'
		));
		$mail->setFrom($record->email, $record->name);
		
		// Definindo destinatários
		if ($config->isProducao()) {
			$recipientTipo = $contactTipo->getFirstChildByModel('ContactRecipients');
			$recipients = $recipientTipo->getInterAdmins(array(
				'fields' => array('name', 'email')
			));
			if ($recipients) {
				// Primeiro é To
				$recipient = array_shift($recipients);
				$mail->addTo($recipient->email, $recipient->name);
				// Restante é CC
				foreach ($recipients as $recipient) {
					$mail->addCc($recipient->email, $recipient->name);
				}
			}
			$mail->addBcc($config->name_id . '@sites.jp7.com.br');
		} else {
			$mail->addTo('debug+' . $config->name_id . '@jp7.com.br');
		}
		
		$mail->send();
	}
	
	protected function _getFormHtml($formTipo, $record) {
		ob_start();
		foreach ($formTipo->getCampos() as $campo) {
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
		if ($this->_getParam('error')) {
			$this->view->title = 'Problema ao enviar a mensagem.';
			$this->view->message = 'Por favor, tente mais tarde.';
		} else {
			$this->view->title = 'Mensagem enviada com sucesso!';
			$this->view->message = 'Agradecemos o seu contato.<br />Por favor, aguarde nosso retorno em breve.';
		}
	}
}