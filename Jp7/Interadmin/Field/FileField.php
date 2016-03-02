<?php

namespace Jp7\Interadmin\Field;

use Former;
Use HtmlObject\Element;

class FileField extends ColumnField
{
    protected $name = 'file';
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
        $input->append('<input type="button" value="Procurar..." />');
        // TODO td.image_preview .image_preview_background
        $input->append($this->getCellHtml()); // thumbnail
        if ($this->editCredits) {
            $input->append($this->getCreditsHtml());
        }
        return $input;
    }
    
    protected function getCreditsHtml()
    {
        $creditsField = new VarcharField([
            'tipo' => $this->campo['tipo'].'_text'
        ]);
        $creditsField->setRecord($this->record);
        $creditsField->setIndex($this->i);
        return '<div class="input-group"><span class="input-group-addon">Legenda:</span>'.
            $creditsField->getFormerField()->raw().
            '</div>';
    }
}
