<?php

class Jp7_Box_Offices extends Jp7_Box_BoxAbstract {
    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	$officeTipo = InterAdminTipo::findFirstTipoByModel('Offices');
		$this->offices = array();
		if ($this->officeTipo = $officeTipo) {
			$options = array(
				'fields' => array('*', 'state' => array('sigla')),
				'limit' => $this->params->limit
			);
			if ($this->params->featured) {
				$options['where'][] = "featured <> ''";
			}
			$this->title = ($this->params->title) ? $this->params->title : $officeTipo->getNome();
			$this->offices = $officeTipo->getInterAdmins($options);
			
			$this->_prepareDataImages();
		}
    }
	
    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle() {
        return 'Unidades';
    }
	
	protected function _getEditorFields() {
    	ob_start();
		?>
		<div class="fields">
			<div class="field">
				<label>Título:</label>
				<input type="text" class="textbox" label="Título" placeholder="Automático" 
					name="<?php echo $this->id_box; ?>[title][]"
					value="<?php echo $this->params->title; ?>"	/>
			</div>
			<div class="field">
				<label>Destaques:</label>
				<?php echo $this->checkbox('featured'); ?>
			</div>
			<div class="field">
				<label>Limite:</label>
				<?php echo $this->numericField('limit', 'Limite', 'Todos'); ?>
			</div>
			
			<?php $this->_getEditorFieldsImages(); ?>			
		</div>
		<?php
		return ob_get_clean();
    }
}