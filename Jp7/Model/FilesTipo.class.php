<?php

class Jp7_Model_FilesTipo extends Jp7_Model_TipoAbstract {
	
	public $attributes = array(
		'id_tipo' => 'Files',
		'nome' => 'Arquivos para Download',
		'campos' => 'varchar_key{,}Nome{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}name{;}file_1{,}Arquivo{,}{,}{,}S{,}S{,}trigger{,}S{,}{,}{,}{,}{,}{,}{,}{,}file{;}int_key{,}Ordem{,}{,}{,}{,}S{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}char_1{,}Destaque{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}featured{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => '',
		'template' => 'files/index',
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
	
	public function getEditorFields(Jp7_Box_BoxAbstract $box) {
		ob_start();
		?>
		<div class="fields">
			<?php echo parent::_getEditorImageFields($box); ?>
		</div>
		<?php
		return ob_get_clean();
	}
	
	public function prepareData(Jp7_Box_BoxAbstract $box) {
		parent::_prepareImageData($box);
	}
}