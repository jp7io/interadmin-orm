<?php

class Jp7_Box_Content extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	if ($section = $this->params->section) {
			if ($this->sectionTipo = InterAdminTipo::getInstance($section)) {
				$this->title = ($this->params->title) ? $this->params->title : $this->sectionTipo->getNome();
				$this->records = $this->sectionTipo->getInterAdmins(array(
					'fields' => array('*')
				));
			}
		}
    }
	
    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle() {
        return 'Conteúdo';
    }
	
	/**
     * @see Jp7_Box_BoxAbstract::_getEditorFields()
     */
	protected function _getEditorFields() {
    	ob_start();
		?>
		<div class="fields">
			<div class="field">
				<label>Título:</label>
				<input type="text" class="textbox" label="Título" placeholder="Automático" 
					name="<?php echo $this->id_box; ?>[title][]"
					value="<?php echo $this->params->title ? $this->params->title : ''; ?>"	/>
			</div>
			
			<div class="field">
				<label>Seção:</label>
				<select class="selectbox" obligatory="yes" label="Seção" name="<?php echo $this->id_box; ?>[section][]">
					<?php
					$tipos = InterAdminTipo::findTipos(array(
						'where' => array("model_id_tipo = 'Content'")
					));
					?>
					<?php echo $this->_options($tipos,  $this->params->section); ?>					
				</select>
			</div>
		</div>
		<?php
		return ob_get_clean();
    }
}