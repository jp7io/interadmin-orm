<?php

namespace Jp7\Interadmin\Field;

use ADOFetchObj;

class SelectMulti extends Select
{
    protected $name = 'select_multi';
    /*
     * "0" Por Registros
     * "S" Por Tipos
     * "X" Com Busca
     */
    
    public function getCellHtml(ADOFetchObj $record) {
        return implode(',<br>', $this->getTextArray($record, true));
    }
    
    public function getCellText(ADOFetchObj $record)
    {
        return implode(",\n", $this->getTextArray($record, false));
    }
    
    protected function getTextArray(ADOFetchObj $record, $html)
    {
        $ids = jp7_explode(',', ColumnField::getCellText($record));
        $array = [];
        foreach ($ids as $id) {
            $array[] = $this->formatText($id, $html);
        }
        return $array;
    }
}
