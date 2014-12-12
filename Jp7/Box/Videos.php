<?php

class Jp7_Box_Videos extends Jp7_Box_BoxAbstract {
    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	if ($this->view->record) {
			try {
				$this->videos = $this->view->record->getVideos(array(
					'fields' => array('*')
				));
			} catch (Exception $e) {
				// Do nothing, method getVideos doesnt exist
			}
		} elseif ($this->view->tipo) {
			if ($videosTipo = $this->view->tipo->getFirstChildByModel('ContentVideos')) {
				$this->videos = $videosTipo->find(array(
					'fields' => array('*')
				));
			}
		}
    }
    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle() {
        return 'Vídeos';
    }
}