<?php

namespace Jp7\Interadmin\Field;

use Former;

/**
 * @property string $tipo
 * @property InterAdminTipo|string $nome
 * @property string $ajuda
 * @property string|int $tamanho
 * @property string|bool $obrigatorio    'S' or ''
 * @property string $separador
 * @property string $xtra
 * @property string|bool $lista     'S' or ''
 * @property numeric $orderby
 * @property string|bool $combo     'S' or ''
 * @property string|bool $readonly  'S' or ''
 * @property string|bool $form      'S' or ''
 * @property string $label
 * @property mixed $permissoes
 * @property string $default
 * @property string $nome_id
 */
class ColumnField extends BaseField
{
    /**
     * @var array
     */
    protected $campo;
    /**
     * @var int
     */
    protected $i = 0;
    
    /**
     * @param array $campo
     */
    public function __construct(array $campo)
    {
        $this->campo = $campo;
    }
    
    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (!isset($this->campo[$name])) {
            return;
        }
        return $this->campo[$name];
    }
    
    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->campo[$name]);
    }
    
    /**
     * @param string $name
     * @return void
     */
    public function __unset($name)
    {
        unset($this->campo[$name]);
    }
    
    public function setIndex($i)
    {
        $this->i = $i;
    }

    public function getHeaderTag()
    {
        return parent::getHeaderTag()->title($this->tipo);
    }
    
    public function getLabel()
    {
        return $this->nome;
    }

    public function getText()
    {
        $column = $this->tipo;
        return $this->record->$column;
    }
    
    public function getEditTag()
    {
        $input = parent::getEditTag();
        if ($this->ajuda) {
            $input->help($this->ajuda);
        }
        $input->getLabel()->setAttribute('title', $this->tipo.' (xtra: '.$this->xtra.')');
        $input->onGroupAddClass($this->id);
        if ($this->separador) {
            $input->onGroupAddClass('has-separator');
        }
        return $input;
    }
    
    protected function getFormerField()
    {
        return Former::text($this->getFormerName())
            ->value($this->getText());
    }
    
    protected function getFormerName()
    {
        return $this->tipo.'['.$this->i.']';
    }
    
    public function getRules()
    {
        $rules = [];
        if ($this->obrigatorio) {
            $rules[$this->getFormerName()][] = 'required';
        }
        return $rules;
    }
}
