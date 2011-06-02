<?php

class Jp7_Box_Content extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	if ($section = $this->params->section) {
			if ($this->sectionTipo = InterAdminTipo::getInstance($section)) {
				$this->title = ($this->params->title) ? $this->params->title : $this->sectionTipo->getNome();
				
				$options = array(
					'fields' => array('*'),
					'limit' => $this->params->limit
				);
				if ($this->params->featured) {
					$options['where'][] = "featured <> ''";
				}				
				$this->records = $this->sectionTipo->getInterAdmins($options);
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
			<div class="field obligatory">
				<label>Seção:</label>
				<select class="selectbox" obligatory="yes" label="Seção" name="<?php echo $this->id_box; ?>[section][]">
					<?php
					$tipos = InterAdminTipo::findTipos(array(
						'where' => array(
							"model_id_tipo = 'Content'",
							"model_id_tipo != '0'"
						),
						'order' => 'parent_id_tipo, ordem'
					));
					?>
					<?php echo $this->options($tipos,  $this->params->section); ?>					
				</select>
			</div>
			<div class="field">
				<label>Destaques:</label>
				<?php echo $this->checkbox('featured'); ?>
			</div>
			<div class="field">
				<label>Limite:</label>
				<?php echo $this->numericField('limit', 'Limite', 'Todos'); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
    }
}