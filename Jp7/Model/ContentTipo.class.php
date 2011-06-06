<?php

class Jp7_Model_ContentTipo extends Jp7_Model_TipoAbstract {	protected static $_children;
	
	public $attributes = array(
		'id_tipo' => 'Content',
		'nome' => 'Conteúdo',
		'campos' => 'varchar_key{,}Título{,}{,}{,}{,}{,}0{,}{,}2{,}{,}{,}{,}{,}{,}{,}title{;}varchar_1{,}Subtítulo{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}subtitle{;}text_1{,}Resumo{,}{,}3{,}{,}{,}html_light{,}{,}{,}{,}{,}{,}{,}{,}{,}summary{;}text_2{,}Texto{,}{,}20{,}{,}S{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}text{;}file_1{,}Imagem{,}{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}image{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}char_1{,}Destaque{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}featured{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => '',
		'template' => 'content/index',
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
			self::$_children = $contentSubitem->id_tipo . '{,}Sub-itens{,}{,}{;}' .
				$images->id_tipo . '{,}Imagens{,}{,}{;}';
		}		
		$this->children = self::$_children;
	}
	
	public function createChildren(InterAdminTipo $tipo) {
		parent::createBoxesSettingsAndIntroduction($tipo);
	}
	
	public function getEditorFields(Jp7_Box_BoxAbstract $box) {
		ob_start();
		?>
		<div class="fields">
			<div class="group">
				<div class="group-label">Imagens</div>
				<div class="group-fields">
					<div class="field">
						<label>Dimensões:</label>
						<?php echo $box->numericField('imgWidth', 'Largura', '80'); ?> x
						<?php echo $box->numericField('imgHeight', 'Altura', '60'); ?> px
					</div>
					<div class="field">
						<label title="Se estiver marcado irá recortar a imagem nas dimensões exatas que foram informadas.">Recortar:</label>
						<?php echo $box->checkbox('imgCrop', true); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
	
	public function prepareData(Jp7_Box_BoxAbstract $box) {
		$imgHeight = $box->params->imgHeight ? $box->params->imgHeight : 60;
		$imgWidth = $box->params->imgWidth ? $box->params->imgWidth : 80;
		
		$box->view->imgSize = $imgWidth . 'x' . $imgHeight;
		$box->view->imgCrop = isset($box->params->imgCrop) ? $box->params->imgCrop : true;
		
		$box->view->headStyle()->appendStyle('
.content-content .img-wrapper {
	height: ' . $imgHeight . 'px;
	width: ' . $imgWidth . 'px;
	line-height: ' . $imgHeight . 'px;
}
.content-content .img-wrapper img {
	max-height: ' . $imgHeight . 'px;
	max-width: ' . $imgWidth . 'px;
}
		');
	}
}