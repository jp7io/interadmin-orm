<?php

class Jp7_Model_NewsTipo extends Jp7_Model_TipoAbstract {
	public $attributes = array(
		'id_tipo' => 'News',
		'campos' => 'varchar_key{,}Título{,}{,}{,}S{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}text_1{,}Resumo{,}{,}5{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}file_1{,}Imagem{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}char_3{,}Sem Borda{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}varchar_1{,}Link{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}text_2{,}Texto{,}{,}20{,}{,}S{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}varchar_2{,}Créditos{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}char_1{,}Destaque{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}char_2{,}Destaque Plus{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}',
		'children' => '7{,}Sub-Itens{,}{,}{;}',
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