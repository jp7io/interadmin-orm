<?php

class Jp7_Box_Content extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	if ($section = $this->params->section) {
			if ($this->sectionTipo = InterAdminTipo::getInstance($section)) {
				global $lang;
				
				$this->title = ($this->params->{'title' . $lang->prefix}) ? $this->params->{'title' . $lang->prefix} : $this->sectionTipo->getNome();
				
				$options = array(
					'fields' => array('*'),
					'limit' => $this->params->limit
				);
				if ($this->params->featured) {
					$options['where'][] = "featured <> ''";
				}				
				$this->records = $this->sectionTipo->getInterAdmins($options);
				
				$this->_prepareDataImages();
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
		global $config;
		
    	ob_start();
		?>
		<div class="fields">
			<?php foreach ($config->langs as $key => $lang) { ?>
				<?php
				$sufix = ($lang->default) ? '' : '_' . $key;
				?>
				<div class="field">
					<label>
						<?php if (count($config->langs) > 1) { ?>							<img src="/_default/img/icons/<?php echo $key; ?>.png" style="vertical-align:middle;" />						<?php } ?>					Título:</label>
					<input type="text" class="textbox" label="Título" placeholder="Automático" 
						name="<?php echo $this->id_box; ?>[title<?php echo $sufix; ?>][]"
						value="<?php echo $this->params->{'title' . $sufix}; ?>"	/>
				</div>
			<?php } ?>
			<div class="field obligatory">
				<label>Seção:</label>
				<select class="selectbox" obligatory="yes" label="Seção" name="<?php echo $this->id_box; ?>[section][]">
					<?php
					$tipos = InterAdminTipo::findTipos(array(
						'fields' => array('nome'),
						'where' => array(
							"campos LIKE '%}title{%'",
							"campos LIKE '%}image{%'",
							"model_id_tipo != 'Introduction'"				
						),
						'order' => 'parent_id_tipo, ordem',
						'use_published_filters' => true
					));
					?>
					<?php echo $this->tiposOptions($tipos, $this->params->section, true); ?>					
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
			
			<?php $this->_getEditorFieldsImages(); ?>
		</div>
		<?php
		return ob_get_clean();
    }
}