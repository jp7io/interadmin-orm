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
			<?php if (Jp7_Box_Manager::getRecordMode()) { ?>
				<div class="group">
					<div class="group-label">Vídeo</div>
					<div class="group-fields">
						<div class="field">
							<label>Dimensões:</label>
							<?php echo $box->numericField('videoWidth', 'Largura', '620'); ?> x
							<?php echo $box->numericField('videoHeight', 'Altura', '380'); ?> px
						</div>
					</div>
				</div>
			<?php } else { ?>
				<?php echo parent::_getEditorImageFields($box, false, 310, 230); ?>
			<?php } ?>
		</div>
		<?php
		return ob_get_clean();
	}
	
	public function prepareData(Jp7_Box_BoxAbstract $box) {
		if (Jp7_Box_Manager::getRecordMode()) {
			$box->params->videoWidth = $box->params->videoWidth ? $box->params->videoWidth : 620;
			$box->params->videoHeight = $box->params->videoHeight ? $box->params->videoHeight : 380;
			$box->view->params = $box->params;
		} else {
			parent::_prepareImageData($box, 310, 230);
		}
	}
	
	public static function checkThumb($from, $id, $id_tipo) {
		if ($from == 'edit' || $from == 'insert') {
			$tipo = InterAdminTipo::getInstance($id_tipo);
			$registro = $tipo->getInterAdminById($id, array(
				'fields' => array('video', 'thumb')
			));
			if ($registro && !$registro->thumb) {
				// Salvando thumb caso esteja vazio e seja um vídeo do YouTube ou Vimeo
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