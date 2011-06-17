<?php

class Jp7_Model_NewsTipo extends Jp7_Model_TipoAbstract {
	protected static $_children;
	
	public $attributes = array(
		'id_tipo' => 'News',
		'nome' => 'Notícias',
		'campos' => 'varchar_key{,}Título{,}{,}{,}S{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}title{;}varchar_1{,}Subtítulo{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}subtitle{;}text_1{,}Resumo{,}{,}5{,}{,}{,}html_light{,}{,}{,}{,}{,}{,}{,}{,}{,}summary{;}text_2{,}Texto{,}{,}20{,}{,}S{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}text{;}varchar_2{,}Créditos{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}credits{;}file_1{,}Imagem{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}image{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}char_1{,}Destaque{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}featured{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => '',
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
	
	public function __construct() {
		parent::__construct();
		if (!self::$_children) {
			$contentSubitem = $this->_findChildByModel('ContentSubitem');
			$images = $this->_findChildByModel('Images');
			$videos = $this->_findChildByModel('ContentVideos');
			$contentFiles = $this->_findChildByModel('ContentFiles');
			
			self::$_children = $contentSubitem->id_tipo . '{,}Subitens{,}{,}{;}' .
				$images->id_tipo . '{,}Imagens{,}{,}{;}' .
				$videos->id_tipo . '{,}Vídeos{,}{,}{;}' .
				$contentFiles->id_tipo . '{,}Arquivos para Download{,}{,}{;}';
		}
		$this->children = self::$_children;
	}
	
	public function createChildren(InterAdminTipo $tipo) {
		parent::createBoxesSettingsAndIntroduction($tipo);
	}
}