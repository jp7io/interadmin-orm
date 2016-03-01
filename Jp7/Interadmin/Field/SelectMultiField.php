<?php

namespace Jp7\Interadmin\Field;

class SelectMultiField extends SelectField
{
    protected $name = 'select_multi';
    /*
     * "0" Por Registros
     * "S" Por Tipos
     * "X" Com Busca
     */
    
    public function getCellHtml()
    {
        return implode(',<br>', $this->getTextArray(true));
    }
    
    public function getText()
    {
        return implode(",\n", $this->getTextArray(false));
    }
    
    protected function getTextArray($html)
    {
        $ids = jp7_explode(',', ColumnField::getText());
        $array = [];
        foreach ($ids as $id) {
            $array[] = $this->formatText($id, $html);
        }
        return $array;
    }
}
