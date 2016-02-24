<?php

namespace Jp7\Interadmin\Field;

class File extends ColumnField
{
    protected $name = 'file';

    public function getCellHtml(\ADOFetchObj $record) {
        $url = $this->getCellText($record);
        return interadmin_arquivos_preview(
            $url,
            '', // alt
            true, // presrc
            true // icon_small
        );
    }

}
