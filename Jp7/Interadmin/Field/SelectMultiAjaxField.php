<?php

namespace Jp7\Interadmin\Field;

use Former;

class SelectMultiAjaxField extends SelectMultiField
{
    protected function getFormerField()
    {
        return Former::select($this->getFormerName().'[]') // multiple requires []
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
            return $this->getRecordOptions([
                'id IN ('.$db->qstr($value).')'
            ]);
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
        throw new Exception('Not implemented');
    }
    
    protected function getRecordOptions($where = [])
    {
        $records = $this->findRecords($where);
        $options = [];
        foreach ($records as $record) {
            $options[$record->getStringValue()] = ['value' => $record->id, 'selected' => true];
        }
        return $options;
    }
}
