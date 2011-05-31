<?php

class Jp7_Model_SettingsTipo extends Jp7_Model_TipoAbstract {
	public $isSubTipo = true;
	
	public $attributes = array(
		'id_tipo' => 'Settings',
		'nome' => 'Configurações',
		'campos' => 'tit_1{,}Metatags{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}varchar_key{,}Title{,}Límite máximo de 50 caracteres{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}title{;}varchar_1{,}Description{,}Límite máximo de 150 caracteres{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}description{;}varchar_2{,}Keywords{,}Límite máximo de 80 caracteres{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}keywords{;}char_1{,}Sobrescrever Keywords{,}Se marcado, não manterá keywords padrão.{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}overwrite_keywords{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => '',
		'template' => '',
		'editpage' => '',
		'class' => '',
		'class_tipo' => '',
		'model_id_tipo' => 0,
		'tabela' => '',
		'unico' => 'S'
	);
	
}