<?php

class Jp7_Model_TipoAbstract extends InterAdminTipo {
	public $isSubTipo = false;
	public $hasOwnPage = true;
	
	/**
	 * $id_tipo não é inteiro
	 * @return 
	 */
	public function __construct() {
		
	}
	
	public function getFieldsValues($fields, $forceAsString = false, $fieldsAlias = false) {
		if (is_string($fields)) {
			return $this->attributes[$fields]; 
		} elseif (is_array($fields)) {
			return (object) array_intersect_key($this->attributes, array_flip($fields));
		}
	}
	
	protected function _findChildByModel($model_id_tipo) {
		$child = InterAdminTipo::findFirstTipoByModel($model_id_tipo, array(
			'where' => array("admin <> ''")
		));
		if (!$child) {
			// Tenta criar o tipo filho caso ele não exista
			$sistemaTipo = InterAdminTipo::findFirstTipo(array(
				'where' => array(
					"nome = 'Sistema'",
					"admin <> ''"
				)
			));
			if ($sistemaTipo) {
				$columns = $sistemaTipo->getDb()->MetaColumns($sistemaTipo->getTableName());
				if ($columns['MODEL_ID_TIPO']->type == 'varchar') {
					$classesTipo = $sistemaTipo->getFirstChildByNome('Classes');
					if ($classesTipo) {
						$child = new InterAdminTipo();
						$child->parent_id_tipo = $classesTipo->id_tipo;
						$child->model_id_tipo = $model_id_tipo;
						$child->nome = 'Modelo - ' . $model_id_tipo;
						$child->mostrar = 'S';
						$child->admin = 'S';
						$child->save();
						return $child;
					}
				}
			}
			//throw new Exception('Could not find a Tipo using the model "' . $model_id_tipo . '". You need to create one in Sistema/Classes.');
		} else {
			return $child;
		}
	}
	
	/**
	 * Trigger executado após inserir um tipo com esse modelo.
	 * 
	 * @param InterAdminTipo $tipo
	 * @return void
	 */
	public function createChildren(InterAdminTipo $tipo) {
		
	}
	/**
	 * Helper for creating children Tipos for Boxes, Settings and Introduction.
	 * 
	 * @param InterAdminTipo $tipo
	 * @return void
	 */
	public function createBoxesSettingsAndIntroduction(InterAdminTipo $tipo) {
		if (!$tipo->getFirstChildByModel('Introduction')) {
			$introduction = $tipo->createChild('Introduction');
			$introduction->nome = 'Introdução';
			$introduction->ordem = -60;
	        $introduction->save();
		}
		if (!$tipo->getFirstChildByModel('Images')) {
			$images = $tipo->createChild('Images');
			$images->nome = 'Images';
			$images->ordem = -50;
	        $images->save();
		}
		if ($tipo->model_id_tipo !== 'Videos' && !$tipo->getFirstChildByModel('ContentVideos')) {
			$videos = $tipo->createChild('ContentVideos');
			$videos->nome = 'Vídeos';
			$videos->ordem = -40;
	        $videos->save();
		}
		if ($tipo->model_id_tipo !== 'Files' && !$tipo->getFirstChildByModel('ContentFiles')) {
			$files = $tipo->createChild('ContentFiles');
			$files->nome = 'Arquivos para Download';
			$files->ordem = -30;
	        $files->save();
		}
		if (!$tipo->getFirstChildByModel('Boxes')) {
			$boxes = $tipo->createChild('Boxes');
			$boxes->nome = 'Boxes';
			$boxes->ordem = -20;
	        $boxes->save();
		}
		if (!$tipo->getFirstChildByModel('Settings')) {
			$settings = $tipo->createChild('Settings');
			$settings->nome = 'Configurações';
			$settings->ordem = -10;
	        $settings->save();
		}
	}
	/**
	 * Returns the fields when editting the boxes.
	 * 
	 * @param Jp7_Box_BoxAbstract $box
	 * @return string	HTML
	 */
	public function getEditorFields(Jp7_Box_BoxAbstract $box) {
		// do nothing
	}
	/**
	 * Receives the params from the boxes and prepare the necessary data.
	 * 
	 * @param Jp7_Box_BoxAbstract $box
	 * @return void
	 */
	public function prepareData(Jp7_Box_BoxAbstract $box) {
		// do nothing
	}
	
	protected function _getEditorImageFields($box, $lightbox = false, $default_width = '80', $default_height = '60') {
		ob_start();
		?>
		<div class="group">
			<div class="group-label">Imagens</div>
			<div class="group-fields">
				<div class="field">
					<label>Dimensões:</label>
					<?php echo $box->numericField('imgWidth', 'Largura', $default_width); ?> x
					<?php echo $box->numericField('imgHeight', 'Altura', $default_height); ?> px
				</div>
				<div class="field">
					<label title="Se estiver marcado irá recortar a imagem nas dimensões exatas que foram informadas.">Recortar:</label>
					<?php echo $box->checkbox('imgCrop'); ?>
				</div>
				<?php if ($lightbox) { ?>
					<div class="field">
						<label title="Exibe visualizador com a imagem ampliada.">Ampliar:</label>
						<?php echo $box->checkbox('lightbox'); ?>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
	
	protected function _prepareImageData($box, $default_width = '80', $default_height = '60') {
		$params = $box->params; // facilita
		$view = $box->view;
		
		$params->imgWidth = $params->imgWidth ? $params->imgWidth : $default_width;
		$params->imgHeight = $params->imgHeight ? $params->imgHeight : $default_height;
		$params->imgSize = $params->imgWidth . 'x' . $params->imgHeight; 
		
		$view->params = $params;
		
		if ($params->lightbox) {
			$view->headScript()->appendFile('/_default/js/jquery/jquery.jp7.js');
			$view->headScript()->appendFile('/_default/js/jquery/jquery.lightbox-0.5.js');
			$view->headLink()->appendStylesheet('/_default/js/jquery/themes/jquery.lightbox-0.5.css');
		}
				
		$view->headStyle()->appendStyle('
.content-' . toId($this->id_tipo) . ' .img-wrapper {
	height: ' . $params->imgHeight  . 'px;
	width: ' . $params->imgWidth . 'px;
	line-height: ' . $params->imgHeight  . 'px;
}
.content-' . toId($this->id_tipo) . ' .img-wrapper img {
	max-height: ' . $params->imgHeight  . 'px;
	max-width: ' . $params->imgWidth . 'px;
}
');
	}
}