<?php

class Jp7_Box_News extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	$newsTipo = InterAdminTipo::findFirstTipoByModel('News');
		$this->sectionTipo = $newsTipo;
		
		if ($newsTipo) {
			$options = array(
				'fields' => array('title', 'image', 'date_publish'),
				'fields_alias' => true, // Não dá para garantir que está true por padrão
				'limit' => $this->params->limit
			);
			if ($this->params->featured) {
				$options['where'][] = "featured <> ''";
			}
			
			global $lang;
			$this->title = ($this->params->{'title' . $lang->prefix}) ? $this->params->{'title' . $lang->prefix} : $newsTipo->getNome();
			$this->news = $newsTipo->find($options);
			
			$this->_prepareDataImages();
		} else {
			$this->news = array();	
		}
    }
    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle() {
        return 'Notícias';
    }
	
	protected function _getEditorFields() {
		$config = InterSite::config();
    	ob_start();
		?>
		<div class="fields">
			<?php foreach ($config->langs as $key => $lang) { ?>
				<?php
				$sufix = ($lang->default) ? '' : '_' . $key;
				?>
				<div class="field">
					<label>
						<?php if (count($config->langs) > 1) { ?>
							<img src="/_default/img/icons/<?php echo $key; ?>.png" style="vertical-align:middle;" />
						<?php } ?>
					Título:</label>
					<input type="text" class="textbox" label="Título" placeholder="Automático" 
						name="<?php echo $this->id_box; ?>[title<?php echo $sufix; ?>][]"
						value="<?php echo $this->params->{'title' . $sufix}; ?>"	/>
				</div>
			<?php } ?>
			
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