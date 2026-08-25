<?php

namespace Jp7\Interadmin;

/**
 * Generates the HTML output for a field based on its type, such as varchar, int or text.
 */
class FieldUtil
{
    public $id;
    public $id_tipo;
    public $field;

    /**
     * Construtor p￺úblico.
     *
     * @param array $field Formato dos campos do Type [optional]
     *
     * @return
     */
    public function __construct($field = [])
    {
        $this->field = $field;
    }
    public function __toString(): string
    {
        return $this->field['tipo'];
    }

    /**
     * Retorna os xtra dos campos do tipo select_ que armazenam tipos.
     *
     * @return array
     */
    public static function getSelectTipoXtras(): array
    {
        return ['S', 'X_tipos', 'ajax_tipos', 'radio_tipos'];
    }
    /**
     * Retorna os xtra dos campos do tipo special_ que armazenam tipos.
     *
     * @return array
     */
    public static function getSpecialTipoXtras(): array
    {
        return ['tipos_multi', 'tipos'];
    }
    /**
     * Retorna os xtra dos campos do tipo special_ que armazenam m￺últiplos registros.
     *
     * @return array
     */
    public static function getSpecialMultiXtras(): array
    {
        return ['registros_multi', 'tipos_multi'];
    }
    /**
     * Retorna o valor do campo no header (cabeçalho da listagem).
     *
     * @param array $campo
     *
     * @return string
     */
    public static function getFieldHeader($campo)
    {
        $key = $campo['tipo'];
        if (strpos($key, 'special_') === 0 || strpos($key, 'func_') === 0) {
            if (!is_callable($campo['nome'])) {
                return 'Função '.$campo['nome'].' não encontrada.';
            }
            return call_user_func($campo['nome'], $campo, '', 'header');
        }
        if (strpos($key, 'select_') === 0) {
            if ($campo['label']) {
                return $campo['label'];
            }
            // Type::getFields() resolves a select_'s `nome` to a Type; only 'all' stays a string.
            return $campo['nome'] instanceof Type ? $campo['nome']->nome : 'Tipos';
        }
        return $campo['nome'];
    }
}
