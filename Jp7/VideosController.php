<?php

// Necessário para herdar métodos padrão
return Jp7_Controller_Dispatcher::evalAsAController(__FILE__);

class Jp7_VideosController extends __Controller_Action
{
    public function indexAction()
    {
        $id = $this->_getParam('id');
        // Irá cachear uma página diferente para cada registro
        Jp7_Cache_Output::getInstance()->start((string) $id);

        $contentTipo = self::getTipo();

        if ($id) {
            # show
            $record = $contentTipo->findById($id, [
                'fields' => ['title', 'video', 'summary', 'duration'],
            ]);
            if (!$record) {
                $this->_redirect($contentTipo->getUrl());
            }
            self::setRecord($record);
        } else {
            # index
            // Introdução
            if ($introductionTipo = $contentTipo->getFirstChildByModel('Introduction')) {
                $this->view->introductionItens = $introductionTipo->find([
                    'fields' => '*',
                ]);
            }

            $this->view->records = $contentTipo->find([
                'fields' => ['title', 'video', 'thumb', 'summary', 'duration'],
            ]);

            $this->view->headScript()->appendFile(DEFAULT_PATH.'/js/fancybox-2.1.5/jquery.fancybox.pack.js');
            $this->view->headScript()->appendFile(DEFAULT_PATH.'/js/fancybox-2.1.5/helpers/jquery.fancybox-media.js');
            $this->view->headLink()->appendStylesheet(DEFAULT_PATH.'/js/fancybox-2.1.5/jquery.fancybox.css');
        }
    }
}
