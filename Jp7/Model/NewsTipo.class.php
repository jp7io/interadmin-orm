<?php

class Jp7_Model_NewsTipo extends Jp7_Model_TipoAbstract {
	public $attributes = array(
		'id_tipo' => 'News',
		'campos' => 'varchar_key{,}Título{,}{,}{,}S{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}text_1{,}Resumo{,}{,}5{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}file_1{,}Imagem{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}text_2{,}Texto{,}{,}20{,}{,}S{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}varchar_2{,}Créditos{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}char_1{,}Destaque{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => 'Imagens',
		'template' => 'news/index',
		'editpage' => '',
		'class' => '',
		'class_tipo' => '',
		'model_id_tipo' => 0,
		'tabela' => ''
	);
}