<?php

namespace Jp7\InterAdmin;

/**
 * Generates the HTML output for a field based on its type, such as varchar, int or text.
 */
class FieldUtil
{
    public $id;
    public $type_id;
    public $field;

    /**
     * Construtor p￺úblico.
     *
     * @param array $field One row of the Type's field definitions [optional]
     *
     * @return
     */
    public function __construct($field = [])
    {
        $this->field = $field;
    }
    public function __toString(): string
    {
        return $this->field['type'];
    }

    /**
     * The xtra values of select_ fields which store types.
     *
     * @return array
     */
    public static function getSelectTypeXtras(): array
    {
        return ['S', 'X_tipos', 'ajax_tipos', 'radio_tipos'];
    }
    /**
     * The xtra values of special_ fields which store types.
     *
     * @return array
     */
    public static function getSpecialTypeXtras(): array
    {
        return ['tipos_multi', 'tipos'];
    }
    /**
     * The xtras of the special_ fields that store multiple records.
     *
     * @return array
     */
    public static function getSpecialMultiXtras(): array
    {
        return ['registros_multi', 'tipos_multi'];
    }
    /**
     * The field's value in the list header.
     *
     * @param array $field
     *
     * @return string
     */
    public static function getFieldHeader($field)
    {
        $key = $field['type'];
        if (strpos($key, 'special_') === 0 || strpos($key, 'func_') === 0) {
            if (!is_callable($field['name'])) {
                return 'Função '.$field['name'].' não encontrada.';
            }
            return call_user_func($field['name'], $field, '', 'header');
        }
        if (strpos($key, 'select_') === 0) {
            if ($field['label']) {
                return $field['label'];
            }
            // Type::getFields() resolves a select_'s `name` to a Type; only 'all' stays a string.
            return $field['name'] instanceof Type ? $field['name']->name : 'Tipos';
        }
        return $field['name'];
    }
}
