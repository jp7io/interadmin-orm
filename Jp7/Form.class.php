<?php
/**
 * 
 * @author JP7
 */
class Jp7_Form extends Zend_Form {
	/**
     * Constructor
     *
     * Registers form view helper as decorator
     *
     * @param mixed $options
     * @return void
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
		// Adicionado para que campos dentro de Jp7/Form possam ser exibidos
		$this->addPrefixPath('Jp7_Form', 'Jp7/Form/');
    }
	
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
			'message' => '',
			'config' => $config,
			'recipients' => false
		);
		$options = $options + $default;
		
		// Layout
		$view = Zend_Layout::getMvcInstance()->getView();
		// Html
		$options['message'] .= $this->prepareMailHtml($record, $options);;
		$html = $view->partial($options['template'], $options);
		
		// Text Plain
		$text = strip_tags($html);
		
		// Email
		$mail = new Jp7_Mail();
		$mail->setSubject($options['subject']);
		$mail->setBodyHtml($html);
		$mail->setBodyText($text);
		
		// Definindo destinatários somente se recipients estiver setado
		if ($options['recipients'] !== false) {
			if ($config->isProducao()) {
				if (is_array($options['recipients']) && count($options['recipients'])) {
					// Primeiro é To
					$recipient = array_shift($options['recipients']);
					$mail->addTo($recipient->email, $recipient->name);
					// Restante é CC
					foreach ($options['recipients'] as $recipient) {
						$mail->addCc($recipient->email, $recipient->name);
					}
				}
				$mail->addBcc($config->name_id . '@sites.jp7.com.br');
			} else {
				$mail->addTo('debug+' . $config->name_id . '@jp7.com.br');
			}
		}
		
		return $mail;
	}
	
	public function prepareMailHtml(InterAdmin $record, $options = array()) {
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
			$html .= '<b>' . $this->_getMailLabel($field) . '</b>: ' . $this->_getMailValue($record, $field) . $linebreak;
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
	
	/**
	 * FIXME temporário
	 */
	protected function _getMailLabel($field) {
		if ($field['label']) {
			return $field['label'];
		} elseif ($field['nome'] instanceof InterAdminTipo) {
			return $field['nome']->getFieldsValues('nome');
		} else {
			return $field['nome'];
		}
	}
	
	/**
	 * FIXME temporário
	 */
	protected function _getMailValue($record, $field) {
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
	
	/**
	 * Create an array of elements from an InterAdminTipo.
	 * 
	 * @param InterAdminTipo $tipo
	 * @return array
	 */
	public function createElements(array $campos, $prefix = '', array $options = array()) {
		$options = $options + array(
			'label_suffix' => ':',
			'required_suffix' => ''
		);
		
		$elements = array();
		foreach ($campos as $campo) {
			if ($campo['form']) {
				$element = $this->createElementFromCampo($campo, $prefix, $options);
				$elements[$element->getId()] = $element;
			}
		}
		return $elements;
	}
	
	public function createElementFromCampo($campo, $prefix, $options) {
		list($prefixCampo, $suffixCampo) = explode('_', $campo['tipo']);
		
		$name = $prefix . $campo['nome_id'];
		$label_suffix = $options['label_suffix'] . (($campo['obrigatorio']) ? $options['required_suffix'] : ''); 
		
		$options = array(
			'label' => $campo['nome'] . $label_suffix,
			'description' => $campo['ajuda'],
			'required' => (bool) $campo['obrigatorio']
		);
		
		switch ($prefixCampo) {
			case 'varchar':
				$element = $this->createElement('text', $name, $options);
				break;
			case 'text':
				$element = $this->createElement('textarea', $name, $options);
				if ($campo['tamanho']) {
					$element->setOptions(array('rows' => $campo['tamanho']));
				}
				break;
			case 'select':
				$registros = $campo['nome']->getInterAdmins();
				$multiOptions = array(
					'' => '-- selecione --'
				);
				foreach ($registros as $registro) {
					$multiOptions[(string) $registro] = $registro->getStringValue();
				}
				$options['multiOptions'] = $multiOptions;
				// Label não é o $campo['nome'] como nos outros elementos
				$options['label'] = $campo['label'] . $label_suffix;
								
				$element = $this->createElement('select', $name, $options);
				break;
			case 'date':
				$temHora = $campo['xtra'] === 0 || strpos($campo['xtra'], 'datetime') !== false;
				
				if (strpos($campo['xtra'], 'nocombo') === false) {
					$element = $this->createElement('date', $name, $options);
				} else {
					$options['class'] = 'datepicker';
					$element = $this->createElement('datetext', $name, $options);
					//$element->setAttrib('placeholder', 'Dia/Mês/Ano' . ($temHora ? ' 00:00' : ''));
				}
				$element->addValidator(new Zend_Validate_Date('yyyy-MM-dd'));
				break;
			case 'file':
				$element = $this->createElement('file', $name, $options);
				break;
			case 'char':
				$element = $this->createElement('checkbox', $name, $options);
				break;
			default:
				$element = $this->createElement('text', $name, $options);
				break;
		}
		return $element;
	}
	
	public function populate($values, $prefix = '') {
		if ($values instanceof InterAdmin) {
			$values = $values->attributes;
			foreach ($values as $key => $value) {
				if ($value instanceof InterAdmin) {
					$values[$key] = $value->id;
				}
			}
		}
		if ($prefix) {
			foreach ($values as $key => $value) {
				$values[$prefix . $key] = $value;
				unset($values[$key]);
			}
		}
		parent::populate($values);
	}
}


/*
$usuarioTipo = new Ciintranet_UsuarioTipo();
$form = new Jp7_Form();
$elements = $form->createElements($usuarioTipo->getCampos());
$form->addElements($elements);

$form->populate($usuarioLogado->attributes);

$form->setAction('atualizar_ok.php');

// Somente em MVC
echo $form->render();
// Somente fora do ambiente MVC
echo $form->render(new Jp7_Form_View()); 
*/