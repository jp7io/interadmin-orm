<?php

class Jp7_Box_Iframe extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::_getEditorFields()
     */
    protected function _getEditorFields() {
    	ob_start();
		?>
		<div class="fields">
			<div class="field obligatory">
				<label>Href:</label>
				<input type="text" class="textbox" obligatory="yes" label="Href" value="<?php echo $this->params->href ? $this->params->href : ''; ?>" 
					name="<?php echo $this->id_box; ?>[href][]" />
			</div>
			
			<div class="field">
				<label>Border:</label>
				<?php echo $this->checkbox('border'); ?>
			</div>
			
			<div class="field">
				<label>Altura:</label>
				<?php echo $this->numericField('height', 'Altura', '300'); ?> px
			</div>
			
			<div class="field">
				<label>Título:</label>
				<input type="text" class="textbox" obligatory="no" label="Título" placeholder="Nenhum" value="<?php echo $this->params->title ? $this->params->title : ''; ?>" 
					name="<?php echo $this->id_box; ?>[title][]" />
			</div>
		</div>
		<?php
		return ob_get_clean();
    }
}