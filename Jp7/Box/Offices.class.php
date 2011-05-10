<?php

class Jp7_Box_Offices extends Jp7_Box_BoxAbstract {    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData() {
    	$officeTipo = InterAdminTipo::findFirstTipo(array(
			'where' => array("model_id_tipo = 'Offices'")
		));
		$this->offices = array();
		if ($officeTipo) {
			$this->officeTipo = $officeTipo;
			$this->offices = $officeTipo->getInterAdmins(array(
				'fields' => array('*', 'state' => array('sigla')),
				'where' => array("featured <> ''")
			));
		}
    }
	
    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle() {
        return 'Unidades';
    }
}