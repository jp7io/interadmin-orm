<?php

namespace Jp7\Interadmin\Field;

use Former;

class TextField extends ColumnField
{
    protected $id = 'text';
    const XTRA_TEXT = '0';
    const XTRA_HTML = 'S';
    const XTRA_HTML_LIGHT = 'html_light';
    
    public function getText()
    {
        $text = $this->getValue();
        if (in_array($this->xtra, [self::XTRA_HTML, self::XTRA_HTML_LIGHT])) {
            $text = strip_tags($text);
        }
        return $text;
    }
    
    protected function getFormerField()
    {
        return Former::textarea($this->getFormerName())
            ->value($this->getValue())
            ->id($this->tipo.'_'.$this->index)
            ->data_html($this->xtra ?: false);
    }
}
