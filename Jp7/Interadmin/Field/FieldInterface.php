<?php


interface Jp7_Interadmin_Field_FieldInterface {
    
    public function getHeaderHtml();

    public function getListHtml(ADOFetchObj $record);

    public function getHeaderValue();

    public function getListValue(ADOFetchObj $record);
    
}
