<?php

namespace Jp7\Interadmin\Field;

use InterAdminTipo;

trait SelectFieldTrait
{
    public function getLabel()
    {
        if ($this->label) {
            return $this->label;
        }
        if ($this->nome instanceof InterAdminTipo) {
            return $this->nome->getNome();
        }
        if ($this->nome === 'all') {
            return 'Tipos';
        }
        throw new Exception('Not implemented');
    }
        
    protected function formatText($id, $html)
    {
        list($value, $status) = $this->valueAndStatus($id);
        if ($html) {
            return ($status ? e($value) : '<del>'.e($value).'</del>');
        }
        return $value.($status ? '' : ' [deleted]');
    }
    
    protected function valueAndStatus($id)
    {
        if (!$id) {
            return ['', true];
        }
        if ($this->hasTipo()) {
            return [interadmin_tipos_nome($id), true];
        }
        $related = $this->nome->findById($id);
        if ($related) {
            return [$related->getStringValue(), $related->isPublished()];
        }
        return [$id, false];
    }
        
    protected function getDefaultValue()
    {
        if ($this->default && !is_numeric($this->default) && $this->nome instanceof InterAdminTipo) {
            $defaultArr = [];
            foreach (jp7_explode(',', $this->default) as $idString) {
                $selectedObj = $this->nome->findByIdString($idString);
                if ($selectedObj) {
                    $defaultArr[] = $selectedObj->id;
                }
            }
            if ($defaultArr) {
                $this->default = implode(',', $defaultArr);
            }
        }
        return $this->default;
    }
    
    protected function getOptions()
    {
        if (!$this->hasTipo()) {
            return $this->getRecordOptions();
        }
        if ($this->nome instanceof InterAdminTipo) {
            return $this->getTipoOptions([
                'parent_id_tipo = '.$this->nome->id_tipo
            ]);
        }
        if ($this->nome === 'all') {
            return $this->getTipoTreeOptions();
        }
        throw new Exception('Not implemented');
    }
    
    protected function getTipoOptions($where = [])
    {
        $tipos = $this->findTipos($where);
        $options = [];
        foreach ($tipos as $tipo) {
            $options[$tipo->id_tipo] = $tipo->getNome();
        }
        return $options;
    }
    
    protected function getRecordOptions($where = [])
    {
        $records = $this->findRecords($where);
        $options = [];
        foreach ($records as $record) {
            $options[$record->id] = $record->getStringValue();
        }
        return $options;
    }
    
    protected function findRecords($where = [])
    {
        $campos = $this->nome->getCampos();
        $valueColumn = array_key_exists('varchar_key', $campos) ? 'varchar_key' : 'id';
        return $this->nome->find([
            'fields' => $valueColumn,
            'fields_alias' => false,
            'class' => 'InterAdmin',
            'where' => array_merge(["deleted = ''"], $where),
            'order' => $valueColumn,
        ]);
    }
    
    protected function findTipos($where = [])
    {
        global $lang;
        
        return InterAdminTipo::findTipos([
            'fields' => ['nome'.$lang->prefix, 'parent_id_tipo'],
            'class' => 'InterAdminTipo',
            'use_published_filters' => true,
            'where' => $where,
            'order' => 'admin,ordem,nome'
        ]);
    }
    
    protected function getTipoTreeOptions($where = [])
    {
        $map = [];
        $tipos = $this->findTipos($where);
        
        foreach ($tipos as $tipo) {
            $map[$tipo->parent_id_tipo][] = $tipo;
        }
        $options = [];
        $this->addTipoTreeOptions($options, $map, 0);
        return $options;
    }
    
    protected function addTipoTreeOptions(&$options, $map, $parent_id_tipo, $level = 0)
    {
        if ($map[$parent_id_tipo]) {
            foreach ($map[$parent_id_tipo] as $tipo) {
                $options[$tipo->id_tipo] = ($level ? str_repeat('--', $level) . '> ' : '').$tipo->getNome();
                $this->addTipoTreeOptions($options, $map, $tipo->id_tipo, $level + 1);
            }
        }
    }
}
