<?php

class Jp7_Model_BoxesTipo extends Jp7_Model_TipoAbstract {
	
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
		'tabela' => ''
	);
	
	public function __construct() {
		parent::__construct();
		
		if (!self::$_children) {
			$tipos = InterAdminTipo::findTipos(array(
				'where' => array(
					"model_id_tipo = 'BoxesBox'",
					"admin <> ''"
				)
			));
			
			$child = reset($tipos);
			if (!$child) {
				throw new Exception('Could not find a Tipo using the model "BoxesBox".');
			} else {
				self::$_children = $child->id_tipo . '{,}Boxes{,}{,}{;}';
			}
		}
		$this->children = self::$_children;
	}
}