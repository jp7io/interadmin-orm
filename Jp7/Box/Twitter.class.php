<?php

class Jp7_Box_Twitter extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::_getEditorFields()
     */
    protected function _getEditorFields() {
    	ob_start();
		?>
		<div class="fields">
			<div class="field obligatory">
				<label>Username:</label>
				<input type="text" class="textbox" obligatory="yes" label="Username" value="<?php echo $this->params->username ? $this->params->username : 'twitter'; ?>" 
					name="<?php echo $this->id_box; ?>[username][]" />
			</div>
			<div class="field">
				<label>Altura:</label>
				<?php echo $this->numericField('height', 'Altura', '300'); ?> px
			</div>
			<div class="field">
				<label>Scrollbar:</label>
				<?php echo $this->checkbox('scrollbar'); ?>
			</div>
			<div class="field">
				<label>Horários:</label>
				<?php echo $this->checkbox('timestamp', true); ?>
			</div>
			<div class="field">
				<label>Limite:</label>
				<?php echo $this->numericField('limit', 'Limite', '4'); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
    }
	
	public function getEditorStyle() {
		return "
.box-{$this->id_box} {
	background: #DDEEF6;
}
";
	}
}