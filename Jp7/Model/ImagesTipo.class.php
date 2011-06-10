<?php

class Jp7_Model_ImagesTipo extends Jp7_Model_TipoAbstract {
	public $hasOwnPage = false;
	public $isSubTipo = true;
	
	public $attributes = array(
		'id_tipo' => 'Images',
		'nome' => 'Imagens',
		'campos' => 'varchar_key{,}Nome{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}name{;}file_1{,}Arquivo{,}{,}{,}S{,}S{,}trigger{,}S{,}{,}{,}{,}{,}{,}{,}{,}file{;}int_key{,}Ordem{,}{,}{,}{,}S{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}char_1{,}Destaque{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}featured{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => '',
		'template' => '',
		'editpage' => '',
		'class' => '',
		'class_tipo' => '',
		'model_id_tipo' => 0,
		'tabela' => '',
		'editar' => 'S'
	);
}