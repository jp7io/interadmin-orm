<?php

class Jp7_Box_Files extends Jp7_Box_BoxAbstract {
    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	if ($this->view->record) {
			try {
				$this->files = $this->view->record->getArquivosParaDownload(array(
					'fields' => array('name', 'file')
				));
			} catch (Exception $e) {
				// Do nothing, method getImagens doesnt exist
			}
		} elseif ($this->view->tipo) {
			if ($filesTipo = $this->view->tipo->getFirstChildByModel('Files')) {
				$this->files = $filesTipo->getInterAdmins(array(
					'fields' => array('name', 'file')
				));
			}
		}
    }
    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle() {
        return 'Arquivos';
    }
}