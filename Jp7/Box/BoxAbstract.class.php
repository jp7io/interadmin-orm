<?php

abstract class Jp7_Box_BoxAbstract {
	public function __construct(InterAdmin $record = null) {
		if ($record) {
			foreach ($record->attributes as $key => $value) {
				$this->$key = $value;
			}
			if ($record->params) {
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
				<img class="icon-cog" src="/_default/img/cog.png" onclick="toggleConfig(this);" />
			<?php } ?>
			<img class="icon-delete" src="/_default/img/delete.png" onclick="deleteBox(this);" />
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
	 * @param string $name
	 * @param bool $value
	 * @return string
	 */
	protected function _checkbox($name) {
		ob_start();
		?>
		<input type="hidden" value="<?php echo $this->params->$name ? '1' : '0'; ?>" name="<?php echo $this->id_box; ?>[<?php echo $name; ?>][]" />
		<input type="checkbox" class="checkbox" <?php echo $this->params->$name ? 'checked="checked"' : ''; ?> onclick="$(this).prev().val(this.checked ? 1 : 0)" />
		<?php
		return ob_get_clean();
	}
}