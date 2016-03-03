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
        } elseif ($this->nome instanceof InterAdminTipo) {
            return $this->nome->getFieldsValues('nome');
        } elseif ($this->nome === 'all') {
            return 'Tipos';
        }
        throw new Exception('Not implemented');
    }
    
    public function getCellHtml()
    {
        return $this->formatText(parent::getText(), true);
    }

    public function getText()
    {
        return $this->formatText(parent::getText(), false);
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
    
    protected function getFormerField()
    {
        return Former::select($this->getFormerName())
            ->options($this->getOptions())
            ->value(parent::getText());
    }
    
    protected function getOptions()
    {
        if ($this->relatesToTipo()) {
            if ($this->nome instanceof InterAdminTipo) {
                //
            } elseif ($this->nome === 'all') {
                //
            }
            return [];
        }
        
        $keyColumn = array_key_exists('varchar_key', $this->nome->getCampos()) ? 'varchar_key' : 'id';
        $records = $this->nome->find([
            'fields' => $keyColumn,
            'fields_alias' => false,
            'class' => 'InterAdmin',
            'order' => $keyColumn
        ]);
        $options = [];
        foreach ($records as $record) {
            $options[$record->id] = $record->getStringValue();
        }
        return $options;
    }
}
