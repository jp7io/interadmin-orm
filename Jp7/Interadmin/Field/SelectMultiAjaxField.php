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
    
    /**
     * Returns only the current selected options, all the other options will be
     * provided by the AJAX search
     * @return array
     * @throws Exception
     */
    protected function getOptions()
    {
        global $db;
        if (!$value = $this->getValue()) {
            return []; // evita query inutil
        }
        if (!$this->hasTipo()) {
            $ids = array_filter(explode(',', $value));
            $records = $this->records()->whereIn('id', $ids)->get();
            return $this->toOptions($records);
        }
        /*
        if ($this->nome instanceof InterAdminTipo) {
            return $this->getTipoOptions([
                'parent_id_tipo = '.$this->nome->id_tipo,
                'id_tipo IN ('.$value.')'
            ]);
        }
        if ($this->nome === 'all') {
            return $this->getTipoOptions([
                'id_tipo IN ('.$value.')'
            ]);
        }
        */
        throw new UnexpectedValueException('Not implemented');
    }
    
    public function getFilterTag()
    {
        $selectField = new SelectAjaxField($this->campo);
        $selectField->setRecord($this->record);
        return $selectField->getFilterTag();
    }

    // We have more than one option selected, so we need to add the selected attribute to options
    protected function toOptions(array $array)
    {
        $options = [];
        foreach ($array as $record) {
            $options[$record->getStringValue()] = [
                'value' => $record->id,
                'selected' => true // We are assuming only selected records were found
            ];
        }
        return $options;
    }
}
