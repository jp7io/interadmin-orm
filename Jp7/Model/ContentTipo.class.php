<?php

class Jp7_Model_ContentTipo extends Jp7_Model_TipoAbstract {
	
	protected static $_children;
	
	public $attributes = array(
		'id_tipo' => 'Content',
		'nome' => 'Conteúdo',
		'campos' => 'varchar_key{,}Título{,}{,}{,}{,}{,}0{,}{,}2{,}{,}{,}{,}{,}{,}{,}title{;}varchar_1{,}Subtítulo{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}subtitle{;}text_1{,}Resumo{,}{,}3{,}{,}{,}html_light{,}{,}{,}{,}{,}{,}{,}{,}{,}summary{;}text_2{,}Texto{,}{,}20{,}{,}S{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}text{;}file_1{,}Imagem{,}{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}image{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}',
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
	
	public function __construct() {
		parent::__construct();
		if (!self::$_children) {
			$contentSubitem = $this->_findChildByModel('ContentSubitem');
			$images = $this->_findChildByModel('Images');
			self::$_children = $contentSubitem->id_tipo . '{,}Sub-itens{,}{,}{;}' .
				$images->id_tipo . '{,}Imagens{,}{,}{;}';
		}		
		$this->children = self::$_children;
	}
}