<?php

class Jp7_Box_FacebookComments extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::_getEditorFields()
     */
    protected function _getEditorFields() {
    	ob_start();
		?>
		<div class="fields">
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
				<label>Nº de Posts:</label>
				<?php echo $this->numericField('num_posts', 'Nº de Posts', '10'); ?>
			</div>
			<div class="field">
				<label>Largura:</label>
				<?php echo $this->numericField('width', 'Largura', '645'); ?> px
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
.box-{$this->id_box} label,
.box-{$this->id_box} div {
	color: white;
}
";
	}
	
}