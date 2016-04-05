<?php

namespace Jp7\Interadmin\Field;

use UnexpectedValueException;
use InterAdminTipo;

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
            $records = $this->records()->where('id', $value)->all();
            return $this->toOptions($records);
        }
        if ($this->nome instanceof InterAdminTipo || $this->nome === 'all') {
            $tipos = $this->tipos()->where('id_tipo', $value)->all();
            return $this->toOptions($tipos);
        }
        throw new UnexpectedValueException('Not implemented');
    }
    
    public function searchOptions($search)
    {
        if (!$this->hasTipo()) {
            $query = $this->buildSearch($this->records(), $this->getSearchableFields(), $search, false);
            return $this->toJsonOptions($query->all());
        }
        if ($this->nome instanceof InterAdminTipo || $this->nome === 'all') {
            $query = $this->buildSearch($this->tipos(), ['nome'], $search, 'id_tipo');
            return $this->toJsonOptions($query->all());
        }
        throw new UnexpectedValueException('Not implemented');
    }
    
    protected function buildSearch($query, $fields, $search, $primary_key)
    {
        global $db;
        $pattern = '%'.str_replace(' ', '%', $search).'%';
        $whereOr = [];
        foreach ($fields as $field) {
            $whereOr[] = $field.' LIKE '.$db->qstr($pattern);
        }
        if (is_numeric($search)) {
            $whereOr[] = 'id_tipo = '.intval($search);
        }
        $query->whereRaw('('.implode(' OR ', $whereOr).')');

        $order = [];
        foreach ($fields as $field) {
            $order[] = $field.' LIKE '.$db->qstr($search.'%').' DESC'; // starts with
        }
        $order = array_merge($order, $fields);
        $query->order(implode(', ', $order));
        return $query;
    }
    
    protected function getSearchableFields()
    {
        $campos = $this->nome->getCampos();
        $searchable = [];
        
        foreach ($this->nome->getCamposCombo() as $campoCombo) {
            if ($campos[$campoCombo]['nome'] instanceof InterAdminTipo) {
                foreach ($campos[$campoCombo]['nome']->getCamposCombo() as $campoCombo2) {
                    $searchable[] = $campoCombo.'.'.$campoCombo2;
                }
            } else {
                $searchable[] = $campoCombo;
            }
        }
        return $searchable;
    }
    
    protected function toJsonOptions(array $array)
    {
        $options = [];
        foreach ($this->toOptions($array) as $id => $text) {
            $options[] = [
                'id' => $id,
                'text' => $text
            ];
        }
        return $options;
    }
}
