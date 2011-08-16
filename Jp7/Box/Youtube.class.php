<?php

class Jp7_Box_Youtube extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::_getEditorFields()
     */
    protected function _getEditorFields() {
    	ob_start();
		?>
		<div class="fields">
			<div class="field obligatory">
				<label>URL:</label>
				<input type="text" class="textbox" obligatory="yes" label="URL" 
					value="<?php echo $this->params->url ? $this->params->url : ''; ?>" 
					name="<?php echo $this->id_box; ?>[url][]" />
			</div>
			
			<div class="field">
				<label>Largura:</label>
				<?php echo $this->numericField('width', 'Largura', '315'); ?> px
			</div>
			<div class="field">
				<label>Altura:</label>
				<?php echo $this->numericField('height', 'Altura', '236'); ?> px
			</div>
			
			<div class="field">
				<label>Reproduzir em HD:</label>
				<?php echo $this->checkbox('hd'); ?>
			</div>
			
		</div>
		<?php
		return ob_get_clean();
    }
	/**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */

	public function getEditorStyle() {
		return "
.box-{$this->id_box} {
	background: #FFDDDD;
}
";
	}
    protected function _getEditorTitle() {
        return 'YouTube';
    }
}