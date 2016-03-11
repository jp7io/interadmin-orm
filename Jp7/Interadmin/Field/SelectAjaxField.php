<?php

namespace Jp7\Interadmin\Field;

class SelectAjaxField extends SelectField
{
    protected function getFormerField()
    {
        return parent::getFormerField()
                ->data_ajax()
                ->data_id_tipo($this->nome)
                ->data_has_tipo($this->hasTipo());
    }
    
    /**
     * Returns only the current selected option, all the other options will be
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
                'id = '.$db->qstr($value)
            ]);
        }
        if ($this->nome instanceof InterAdminTipo) {
            return $this->getTipoOptions([
                'parent_id_tipo = '.$this->nome->id_tipo,
                'id_tipo = '.$db->qstr($value)
            ]);
        }
        if ($this->nome === 'all') {
            return $this->getTipoOptions([
                'id_tipo = '.$db->qstr($value)
            ]);
        }
        throw new Exception('Not implemented');
    }
}
