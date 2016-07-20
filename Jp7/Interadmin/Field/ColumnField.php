<?php

namespace Jp7\Interadmin\Field;

use Jp7\Interadmin\Type;

/**
 * @property string $tipo
 * @property Type|string $nome
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
        return $this->getValue();
    }
    
    public function getEditTag()
    {
        $input = parent::getEditTag();
        if ($this->ajuda) {
            $input->help($this->ajuda);
        }
        // Title is just for information
        $input->getLabel()->setAttribute('title', $this->nome_id.' ('.$this->tipo.', xtra: '.$this->xtra.')');
        $input->onGroupAddClass($this->id);
        $input->onGroupAddClass($this->nome_id.'-group');
        if ($this->separador) {
            $input->onGroupAddClass('has-separator');
        }
        $this->handleReadonly($input);
        return $input;
    }
        
    protected function getFormerName()
    {
        return $this->tipo.(is_null($this->index) ? '' : '['.$this->index.']');
    }
    
    protected function getFormerId()
    {
        return $this->nome_id.(is_null($this->index) ? '' : '_'.$this->index);
    }
    
    protected function getValue()
    {
        $column = $this->tipo;
        $value = $this->record->$column;
        if (!$this->record->id && !$value) {
            $value = $this->getDefaultValue();
        }
        return $value;
    }
    
    protected function getDefaultValue()
    {
        return $this->default;
    }
    
    protected function handleReadonly($input)
    {
        if ($this->isReadonly()) {
            $input->disabled();
        }
    }
    
    protected function isReadonly()
    {
        return $this->readonly || !$this->hasPermissions();
    }
    
    protected function hasPermissions()
    {
        global $s_user;
        if (!$this->permissoes || $s_user['sa']) {
            return true;
        }
        if ((string) $this->permissoes === (string) $s_user['tipo']) {
            // By select with the user type, used by CI Intercambio
            return true;
        }
        if ($this->permissoes === 'admin' && $s_user['admin']) {
            return true;
        }
        return false;
    }
    
    public function getRules()
    {
        $rules = [];
        if ($this->isReadonly()) {
            $rules[$this->getRuleName()][] = 'in:'.$this->getValue();
        } elseif ($this->obrigatorio) {
            $rules[$this->getRuleName()][] = 'required';
        }
        return $rules;
    }
}
