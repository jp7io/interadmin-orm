<?php

class Jp7_Box_Youtube extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::_getEditorFields()
     */
    protected function _getEditorFields() {
    	ob_start();
		?>
		<div class="fields">
			<div class="field obligatory">
				<label>ID do Vídeo:</label>
				<input type="text" class="textbox" obligatory="yes" label="ID" 
					value="<?php echo $this->params->id ? $this->params->id : ''; ?>" 
					name="<?php echo $this->id_box; ?>[id][]" />
				<div class="example">Ex.: http://www.youtube.com/watch?v=<strong>ATfdi-oYWzw</strong></div>
			</div>
			
			<div class="field">
				<label>Reproduzir em HD:</label>
				<?php echo $this->checkbox('hd'); ?>
			</div>
			
			<div class="field">
				<label>Incluir vídeos relacionados:</label>
				<?php echo $this->checkbox('rel'); ?>
			</div>
			
			<div class="field">
				<label>Largura:</label>
				<input type="text" class="textbox" size="3" maxlength="4" obligatory="no" label="Width" 
					value="<?php echo $this->params->width ? $this->params->width : '315'; ?>" 
					name="<?php echo $this->id_box; ?>[width][]" /> px
			</div>
			<div class="field">
				<label>Altura:</label>
				<input type="text" class="textbox" size="3" maxlength="4" obligatory="no" label="Height" 
					value="<?php echo $this->params->height ? $this->params->height : '236'; ?>" 
					name="<?php echo $this->id_box; ?>[height][]" /> px
			</div>
		</div>
		<?php
		return ob_get_clean();
    }
	
	public function getEditorStyle() {
		return "
.box-{$this->id_box} {
	background: #FFF;
	color: black;
}
.box-{$this->id_box} label {
	color: black;
}
";
	}
}