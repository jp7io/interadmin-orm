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
        } else {
            $html .= '<div class="panel panel-default">'.
                        '<div class="panel-body">';
            
        }
        
        $html .= implode(PHP_EOL, array_map(function ($field) {
            // (string) is needed to force render
            // Former wants object to be created and rendered before creating next object
            // Without (string) the group doesn't get the class "required"
            return ($field instanceof TitField) ? '' : (string) $field->getEditTag();
        }, $this->fields));
        
        if ($this->fields[0] instanceof TitField) {
            $html .= $this->fields[0]->closePanel();
        } else {
            $html .= '</div>'.
                        '</div>';
        }
        
        return $html;
    }
}