<?php

namespace Jp7\Interadmin\Field;

use UnexpectedValueException;
use Jp7\Interadmin\Type;

class SelectAjaxField extends SelectField
{
    protected function getFormerField()
    {
        return parent::getFormerField()
                ->data_ajax()
                ->data_id_tipo($this->nome)
                ->data_has_tipo($this->hasTipo());
    }
    
    protected function getOptions()
    {
        return $this->toOptions($this->getCurrentRecords());
    }
    
    public function searchOptions($search)
    {
        if (!$this->hasTipo()) {
            $query = $this->buildSearch($this->records(), $this->getSearchableFields(), $search);
            return $this->toJsonOptions($query->get());
        }
        if ($this->nome instanceof Type || $this->nome === 'all') {
            $query = $this->buildSearch($this->tipos(), ['nome'], $search);
            return $this->toJsonOptions($query->get());
        }
        throw new UnexpectedValueException('Not implemented');
    }
    
    protected function buildSearch($query, $fields, $search)
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
        $query->orderByRaw(implode(', ', $order));
        return $query;
    }
    
    protected function getSearchableFields()
    {
        $campos = $this->nome->getCampos();
        $searchable = [];
        
        foreach ($this->nome->getCamposCombo() as $campoCombo) {
            if ($campos[$campoCombo]['nome'] instanceof Type) {
                foreach ($campos[$campoCombo]['nome']->getCamposCombo() as $campoCombo2) {
                    $searchable[] = $campoCombo.'.'.$campoCombo2;
                }
            } else {
                $searchable[] = $campoCombo;
            }
        }
        return $searchable;
    }
    
    protected function toJsonOptions($array)
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
