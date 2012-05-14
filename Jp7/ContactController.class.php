<?php
// Necessário para herdar métodos padrão
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_ContactController extends __Controller_Action {
	
	public function indexAction() {
		include_once ROOT_PATH . '/inc/7.form.lib.php';
		
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
			try {
				$record = $this->_createRecord($contactTipo);
				$this->_validateAndSave($record);
				
				// Utilizado para preparar o email, não tem jeito melhor, por enquanto
				try {
					$this->_sendEmail($record);
					$this->_redirect($contactTipo->getUrl() . '/ok');
				} catch (Exception $e2) {
					throw new Exception('Problema ao enviar a mensagem. Por favor, tente novamente.');
				}
			} catch (Exception $e) {
				// Permite customizar mensagem de erro
				$this->view->errorMessage = $e->getMessage();
			}
		}
		
		// Construindo HTML do form
		$this->view->form = $this->_getFormHtml($contactTipo->getCampos(), $record);
		
	}
	
	protected function _validateAndSave($record) {
		foreach ($record->getTipo()->getCampos() as $campo) {
			$this->_validateCampo($record, $campo);
		}
		$record->save();
	}
	
	protected function _validateCampo($record, $campo) {
		if (!$campo['form']) {
			return;
		}
		// Validação do campo obrigatório
		if ($campo['obrigatorio']) {
			if (!startsWith('char_', $campo['tipo'])) {
				if (!$record->{$campo['nome_id']}) {
					$label = startsWith('select_', $campo['tipo']) ? $campo['label'] : $campo['nome'];
					throw new Exception('Favor preencher campo ' . $label . '.');
				}
			}
		}
		// Validação e-mail
		if (startsWith('varchar_', $campo['tipo']) && $campo['xtra'] == 'email') {
			if (!filter_var($record->{$campo['nome_id']}, FILTER_VALIDATE_EMAIL)) {
				throw new Exception('Valor inválido do campo ' . $campo['nome'] . '.');
			}
		}
	}
	
	protected function _createRecord() {
		$contactTipo = self::getTipo();
		$attributes = @array_map('reset', $_POST);
			
		$record = $contactTipo->createInterAdmin();
		$record->setAttributesSafely($attributes);
		
		return $record;
	}
	
	protected function _sendEmail($record, $sendReply = true) {
		$contactTipo = self::getTipo();
		$contactTipo->getFieldsValues('nome');
		$config = Zend_Registry::get('config');
		
		$recipients = $this->_getRecipients($contactTipo, $record);
		
		$formHelper = new Jp7_Form();
		// E-mail normal para os destinatários do site
		$mail = $formHelper->createMail($record, array(
			'subject' => 'Site ' . $config->name . ' - ' . $contactTipo->nome,
			'title' => $contactTipo->nome,
			'recipients' => $recipients
		));
		$mail->setFrom($record->email, $record->name);
		$mail->send();
		
		if ($sendReply) {
			// E-mail de resposta para o usuário
			$reply = $formHelper->createMail($record, array(
				'subject' => 'Confirmação de Recebimento - ' . $config->name . ' - ' . $contactTipo->nome,
				'title' => $contactTipo->nome,
				'recipients' => array($record), // Envia para o próprio usuário
				'message' => 
					'Agradecemos o seu contato.<br />' .
					'Por favor, aguarde nosso retorno em breve.<br /><br />',
			));
			$reply->setFrom($config->admin_email, $config->admin_name);
			$reply->send();
		}
	}
	
	protected function _getRecipients($contactTipo, $record) {
		$recipientsTipo = $contactTipo->getFirstChildByModel('ContactRecipients');
		return $recipientsTipo->getInterAdmins(array(
			'fields' => array('name', 'email')
		));
	}
	
	protected function _getFormHtml($campos, $record) {
		$translate = Zend_Registry::get('Zend_Translate');
		
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
				// Não prevê special
				if (strpos($campo['tipo_de_campo'], 'select_') === 0) {
					$campo['label'] = $translate->_($campo['label']);
				} else {
					$campo['nome'] = $translate->_($campo['nome']);
				}
				
				// Só para CHAR - checkbox
				if (startsWith('char_', $campo['tipo_de_campo'])) {
					if (!$record->id && $campo['xtra']) {
						$campo['value'] = 'S';
					}
					global $j;
					$form = jp7_db_checkbox($campo['tipo'] . "[".$j."]","S", $campo['tipo'], $campo['readonly'], "", ($campo['value']) ? $campo['value'] : null);
					?>
					<tr>
						<th></th>
						<td colspan="2" class="checkbox-container">
							<?php echo $form; ?><span><?php echo $campo['nome']; ?></span>
						</td>
						<td></td>
					</tr>
					<?php
				// OUTROS CAMPOS
				} else {
					$field = new InterAdminField($campo);
					echo $field->getHtml();
				}
			}
		}
		return ob_get_clean();
	}
	
	public function okAction() {
		$this->view->title = 'Mensagem enviada com sucesso!';
		$this->view->message = 'Agradecemos o seu contato.<br />Por favor, aguarde nosso retorno em breve.';
	}
}