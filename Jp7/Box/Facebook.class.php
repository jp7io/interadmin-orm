<?php

class Jp7_Box_Facebook extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::_getEditorFields()
     */
    protected function _getEditorFields() {
    	ob_start();
		?>
		<div class="fields">
			<div class="field obligatory">
				<label>Href:</label>
				<input type="text" class="textbox" obligatory="yes" label="Href" value="<?php echo $this->params->href ? $this->params->href : 'http://www.facebook.com/platform'; ?>" 
					name="<?php echo $this->id_box; ?>[href][]" />
			</div>
			
			<div class="field">
				<label>Show Faces:</label>
				<?php echo $this->checkbox('show_faces', true); ?>
			</div>
			
			<div class="field">
				<label>Stream:</label>
				<?php echo $this->checkbox('stream'); ?>
			</div>
			
			<div class="field">
				<label>Header:</label>
				<?php echo $this->checkbox('header'); ?>
			</div>
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