<?php

namespace Jp7\Interadmin\Field;

use InterAdminTipo;
use Former;

class SelectField extends ColumnField
{
    protected $id = 'select';
    
    const XTRA_RECORD = '0';
    const XTRA_RECORD_RADIO = 'radio';
    const XTRA_RECORD_AJAX = 'ajax';
    const XTRA_TYPE = 'S';
    const XTRA_TYPE_RADIO = 'radio_tipos';
    const XTRA_TYPE_AJAX = 'ajax_tipos';
    
    public function getLabel()
    {
        if ($this->label) {
            return $this->label;
        }
        if ($this->nome instanceof InterAdminTipo) {
            return $this->nome->getFieldsValues('nome');
        }
        if ($this->nome === 'all') {
            return 'Tipos';
        }
        throw new Exception('Not implemented');
    }
    
    public function getCellHtml()
    {
        return $this->formatText($this->getValue(), true);
    }

    public function getText()
    {
        return $this->formatText($this->getValue(), false);
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
        if ($this->relatesToTipo()) {
            return [interadmin_tipos_nome($id), true];
        }
        $related = $this->nome->findById($id);
        if ($related) {
            return [$related->getStringValue(), $related->isPublished()];
        }
        return [$id, false];
    }
    
    protected function relatesToTipo()
    {
        return in_array($this->xtra, [self::XTRA_TYPE, self::XTRA_TYPE_AJAX, self::XTRA_TYPE_RADIO]);
    }
    
    protected function isRadio()
    {
        return in_array($this->xtra, [self::XTRA_RECORD_RADIO, self::XTRA_TYPE_RADIO]);
    }
    
    protected function getFormerField()
    {
        if ($this->isRadio()) {
            return Former::radios($this->getFormerName())
                ->radios($this->getRadios())
                ->check($this->getValue());
        }
        return Former::select($this->getFormerName())
            ->options($this->getOptions())
            ->value($this->getValue());
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
        if (!$this->relatesToTipo()) {
            return $this->getRecordOptions();
        }
        if ($this->nome instanceof InterAdminTipo) {
            return $this->getChildrenTipoOptions();
        }
        if ($this->nome === 'all') {
            // interadmin_tipos_combo();
            return [];
        }
        throw new Exception('Not implemented');
    }
    
    protected function getRadios()
    {
        $radios = [];
        if (!$this->obrigatorio) {
            $radios['(nenhum)'] = ['value' => '', 'checked' => true];
        }
        foreach ($this->getOptions() as $key => $value) {
            $radios[$value] = ['value' => $key];
        }
        return $radios;
    }
    
    protected function getChildrenTipoOptions()
    {
        $children = $this->nome->getChildren([
            'where' => "deleted_tipo = ''"
        ]);
        $options = [];
        foreach ($children as $child) {
            $options[$child->id_tipo] = $child->getNome();
        }
        return $options;
    }
    
    protected function getRecordOptions()
    {
        $campos = $this->nome->getCampos();
        $valueColumn = array_key_exists('varchar_key', $campos) ? 'varchar_key' : 'id';
        $records = $this->nome->find([
            'fields' => $valueColumn,
            'fields_alias' => false,
            'class' => 'InterAdmin',
            'where' => ["deleted = ''"],
            'order' => $valueColumn
        ]);
        $options = [];
        foreach ($records as $record) {
            $options[$record->id] = $record->getStringValue();
        }
        return $options;
    }
}
