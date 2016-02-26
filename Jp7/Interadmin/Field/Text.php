<?php

namespace Jp7\Interadmin\Field;

class Text extends ColumnField
{
    protected $name = 'text';
    const XTRA_TEXT = '0';
    const XTRA_HTML = 'S';
    const XTRA_HTML_LIGHT = 'html_light';
    
    public function getCellText(\ADOFetchObj $record) {
        $text = parent::getCellText($record);
        if ($this->campo['xtra'] === self::XTRA_HTML || $this->campo['xtra'] === self::XTRA_HTML_LIGHT) {
            $text = strip_tags($text);
        }
        return $text;
    }
}


