<?php

class Jp7_Box_Slideshow extends Jp7_Box_BoxAbstract {
   
	public function prepareData() {
		$this->items = array();
		if ($tipo = $this->view->tipo) {
			if ($slideshowTipo = $tipo->getFirstChildByModel('Slideshow')) {
				$this->view->headScript()->appendFile('/_default/js/jquery/jquery.jp7.js');
				
				$this->items = $slideshowTipo->getInterAdmins(array(
					'fields' => '*'
				));
			}
		}
		
		$this->params->effect = $this->params->effect ? $this->params->effect : 'fadeOut';
		// Tamanho das imagens
		$this->params->height = $this->params->height ? $this->params->height : 320;
		$this->params->width = $this->params->width ? $this->params->width : 975;
		$this->params->size = $this->params->width . 'x' . $this->params->height;
		
		$this->view->headStyle()->appendStyle('
div.box-slideshow {
	width: ' . $this->params->width . 'px;
	height: ' . $this->params->height . 'px;
}
div.box-slideshow .slideshow-item img {
	width: ' . $this->params->width . 'px;
	height: ' . $this->params->height . 'px;
}');
	}
	
	/**
     * @see Jp7_Box_BoxAbstract::_getEditorFields()
     */
    protected function _getEditorFields() {
    	ob_start();
		?>
		<div class="fields">
			<div class="field">
				<label>Efeito:</label>
				<select class="selectbox" obligatory="yes" label="Efeito" name="<?php echo $this->id_box; ?>[effect][]">
					<?php
					$options = array(
						(object) array('value' => 'fadeOut', 'text' => 'Desaparecer gradualmente - Fade Out'),
						(object) array('value' => 'slideLeft', 'text' => 'Deslizar para a esquerda')
					);
					?>
					<?php echo $this->options($options, $this->params->effect ? $this->params->effect : 'fadeOut'); ?>					
				</select>
			</div>
			
			<div class="field">
				<label>Largura:</label>
				<?php echo $this->numericField('width', 'Largura', '975'); ?> px
			</div>
			<div class="field">
				<label>Altura:</label>
				<?php echo $this->numericField('height', 'Altura', '320'); ?> px
			</div>
		</div>
		<?php
		return ob_get_clean();
    }
}