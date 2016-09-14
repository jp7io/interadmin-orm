<?php

namespace Jp7\Interadmin\Field;

use Former;
use UnexpectedValueException;

class SelectMultiAjaxField extends SelectMultiField
{
    protected function getFormerField()
    {
        return Former::select($this->getFormerName().'[]') // multiple requires []
            ->id($this->getFormerId())
            ->options($this->getOptions())
            ->multiple()
            ->data_ajax()
            ->data_id_tipo($this->nome)
            ->data_has_tipo($this->hasTipo());
    }

    protected function getOptions()
    {
        return $this->toOptions($this->getCurrentRecords());
    }

    public function getFilterTag()
    {
        $selectField = new SelectAjaxField($this->campo);
        $selectField->setRecord($this->record);
        return $selectField->getFilterTag();
    }

    // We have more than one option selected, so we need to add the selected attribute to options
    protected function toOptions($array)
    {
        $options = [];
        foreach (parent::toOptions($array) as $id => $text) {
            $options[$text] = [
                'value' => $id,
                'selected' => true // We are assuming only selected records were found
            ];
        }
        return $options;
    }
}
