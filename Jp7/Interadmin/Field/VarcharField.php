<?php

namespace Jp7\Interadmin\Field;

use HtmlObject\Element;
use DB;

class VarcharField extends ColumnField
{
    protected $id = 'varchar';
    /*
    [0] = "Normal";
    ['id'] = "ID";
    ['id_email'] = "ID E-Mail";
    ['email'] = "E-Mail";
    ['num'] = "Número";
    ['cep'] = "CEP";
    ['cpf'] = "CPF";
    ['telefone'] = "Telefone";
    ['ll']="Latitude e Longitude";
    ['url'] = "URL";
    ['cor']="Cor Hexadecimal";
    */
    public function getRules()
    {
        $rules = parent::getRules();
        $name = $this->getRuleName();
        
        if ($this->isUnique()) {
            $rules[$name][] = $this->getUniqueRule();
        }
        if ($this->isEmail()) {
            $rules[$name][] = 'email';
        } elseif ($this->isNumeric()) {
            $rules[$name][] = 'pseudonumeric';
        } elseif ($this->isCep()) {
            $rules[$name][] = 'cep';
        } elseif ($this->isCpf()) {
            $rules[$name][] = 'cpf';
        }
        if ($this->tamanho) {
            $rules[$name][] = 'max:'.$this->tamanho;
        }
        return $rules;
    }
    
    protected function isUnique()
    {
        return $this->xtra === 'id' || $this->xtra === 'id_email' || $this->xtra === 'cpf';
    }
    
    protected function getUniqueRule()
    {
        // unique:table,column,except,idColumn
        $table = str_replace(DB::getTablePrefix(), '', $this->type->getInterAdminsTableName()); // FIXME
        return 'unique:'.implode(',', [
            $table,             // table
            $this->tipo,        // column
            $this->record->id,  // except
            'id',               // idColumn
            // WHERE:
            'id_tipo', $this->type->id_tipo
        ]);
    }
    
    protected function isEmail()
    {
        return $this->xtra === 'email' || $this->xtra === 'id_email';
    }
    
    protected function isNumeric()
    {
        return $this->xtra === 'num';
    }
    
    protected function isTel()
    {
        return $this->xtra === 'telefone';
    }
    
    protected function isCpf()
    {
        return $this->xtra === 'cpf';
    }
    
    protected function isCep()
    {
        return $this->xtra === 'cep';
    }
    
    protected function isColor()
    {
        return $this->xtra === 'cor';
    }
    
    protected function getFormerField()
    {
        $input = parent::getFormerField();
        if ($this->isEmail()) {
            $input->type('email');
        } elseif ($this->isNumeric()) {
            // Remove Former HTML5 Validation
            // Because we accept numbers in Brazilian format: 1,99 instead of 1.99
            $input->pattern('[+-]?[0-9]+([0-9,.]*[0-9]+)?');
        } elseif ($this->isTel()) {
            $input->type('tel');
        } elseif ($this->isColor()) {
            $input->prepend($this->getColorpickerHtml());
        }
        return $input->data_type($this->xtra ?: false);
    }
    
    public function hasMassEdit()
    {
        return true;
    }
    
    protected function getColorpickerHtml()
    {
        return '<div class="colorpicker-button"></div>';
    }
}
