<?php

namespace Jp7\Interadmin\Field;

class FileField extends ColumnField
{
    protected $name = 'file';

    public function getCellHtml()
    {
        $url = $this->getText();
        return interadmin_arquivos_preview(
            $url,
            '', // alt
            true, // presrc
            true // icon_small
        );
    }

}
