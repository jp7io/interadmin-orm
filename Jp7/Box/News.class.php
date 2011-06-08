<?php

class Jp7_Box_News extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	$newsTipo = InterAdminTipo::findFirstTipoByModel('News');
		if ($newsTipo) {
			$options = array(
				'fields' => array('title', 'image', 'date_publish'),
				'fields_alias' => true, // Não dá para garantir que está true por padrão
				'limit' => $this->params->limit
			);
			if ($this->params->featured) {
				$options['where'][] = "featured <> ''";
			}
			$this->title = ($this->params->title) ? $this->params->title : $newsTipo->getNome();
			$this->news = $newsTipo->getInterAdmins($options);
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
		</div>
		<?php
		return ob_get_clean();
    }
}