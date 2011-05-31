<?php

class Jp7_Model_ContactTipo extends Jp7_Model_TipoAbstract {
	public $attributes = array(
		'id_tipo' => 'Contact',
		'nome' => 'Contato',
		'campos' => 'varchar_key{,}Nome{,}{,}{,}S{,}{,}0{,}{,}{,}{,}{,}S{,}{,}{,}{,}name{;}varchar_1{,}E-mail{,}E-mail no formato: nome@dominio.com.br{,}{,}S{,}{,}email{,}S{,}{,}{,}{,}S{,}{,}{,}{,}email{;}varchar_3{,}Telefone{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}S{,}{,}{,}{,}telephone{;}varchar_2{,}Assunto{,}{,}{,}S{,}{,}0{,}S{,}{,}{,}{,}S{,}{,}{,}{,}subject{;}text_1{,}Mensagem{,}{,}10{,}S{,}S{,}0{,}{,}{,}{,}{,}S{,}{,}{,}{,}message{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => '',
		'template' => 'contact/$action',
		'editpage' => '',
		'class' => '',
		'class_tipo' => '',
		'model_id_tipo' => 0,
		'tabela' => '',
		'layout' => 4,
		'layout_registros' => 4,
		'texto' => 'Contém as mensagens recebidas através do formulário de contato.' 
	);
	
	public function createChildren(InterAdminTipo $tipo) {
		parent::createBoxesSettingsAndIntroduction($tipo);
		
		if (!$tipo->getFirstChildByModel('ContactRecipients')) {
			$recipients = $tipo->createChild('ContactRecipients');
			$recipients->nome = 'Destinatários';
			$recipients->ordem = -25;
	        $recipients->save();
		}
	}
}