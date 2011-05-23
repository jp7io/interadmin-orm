<?php

abstract class Jp7_Box_BoxAbstract {
	public function __construct(InterAdmin $record = null) {
		if ($record) {
			foreach ($record->attributes as $key => $value) {
				$this->$key = $value;
			}
			if (is_string($record->params)) {
				$this->params = unserialize($record->params);
			}
		}
	}
	/**
	 * Prepara os dados que vão ser utilizados na view do box mais tarde. 
	 * Exemplo: Faz a busca das notícias que vão ser exibidas.
	 * 
	 * @return void
	 */
	public function prepareData() {
		// Vazio por padrão
	}
	/**
	 * Retorna o HTML do box
	 * @return string
	 */
	public function getEditorHtml() {
		$fields = $this->_getEditorFields();
		
		ob_start();
		?>
		<div class="box box-<?php echo $this->id_box; ?>">
			<?php echo $this->_getEditorTitle(); ?>
			<?php echo $this->_getEditorControls((bool) $fields); ?>
			<div style="clear:both;"></div>
			<?php echo $fields; ?>
			<input type="hidden" name="box[]" value="<?php echo $this->id_box; ?>" />
		</div>
		<?php
		return ob_get_clean();
	}
	protected function _getEditorTitle() {
		return ucwords(str_replace('-', ' ', $this->id_box));
	}
	protected function _getEditorControls($hasFields = true) {
		?>
		<div class="icons">
			<?php if ($hasFields) { ?>
				<div class="icon icon-toggle" onclick="toggleConfig(this);"></div>
			<?php } ?>
			<div class="icon icon-delete" onclick="deleteBox(this);"></div>
		</div>		
		<?php
	}
	/**
	 * Prepara o HTML dos campos.
	 * @return string
	 */
	protected function _getEditorFields() {
		return '';
	}
	/**
	 * Retorna o CSS do box
	 * @return string
	 */
	public function getEditorStyle() {
		return '';
	}
	/**
	 * Helper for Checkbox.
	 * 
	 * @param string 	$name			Parameter's name, e.g. header is $this->params->header
	 * @param bool 		$default_value	Default value for when the value is NULL
	 * @return string
	 */
	protected function _checkbox($name, $default_value = false) {
		if (is_null($this->params->$name)) {
			$this->params->$name = $default_value;
		}
		ob_start();
		?>
		<input type="hidden" value="<?php echo $this->params->$name ? '1' : '0'; ?>" name="<?php echo $this->id_box; ?>[<?php echo $name; ?>][]" />
		<input type="checkbox" class="checkbox" <?php echo $this->params->$name ? 'checked="checked"' : ''; ?> onclick="$(this).prev().val(this.checked ? 1 : 0)" />
		<?php
		return ob_get_clean();
	}	
	/**
	 * Helper for Selectbox's options.
	 * 
	 * @param array 	$options
	 * @param int 		$value
	 * @return string
	 */
	protected function _options($options, $value) {
		ob_start();
		?>
		<option value="">Selecione</option>
		<option value="">-------------------------------</option>
		<?php foreach ($options as $option) { ?>
			<option value="<?php echo $option->id_tipo; ?>" <?php echo ($option->id_tipo == $value) ? 'selected="selected"' : ''; ?>><?php echo $option->nome; ?></option>
		<?php } ?>
		<?php
		return ob_get_clean();
	}
}