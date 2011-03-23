<?php

class Jp7_Box_Facebook extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::_getEditorFields()
     */
    protected function _getEditorFields() {
    	ob_start();
		?>
		<div class="fields">
			<label>Href:</label>
			<input type="text" class="textbox" obligatory="yes" label="Href" value="<?php echo $this->params->href; ?>" 
				name="<?php echo $this->id_box; ?>[href][]" />
			<div style="clear: both;"></div>
			<label>Show Faces:</label>
			<input type="checkbox" class="checkbox" value="1" <?php echo $this->params->show_faces ? 'checked="checked"' : ''; ?>" 
				name="<?php echo $this->id_box; ?>[show_faces][]" />
			<div style="clear: both;"></div>
			<label>Stream:</label>
			<input type="checkbox" class="checkbox" value="1" <?php echo $this->params->stream ? 'checked="checked"' : ''; ?>" 
				name="<?php echo $this->id_box; ?>[stream][]" />
			<div style="clear: both;"></div>
			<label>Header:</label>
			<input type="checkbox" class="checkbox" value="1" <?php echo $this->params->header ? 'checked="checked"' : ''; ?>" 
				name="<?php echo $this->id_box; ?>[header][]" />
			<div style="clear: both;"></div>
		</div>
		<?php
		return ob_get_clean();
    }
}