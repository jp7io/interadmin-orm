<?php

namespace Jp7\Interadmin\Field;

class FieldGroup
{
    /**
     * @var FieldInterface[]
     */
    protected $fields;
    
    public function add(FieldInterface $field)
    {
        $this->fields[] = $field;
    }
    
    public function getEditTag()
    {
        $html = '';
        if ($this->fields[0] instanceof TitField) {
            $html .= $this->fields[0]->openPanel();
        }
        
        $html .= implode(PHP_EOL, array_map(function ($field) {
            return ($field instanceof TitField) ? '' : $field->getEditTag();
        }, $this->fields));
        
        if ($this->fields[0] instanceof TitField) {
            $html .= $this->fields[0]->closePanel();
        }
        
        return $html;
    }
}