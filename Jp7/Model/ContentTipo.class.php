<?php

class Jp7_Model_ContentTipo extends Jp7_Model_TipoAbstract {
	public $attributes = array(
		'id_tipo' => 'Content',
		'campos' => 'varchar_key{,}Título{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}titulo{;}varchar_1{,}Subtítulo{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}subtitulo{;}text_1{,}Resumo{,}{,}3{,}{,}{,}html_light{,}{,}{,}{,}{,}{,}{,}{,}{,}resumo{;}text_2{,}Texto{,}{,}20{,}{,}S{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}texto{;}file_1{,}Imagem{,}{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}imagem{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => '',
		'template' => 'content/index',
		'editpage' => '',
		'class' => '',
		'class_tipo' => '',
		'model_id_tipo' => 0,
		'tabela' => '',
		'layout' => 4,
		'layout_registros' => 4
	);
}