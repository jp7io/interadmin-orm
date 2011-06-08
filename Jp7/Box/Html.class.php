<?php

class Jp7_Box_Html extends Jp7_Box_BoxAbstract {	/**
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
					value="<?php echo $this->params->title; ?>"	/>
			</div>
			<div class="field">
				<label>Sem wrapper:</label>
				<?php echo $this->checkbox('no_wrapper'); ?>
			</div>
			<div class="field">
				<textarea class="textarea" id="textarea_<?php echo uniqid(); ?>" name="<?php echo $this->id_box; ?>[html][]"><?php echo $this->params->html; ?></textarea>
			</div>
		</div>
		<?php
		return ob_get_clean();
    }
}