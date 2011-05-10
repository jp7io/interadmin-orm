<?php

class Jp7_Model_ContactTipo extends Jp7_Model_TipoAbstract {
	
	public $attributes = array(
		'id_tipo' => 'Contact',
		'nome' => 'Contato',
		'campos' => 'varchar_key{,}Título{,}Límite máximo de 50 caracteres{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}title{;}varchar_1{,}Subtítulo{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}subtitle{;}file_1{,}Imagem{,}{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}image{;}text_1{,}Texto{,}{,}10{,}{,}S{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}text{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}',
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
		'layout_registros' => 4
	);
	
}