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
	 * @param bool 	$isRecordPage
	 * @return void
	 */
	public function prepareData() {
		// Vazio por padrão
	}
	/**
	 * Retorna o HTML do box
	 * 
	 * @param bool 	$isRecordPage
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
	 * 
	 * @param bool 	$isRecordPage
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
	public function checkbox($name, $default_value = false) {
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
	public function options($options, $value) {
		ob_start();
		?>
		<option value="">Selecione</option>
		<option value="">-------------------------------</option>
		<?php foreach ($options as $option) { ?>
			<option value="<?php echo $option->value; ?>" <?php echo ($option->value == $value) ? 'selected="selected"' : ''; ?>><?php echo $option->text; ?></option>
		<?php } ?>
		<?php
		return ob_get_clean();
	}
	/**
	 * Helper a numeric input field.
	 *  
	 * @param string $name
	 * @param string $label
	 * @param string $placeholder [optional]
	 * @return string
	 */
	public function numericField($name, $label, $placeholder = '') {
		ob_start();
		?>
		<input type="text" class="numeric textbox" label="<?php echo $label; ?>" placeholder="<?php echo $placeholder; ?>"
			onkeypress="return DFonlyThisChars(true, false, ' -.,()', event)" 
			name="<?php echo $this->id_box; ?>[<?php echo $name; ?>][]"
			value="<?php echo $this->params->$name ? $this->params->$name : ''; ?>"	/>
		<?php
		return ob_get_clean();
	}
	public function tiposOptions($tipos, $value, $show_orphan = false) {
		$tree = array();
		foreach ($tipos as $tipo) {
			$tree[$tipo->parent_id_tipo][] = $tipo;
		}
		$options = array();
		$this->_addTiposRecursively($options, $tree);
		// Valores que não tem pai publicado
		if ($show_orphan) {
			foreach ($tree as $key => $value) {
				$this->_addTiposRecursively($options, $tree, $key);
			}
		}
		return $this->options($options, $value);
	}
	protected function _addTiposRecursively(&$options, &$tree, $parent_id_tipo = 0, $level = 0) {
		foreach ($tree[$parent_id_tipo] as $tipo) {
			$options[] = (object) array(
				'value' => $tipo->id_tipo,
				'text' => trim(str_repeat('--', $level) . ' ' . $tipo->nome)
			);
			if ($tree[$tipo->id_tipo]) {
				$this->_addTiposRecursively($options, $tree, $tipo->id_tipo, $level + 1);
			}
		}
		unset($tree[$parent_id_tipo]);
	}	
}