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
			<?php echo $this->_checkbox('show_faces'); ?>
			<div style="clear: both;"></div>
			
			<label>Stream:</label>
			<?php echo $this->_checkbox('stream'); ?>
			<div style="clear: both;"></div>
			
			<label>Header:</label>
			<?php echo $this->_checkbox('header'); ?>
			<div style="clear: both;"></div>
		</div>
		<?php
		return ob_get_clean();
    }
	
	public function getEditorStyle() {
		return "
.box-{$this->id_box} {
	background: #3b5997;
	color: white;
}
.box-{$this->id_box} label {
	color: white;
}
";
	}
}