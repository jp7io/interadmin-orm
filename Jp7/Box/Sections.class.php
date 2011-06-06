<?php

class Jp7_Box_Sections extends Jp7_Box_BoxAbstract {   
    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle() {
        return 'Seções';
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
							"admin = ''",
							"model_id_tipo NOT IN ('Boxes', 'Settings', 'Introduction', 'Images')"
						),
						'order' => 'parent_id_tipo, ordem',
						'use_published_filters' => true
					));
					?>
					<?php echo $this->tiposOptions($tipos,  $this->params->section); ?>			
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