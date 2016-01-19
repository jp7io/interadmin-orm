<?php

class Jp7_Box_Images extends Jp7_Box_BoxAbstract
{
    /**
     * @see Jp7_Box_BoxAbstract::prepareData()
     */
    public function prepareData()
    {
        $this->images = [];
        $this->view->headScript()->appendFile(DEFAULT_PATH.'/js/jquery/jquery.jp7.js');
        $this->view->headScript()->appendFile(DEFAULT_PATH.'/js/jquery/jquery.lightbox-0.5.js');
        $this->view->headLink()->appendStylesheet(DEFAULT_PATH.'/js/jquery/themes/jquery.lightbox-0.5.css');

        if ($this->view->record) {
            try {
                $this->images = $this->view->record->getImagens([
                    'fields' => ['*'],
                ]);
            } catch (Exception $e) {
                // Do nothing, method getImagens doesnt exist
            }
        } elseif ($this->view->tipo) {
            if ($imagesTipo = $this->view->tipo->getFirstChildByModel('Images')) {
                $this->images = $imagesTipo->find([
                    'fields' => '*',
                ]);
            }
        }
    }
    /**
     * @see Jp7_Box_BoxAbstract::_getEditorTitle()
     */
    protected function _getEditorTitle()
    {
        return 'Imagens';
    }
}
