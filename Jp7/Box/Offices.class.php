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
			// Tamanho das imagens
			$this->params->imgHeight = $this->params->imgHeight ? $this->params->imgHeight : 60;
			$this->params->imgWidth = $this->params->imgWidth ? $this->params->imgWidth : 80;
			
			$this->params->imgSize = $this->params->imgWidth . 'x' . $this->params->imgHeight;
			$this->params->imgCrop = isset($this->params->imgCrop) ? $this->params->imgCrop : true;
			
			if ($this->view) {
				$this->view->headStyle()->appendStyle('
	.box-offices.id-' . $this->id . ' .img-wrapper {
	height: ' . $this->params->imgHeight . 'px;
	width: ' . $this->params->imgWidth . 'px;
	line-height: ' . $this->params->imgHeight . 'px;
	}
	.box-offices.id-' . $this->id . ' .img-wrapper img {
	max-height: ' . $this->params->imgHeight . 'px;
	max-width: ' . $this->params->imgWidth . 'px;
	}
	');
			}
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
			
			<div class="group">
				<div class="group-label">Imagens</div>
				<div class="group-fields">
					<div class="field">
						<label>Dimensões:</label>
						<?php echo $this->numericField('imgWidth', 'Largura', '80'); ?> x
						<?php echo $this->numericField('imgHeight', 'Altura', '60'); ?> px
					</div>
					<div class="field">
						<label title="Se estiver marcado irá recortar a imagem nas dimensões exatas que foram informadas.">Recortar:</label>
						<?php echo $this->checkbox('imgCrop', true); ?>
					</div>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
    }
}