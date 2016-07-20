<?php

namespace Jp7\Former;

use Former\Former as OriginalFormer;
use Log;
use Jp7\Interadmin\Record;
use Jp7\Interadmin\EloquentProxy;
use Jp7\Interadmin\FieldUtil;
use Lang;
use UnexpectedValueException;
use BadMethodCallException;
use DateTime;

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
        // Send missing validations to Log
        if (getenv('APP_DEBUG')) {
            if ($errors = app()['session']->get('errors')) {
                Log::notice('Validation error', $errors->all());
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
        if (!$model instanceof Record) {
            return $this->former->populate($model);
        }
        
        $this->model = $model;
        $this->rules = $model->getRules();

        $proxy = new EloquentProxy;
        $proxy->setRecord($model);
        return $this->former->populate($proxy);
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
        if (str_contains($alias, '[')) {
            // Nested models: socios[0][nome]
            $aliasParts = explode('.', $this->toDots($alias));
            if (count($aliasParts) !== 3) {
                return;
            }
            list($childName, $i, $childAlias) = $aliasParts;
            try {
                $childType = $this->model->$childName()->type();
                $this->decorateFieldByTypeAndAlias($field, $childType, $childAlias);
            } catch (BadMethodCallException $e) {
                // no child type with this name
            }
            return;
        }
        $type = $this->model->getType();
        $this->decorateFieldByTypeAndAlias($field, $type, $alias);
    }

    private function decorateFieldByTypeAndAlias($field, $type, $alias)
    {
        $campos = $type->getCampos();
        $aliases = array_flip($type->getCamposAlias());

        if (empty($aliases[$alias])) {
            return;
        }

        $name = $aliases[$alias];
        $campo = $campos[$name];

        // Set label
        if (!Lang::has('validation.attributes.'.$alias)) {
            // FIXME FieldUtil::getCampoHeader roda funcoes special_
            $label = $campo['label'] ?: FieldUtil::getCampoHeader($campo);
            $field->label($label);
        }

        // Populate options
        if (starts_with($name, 'select_')) {
            $this->populateOptions($field, $campo['nome']);
        }
        // Fix date format
        if ($field->getType() === 'date' && $field->getValue() instanceof DateTime) {
            $field->setValue($field->getValue()->format('Y-m-d'));
        }
        
        if (isset($this->rules[$alias])) {
            if (in_array('name_and_surname', $this->rules[$alias])) {
                $field->pattern('\S+ +\S.*')
                    ->title('Preencha nome e sobrenome');
            }
        }
    }

    protected function toDots($name)
    {
        $name = str_replace( // same replace Laravel and Former do
            ['[', ']'],
            ['.', ''],
            $name
        );
        return trim($name, '.');
    }

    private function populateOptions($field, $campoType)
    {
        if ($field->getType() === 'select') {
            $field->options(function () use ($campoType) {
                $options = [];
                foreach ($campoType->records()->get() as $record) {
                    $options[$record->id] = $record->getName();
                }
                return $options;
            });
        } elseif ($field->getType() === 'radios') {
            $radios = [];
            foreach ($campoType->records()->get() as $record) {
                if (!$name = $record->getName()) {
                    throw new UnexpectedValueException('getName() returned empty value for Record ID: '.$record->id);
                }
                $radios[$name] = ['value' => $record->id];
            }
            $field->radios($radios);
        }
    }
}
