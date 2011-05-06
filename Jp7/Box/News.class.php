<?php

class Jp7_Box_News extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	$newsTipo = reset(InterAdminTipo::findTipos(array(
			'where' => array("model_id_tipo = 'News'"),
			'limit' => 1
		)));
		if ($newsTipo) {
			$this->news = $newsTipo->getInterAdmins(array(
				'fields' => array('titulo', 'date_publish'),
				'fields_alias' => true, // Não dá para garantir que está true por padrão
				'limit' => 3
			));
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
}