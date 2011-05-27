<?php

class Jp7_Model_NewsTipo extends Jp7_Model_TipoAbstract {
	public $attributes = array(
		'id_tipo' => 'News',
		'nome' => 'Notícias',
		'campos' => 'varchar_key{,}Título{,}{,}{,}S{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}title{;}text_1{,}Resumo{,}{,}5{,}{,}S{,}html_light{,}{,}{,}{,}{,}{,}{,}{,}{,}summary{;}file_1{,}Imagem{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}image{;}text_2{,}Texto{,}{,}20{,}{,}S{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}text{;}varchar_2{,}Créditos{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}credits{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}char_1{,}Destaque{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}featured{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => 'Imagens',
		'template' => 'news/index',
		'editpage' => '',
		'class' => '',
		'class_tipo' => '',
		'model_id_tipo' => 0,
		'tabela' => '',
		'layout' => Jp7_Box_Manager::COL_2_LEFT,
		'layout_registros' => Jp7_Box_Manager::COL_2_LEFT,
		'editar' => 'S'
	);
	
	public function createChildren(InterAdminTipo $tipo) {
		parent::createBoxesSettingsAndIntroduction($tipo);
	}
}