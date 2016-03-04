<?php

namespace Jp7\Interadmin\Field;

use HtmlObject\Input;

class FileField extends ColumnField
{
    protected $id = 'file';
    protected $editCredits = true;

    public function getCellHtml()
    {
        return interadmin_arquivos_preview(
            $this->getText() ?: DEFAULT_PATH.'/img/px.png', // url
            '', // alt
            false, // presrc
            true // icon_small
        );
    }
    
    function setEditCredits($boolean)
    {
        $this->editCredits = $boolean;
    }

    protected function getFormerField()
    {
        $input = parent::getFormerField();
        $input->append($this->getSearchButton());
        // TODO td.image_preview .image_preview_background
        $input->append($this->getCellHtml()); // thumbnail
        if ($this->editCredits) {
            $input->append($this->getCreditsHtml());
        }
        return $input;
    }
    
    protected function getSearchButton()
    {
        return Input::button(null, 'Procurar...');
    }
    
    protected function getCreditsHtml()
    {
        $field = new VarcharField(['tipo' => $this->tipo.'_text']);
        $field->setRecord($this->record);
        $field->setIndex($this->i);
        $input = $field->getFormerField();
        $this->handleReadonly($input);
        return '<div class="input-group"><span class="input-group-addon">Legenda:</span>'.
            $input->raw().'</div>';
    }
}
