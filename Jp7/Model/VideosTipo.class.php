<?php

class Jp7_Model_VideosTipo extends Jp7_Model_TipoAbstract {
	
	public $attributes = array(
		'id_tipo' => 'Videos',
		'nome' => 'Vídeos',
		'campos' => 'varchar_key{,}Título{,}{,}{,}S{,}S{,}0{,}{,}2{,}{,}{,}{,}{,}{,}{,}title{;}varchar_1{,}Vídeo{,}Endereço do vídeo no YouTube ou Vimeo. Ex: http://www.youtube.com/watch?v=123ab456{,}{,}S{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}video{;}file_1{,}Thumb{,}Caso não seja cadastrada, será usada a imagem do YouTube para preview do vídeo.{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}thumb{;}text_1{,}Descrição{,}{,}5{,}{,}S{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}summary{;}int_key{,}Ordem{,}{,}{,}{,}S{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}S{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}char_1{,}Destaque{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}featured{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => '',
		'template' => 'videos/index',
		'editpage' => '',
		'class' => '',
		'class_tipo' => '',
		'model_id_tipo' => 0,
		'tabela' => '',
		'layout' => Jp7_Box_Manager::COL_2_LEFT,
		'layout_registros' => Jp7_Box_Manager::COL_2_LEFT,
		'editar' => 'S',
		'texto' => 'Cadastro de vídeos do YouTube e Vimeo.',
		'disparo' => 'Jp7_Model_VideosTipo::checkThumb'
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
	
	public static function checkThumb($from, $id, $id_tipo) {
		if ($from == 'edit' || $from == 'insert') {
			$tipo = InterAdminTipo::getInstance($id_tipo);
			$registro = $tipo->getInterAdminById($id, array(
				'fields' => array('video', 'thumb')
			));
			if ($registro && !$registro->thumb) {
				if (startsWith('http://www.youtube.com', $registro->video)) {
					$registro->updateAttributes(array(
						'thumb' => Jp7_YouTube::getThumbnail($registro->video)
					));
				} elseif (startsWith('http://vimeo.com', $registro->video)) {
					$registro->updateAttributes(array(
						'thumb' => Jp7_Vimeo::getThumbnailLarge($registro->video)
					));
				}
			}			
		}
	}
}