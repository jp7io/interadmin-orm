<?php

class Jp7_Model_ContactReceivedTipo extends Jp7_Model_TipoAbstract {
	
	public $attributes = array(
		'id_tipo' => 'ContactReceived',
		'nome' => 'Contato - Mensagens Recebidas',
		'campos' => 'varchar_key{,}Nome{,}{,}{,}S{,}{,}0{,}{,}{,}{,}{,}S{,}{,}{,}{,}name{;}varchar_1{,}E-mail{,}E-mail no formato: nome@dominio.com.br{,}{,}S{,}{,}email{,}S{,}{,}{,}{,}S{,}{,}{,}{,}email{;}varchar_3{,}Telefone{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}S{,}{,}{,}{,}telephone{;}varchar_2{,}Assunto{,}{,}{,}S{,}{,}0{,}S{,}{,}{,}{,}S{,}{,}{,}{,}subject{;}text_1{,}Mensagem{,}{,}10{,}S{,}S{,}0{,}{,}{,}{,}{,}S{,}{,}{,}{,}message{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => '',
		'template' => '',
		'editpage' => '',
		'class' => '',
		'class_tipo' => '',
		'model_id_tipo' => 0,
		'tabela' => ''
	);
	
}