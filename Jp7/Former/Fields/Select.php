<?php

namespace Jp7\Former\Fields;

class Select extends \Former\Form\Fields\Select
{
    public function options($list, $selected = null, $valuesAsKeys = false)
    {
        if ($list instanceof \Jp7\Interadmin\Query\BaseQuery) {
            throw new \Exception('Use ->lists(attr_name,id)');
        }
        
        $this->children = []; // clear previous options
        return parent::options($list, $selected, $valuesAsKeys);
    }
    
    public function render()
    {
        // Use "Selecione" as default placeholder
        if ($this->getPlaceholder() === null || $this->getPlaceholder() === false) {
            if (empty($this->attributes['multiple'])) {
                $this->placeholder('Selecione');
            }
        }
        // Remove "disabled" from placeholder <option>
        $option = '<option value=""';
        return str_replace($option.' disabled="disabled"', $option, parent::render());
    }
}
