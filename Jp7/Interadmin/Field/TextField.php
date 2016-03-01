<?php

namespace Jp7\Interadmin\Field;

use ADOFetchObj;

class TextField extends ColumnField
{
    protected $name = 'text';
    const XTRA_TEXT = '0';
    const XTRA_HTML = 'S';
    const XTRA_HTML_LIGHT = 'html_light';
    
    public function getText()
    {
        $text = parent::getText();
        if ($this->campo['xtra'] === self::XTRA_HTML || $this->campo['xtra'] === self::XTRA_HTML_LIGHT) {
            $text = strip_tags($text);
        }
        return $text;
    }
}
