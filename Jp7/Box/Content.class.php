<?php

class Jp7_Box_Content extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	if ($section = $this->params->section) {
			if ($this->sectionTipo = InterAdminTipo::getInstance($section)) {
				$this->title = ($this->params->title) ? $this->params->title : $this->sectionTipo->getNome();
				
				$options = array(
					'fields' => array('*'),
					'limit' => $this->params->limit
				);
				if ($this->params->featured) {
					$options['where'][] = "featured <> ''";
				}				
				$this->records = $this->sectionTipo->getInterAdmins($options);
				
				// Tamanho das imagens
				$imgHeight = $this->params->imgHeight ? $this->params->imgHeight : 60;
				$imgWidth = $this->params->imgWidth ? $this->params->imgWidth : 80;
				
				$this->imgSize = $imgWidth . 'x' . $imgHeight;
				$this->imgCrop = isset($this->params->imgCrop) ? $this->params->imgCrop : true;
				
				$this->view->headStyle()->appendStyle('
.box-content.id-' . $this->id . ' .img-wrapper {
	height: ' . $imgHeight . 'px;
	width: ' . $imgWidth . 'px;
	line-height: ' . $imgHeight . 'px;
}
.box-content.id-' . $this->id . ' .img-wrapper img {
	max-height: ' . $imgHeight . 'px;
	max-width: ' . $imgWidth . 'px;
}
');
			}
		}
    }
	
    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle() {
        return 'Conteúdo';
    }
	
	/**
     * @see Jp7_Box_BoxAbstract::_getEditorFields()
     */
	protected function _getEditorFields() {
    	ob_start();
		?>
		<div class="fields">
			<div class="field">
				<label>Título:</label>
				<input type="text" class="textbox" label="Título" placeholder="Automático" 
					name="<?php echo $this->id_box; ?>[title][]"
					value="<?php echo $this->params->title ? $this->params->title : ''; ?>"	/>
			</div>
			<div class="field obligatory">
				<label>Seção:</label>
				<select class="selectbox" obligatory="yes" label="Seção" name="<?php echo $this->id_box; ?>[section][]">
					<?php
					$tipos = InterAdminTipo::findTipos(array(
						'where' => array(
							"model_id_tipo = 'Content'",
							"model_id_tipo != '0'"
						),
						'order' => 'parent_id_tipo, ordem',
						'use_published_filters' => true
					));
					?>
					<?php echo $this->tiposOptions($tipos, $this->params->section, true); ?>					
				</select>
			</div>
			<div class="field">
				<label>Destaques:</label>
				<?php echo $this->checkbox('featured'); ?>
			</div>
			<div class="field">
				<label>Limite:</label>
				<?php echo $this->numericField('limit', 'Limite', 'Todos'); ?>
			</div>
			
			<div class="group">
				<div class="group-label">Imagens</div>
				<div class="group-fields">
					<div class="field">
						<label>Dimensões:</label>
						<?php echo $this->numericField('imgWidth', 'Largura', '80'); ?> x
						<?php echo $this->numericField('imgHeight', 'Altura', '60'); ?> px
					</div>
					<div class="field">
						<label title="Se estiver marcado irá recortar a imagem nas dimensões exatas que foram informadas.">Recortar:</label>
						<?php echo $this->checkbox('imgCrop', true); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
    }
}