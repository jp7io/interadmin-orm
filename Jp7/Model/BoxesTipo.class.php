<?php

class Jp7_Model_BoxesTipo extends Jp7_Model_TipoAbstract {
	public $isSubTipo = true;
	
	protected static $_children;
	
	public $attributes = array(
		'id_tipo' => 'Boxes',
		'nome' => 'Boxes',
		'campos' => 'int_1{,}Largura em Colunas{,}{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}1{,}width{;}char_1{,}Página dos Registros{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}records_page{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => '',
		'template' => '',
		'editpage' => 'boxes.php',
		'class' => '',
		'class_tipo' => '',
		'model_id_tipo' => 0,
		'tabela' => '',
		'icone' => 'layout_content'
	);
	
	public function __construct() {
		parent::__construct();
		
		if (!self::$_children) {
			$boxesBox = $this->_findChildByModel('BoxesBox');
			self::$_children = $boxesBox->id_tipo . '{,}Boxes{,}{,}{;}';
		}
		$this->children = self::$_children;
	}
}