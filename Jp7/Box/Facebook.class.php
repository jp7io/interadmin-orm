<?php

class Jp7_Box_Facebook extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::_getEditorFields()
     */
    protected function _getEditorFields() {
    	ob_start();
		?>
		<div class="fields">
			<div class="field obligatory">
				<label>URL:</label>
				<input type="text" class="textbox" obligatory="yes" label="Href" value="<?php echo $this->params->href ? $this->params->href : 'http://www.facebook.com/platform'; ?>" 
					name="<?php echo $this->id_box; ?>[href][]" />
			</div>
			<div class="field">
				<label>Cores:</label>
				<select class="selectbox" obligatory="yes" label="Cores" name="<?php echo $this->id_box; ?>[colorscheme][]">
					<?php
					$options = array(
						(object) array('value' => 'light', 'text' => 'Claras / Light'),
						(object) array('value' => 'dark', 'text' => 'Escuras / Dark')
					);
					?>
					<?php echo $this->options($options, $this->params->colorscheme ? $this->params->colorscheme : 'light'); ?>					
				</select>
			</div>
			<div class="field">
				<label>Faces:</label>
				<?php echo $this->checkbox('show_faces', true); ?>
			</div>
			<div class="field">
				<label>Atualizações:</label>
				<?php echo $this->checkbox('stream'); ?>
			</div>
			<div class="field">
				<label>Cabeçalho:</label>
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