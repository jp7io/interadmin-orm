<?php

namespace Jp7\Former;

use Former\Former as OriginalFormer;
use Debugbar;
use Jp7\Interadmin\Record;
use Jp7\Interadmin\FieldUtil;
use Lang;

/**
 * Add InterAdmin settings on former automatically.
 */
class FormerExtension
{
    use RowTrait, DecoratorTrait;

    private $model;
    private $rules;
    private $former;

    public function __construct(OriginalFormer $former)
    {
        // Send missing validations to Debugbar
        if ($errors = app()['session']->get('errors')) {
            if (class_exists('Debugbar')) {
                Debugbar::error($errors->all());
            }
        }

        $this->former = $former;
    }

    /**
     * Forward calls to Former and decorate fields.
     */
    public function __call($method, $arguments)
    {
        $result = call_user_func_array([$this->former, $method], $arguments);

        if ($result instanceof \Former\Form\Form) {
            $this->decorateFormInterAdmin($result);
        } elseif ($result instanceof \Former\Traits\Field) {
            $this->decorateField($result); // DecoratorTrait
            $this->decorateFieldInterAdmin($result);
        }

        return $result;
    }

    public function &__get($property)
    {
        return $this->former->$property;
    }

    public function __set($property, $value)
    {
        $this->former->$property = $value;
    }

    public function populate($model)
    {
        if ($model instanceof Record) {
            $this->model = $model;
            $this->rules = $model->getRules();
        }

        return $this->former->populate($model);
    }
  
    public function close()
    {
        $this->model = null;

        return $this->former->close();
    }
    
    /**
     * Add "rules" and "action" from InterAdmin.
     */
    private function decorateFormInterAdmin($form)
    {
        if ($this->model) {
            $form->rules($this->rules);
            if ($this->model->getRoute('store')) {
                $form->action($this->model->getUrl('store'));
            }
        }
    }

    /**
     * Set "label" and "options" from InterAdmin.
     */
    private function decorateFieldInterAdmin($field)
    {
        if (!$this->model || (!$alias = $field->getName())) {
            return;
        }

        $type = $this->model->getType();
        $campos = $type->getCampos();
        $aliases = array_flip($type->getCamposAlias());

        if (empty($aliases[$alias])) {
            return;
        }

        $name = $aliases[$alias];
        $campo = $campos[$name];

        // Set label
        if (!Lang::has('validation.attributes.'.$alias)) {
            $label = FieldUtil::getCampoHeader($campo);
            $field->label($label);
        }

        // Populate options
        if (starts_with($name, 'select_')) {
            $this->populateOptions($field, $campo['nome']);
        }

        if (isset($this->rules[$alias])) {
            if (in_array('name_and_surname', $this->rules[$alias])) {
                $field->pattern('\S+ +\S.*')
                    ->title('Preencha nome e sobrenome');
            }
        }
    }

    private function populateOptions($field, $campoType)
    {
        if ($field->getType() === 'select') {
            $options = [];
            foreach ($campoType->records()->all() as $record) {
                $options[$record->id] = $record->getName();
            }
            $field->options($options);
        } elseif ($field->getType() === 'radios') {
            $radios = [];
            foreach ($campoType->records()->all() as $record) {
                $radios[$record->getName()] = ['value' => $record->id];
            }
            $field->radios($radios);
        }
    }
}
