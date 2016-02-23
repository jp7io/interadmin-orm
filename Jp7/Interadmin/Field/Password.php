<?php

class Jp7_Interadmin_Field_Password extends Jp7_Interadmin_Field_Base {
    
     public function getListValue($value) {
         return $value ? '******' : '';
     }
}
