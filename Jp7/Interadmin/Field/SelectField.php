<?php

namespace Jp7\Interadmin\Field;

use InterAdminTipo;

class SelectField extends ColumnField
{
    const XTRA_BY_TYPE = 'S';
    protected $name = 'select';
    
    public function getLabel()
    {
        if ($this->campo['label']) {
            return $this->campo['label'];
        } elseif ($this->campo['nome'] instanceof InterAdminTipo) {
            return $this->campo['nome']->getFieldsValues('nome');
        } elseif ($this->campo['nome'] == 'all') {
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
        list($value, $status) = $this->getValueAndStatus($id);
        if ($html) {
            return ($status ? e($value) : '<del>'.e($value).'</del>');
        }
        return $value.($status ? '' : ' [deleted]');
    }
    
    protected function getValueAndStatus($id)
    {
        if (!$id) {
            return ['', true];
        }
        if ($this->hasTipoRelationship()) {
            return [interadmin_tipos_nome($id), true];
        }
        $related = $this->campo['nome']->findById($id);
        if ($related) {
            return [$related->getStringValue(), $related->isPublished()];
        }
        return [$id, false];
    }
    
    protected function hasTipoRelationship()
    {
        return in_array($this->campo['xtra'], [self::XTRA_BY_TYPE, 'ajax_tipos', 'radio_tipos']);
    }
}
