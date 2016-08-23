<?php

class Jp7_View_Helper_Box extends Zend_View_Helper_Abstract
{
    public function Box($id_box, $params = [])
    {
        $classe = Jp7_Box_Manager::get($id_box);
        if (!$classe) {
            throw new Exception('Unknown box: '.$id_box);
        }

        $columnTipo = new Jp7_Model_BoxesTipo;
        $boxIdTipo = $columnTipo->getInterAdminsChildren()['Boxes']['id_tipo'];

        $fakeRecord = new InterAdmin();
        $fakeRecord->id_box = $id_box;
        $fakeRecord->id_tipo = $boxIdTipo;
        $fakeRecord->params = (object) $params;

        $box = new $classe($fakeRecord);
        if (!$box instanceof Jp7_Box_BoxAbstract) {
            throw new Exception('Expected an instance of Jp7_Box_BoxAbstract, received a '.get_class($box).'.');
        }
        $box->prepareData();

        return $this->view->partial('boxes/'.$id_box.'.phtml', $box);
    }
}
