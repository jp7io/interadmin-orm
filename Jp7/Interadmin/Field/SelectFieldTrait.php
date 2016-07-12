<?php

namespace Jp7\Interadmin\Field;

use Jp7\Interadmin\Record;
use Jp7\Interadmin\Type;
use UnexpectedValueException;
use InterAdminOptions;
use InterAdminOptionsTipos;

trait SelectFieldTrait
{
    public function getLabel()
    {
        if ($this->label) {
            return $this->label;
        }
        if ($this->nome instanceof Type) {
            return $this->nome->getName();
        }
        if ($this->nome === 'all') {
            return 'Tipos';
        }
        throw new UnexpectedValueException('Not implemented');
    }
        
    protected function formatText($id, $html)
    {
        list($value, $status) = $this->valueAndStatus($id);
        if ($html) {
            return ($status ? e($value) : '<del>'.e($value).'</del>');
        }
        return $value.($status ? '' : ' [unpublished]');
    }
    
    protected function valueAndStatus($id)
    {
        if (!$id) {
            return ['', true];
        }
        if ($this->hasTipo()) {
            return [interadmin_tipos_nome($id), true];
        }
        /* @var $related Record */
        $related = $this->nome->records()->find($id);
        if ($related) {
            return [$related->getStringValue(), $related->isPublished()];
        }
        return [$id, false];
    }
        
    protected function getDefaultValue()
    {
        if ($this->default && !is_numeric($this->default) && $this->nome instanceof Type) {
            $defaultArr = [];
            foreach (array_filter(explode(',', $this->default)) as $idString) {
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
            return $this->toOptions($this->records()->get());
        }
        if ($this->nome instanceof Type) {
            return $this->toOptions($this->tipos()->get());
        }
        if ($this->nome === 'all') {
            return $this->toTreeOptions($this->tipos()->get());
        }
        throw new UnexpectedValueException('Not implemented');
    }
    
    protected function records()
    {
        $camposCombo = $this->nome->getCamposCombo();
        $query = new InterAdminOptions($this->nome);
        $query->setOptionsArray([
            'fields' => $camposCombo,
            'fields_alias' => false,
            'where' => ["deleted = ''"],
            'order' => implode(', ', $camposCombo)
        ]);
        
        if ($this->where) {
            // From xtra_disabledfields
            $query->whereRaw('1=1'.$this->where);
        }
        return $query;
    }
        
    protected function tipos()
    {
        global $lang;
        
        $query = new InterAdminOptionsTipos(new Type);
        $query->setOptionsArray([
            'fields' => ['nome'.$lang->prefix, 'parent_id_tipo'],
            'fields_alias' => false,
            'use_published_filters' => true,
            'where' => [],
            'order' => 'admin,ordem,nome'.$lang->prefix
        ]);
        // only children tipos
        if ($this->nome instanceof Type) {
            $query->where('parent_id_tipo', $this->nome->id_tipo);
        }
        return $query;
    }
    
    protected function toOptions(array $array)
    {
        $options = [];
        if ($array[0] instanceof Type) {
            foreach ($array as $tipo) {
                $options[$tipo->id_tipo] = $tipo->getName();
            }
        } elseif ($array[0] instanceof Record) {
            foreach ($array as $record) {
                $options[$record->id] = $record->getStringValue();
            }
        } elseif ($array) {
            throw new UnexpectedValueException('Should be an array of Record or Type');
        }
        return $options;
    }
    
    protected function toTreeOptions(array $tipos)
    {
        $map = [];
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
                $prefix = ($level ? str_repeat('--', $level) . '> ' : ''); // ----> Nome
                $options[$tipo->id_tipo] = $prefix.$tipo->getName();
                $this->addTipoTreeOptions($options, $map, $tipo->id_tipo, $level + 1);
            }
        }
    }
}
