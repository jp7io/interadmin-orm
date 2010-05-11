<?php
/**
 * 
 * @author JP7
 */
class Jp7_Form {
	
	/**
	 * Creates a Jp7_Mail with the data sent from the form.
	 * 
	 * @param 	InterAdmin 	$record
	 * @param 	string 		$template	Path to the view to be used as a template.
	 * @return Jp7_Mail
	 */
	public function createMail(InterAdmin $record, $options = array()) {
		// Configuração
		$config = Zend_Registry::get('config');
		
		$default = array(
			'template' => 'templates/email.phtml',
			'title' => '',
			'subject' => '',
			'config' => $config
		);
		$options = $options + $default;
		
		// Layout
		$html = $this->prepareHtml($record, $options);
		$text = strip_tags($html);
		$view = Zend_Layout::getMvcInstance()->getView();
		$html = $view->partial($options['template'], $options + array('message' => $html));
		
		// Email
		$mail = new Jp7_Mail();
		$mail->setSubject($options['subject']);
		$mail->setBodyHtml($html);
		$mail->setBodyText($text);
		
		return $mail;
	}
	
	public function prepareHtml(InterAdmin $record, $options = array()) {
		global $lang;
		
		$linebreak = '<br />' . "\r\n";
		
		if ($options['subject']) {
			$html = '<b>' . $options['subject'] . '</b><br />
				<hr size=1 color="#666666"><br />';
		}
		
		foreach ($record->getAttributesCampos() as $type => $field) {
			if (!$field['form']) {
				continue;
			}
			$html .= '<b>' . $this->_getLabel($field) . '</b>: ' . $this->_getValue($record, $field) . $linebreak;
			if ($field['separador']) {
				$html .= $linebreak;			
			}
		}
		
		$html .= '<br />
			<hr size="1" color="#666666">
			<font size="1" color="#333333">
				<b>Idioma:</b> ' . $lang->lang . '<br />
				<b>Data - Hora de Envio:</b> ' . date('d/m/Y - H:i:s') . '<br />
				<b>IP:</b> ' . $_SERVER['REMOTE_ADDR'] . '<br />
				<br />
			</font>';
		return $html;
	}
	
	protected function _getLabel($field) {
		if ($field['label']) {
			return $field['label'];
		} elseif ($field['nome'] instanceof InterAdminTipo) {
			return $field['nome']->getFieldsValues('nome');
		} else {
			return $field['nome'];
		}
	}
	
	protected function _getValue($record, $field) {
		$value = $record->{$field['nome_id']};
		
		// @todo Falta select_multi
		// Relacionamentos
		if ($field['nome'] instanceof InterAdminTipo) {
			$tipo = $field['nome'];
			
			if (is_numeric($value)) {
				$value = $tipo->getInterAdminById($value);
			}
			if ($value instanceof InterAdminAbstract) {
				return $value->getStringValue();
			}
		// Texto
		} elseif (strpos($field['tipo'], 'text_') === 0) {
			$value = '<div style="background:#F2F2F2;margin-top:3px;padding:5px;border:1px solid #CCC">
				<font face="verdana" size="2" color="#000000" style="font-size:13px">' . toHtml($value) . '</font>
			</div>';
			return $value;
		// String
		} else {
			return $value;
		}
		
	}
	
}
