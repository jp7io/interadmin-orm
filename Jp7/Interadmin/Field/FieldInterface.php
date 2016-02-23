<?php

namespace Jp7\Interadmin\Field;

use ADOFetchObj;

interface FieldInterface
{
    
    public function getHeaderHtml();

    public function getListHtml(ADOFetchObj $record);

    public function getHeaderValue();

    public function getListValue(ADOFetchObj $record);
    
}
