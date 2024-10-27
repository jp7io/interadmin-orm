<?php

namespace Jp7\Interadmin;

/**
 * Generates the HTML output for a field based on its type, such as varchar, int or text.
 */
class FieldUtil
{
    public $id;
    public $type_id;

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
    public function __toString()
    {
        return $this->field['tipo'];
    }

    /**
     * Retorna os xtra dos campos do tipo select_ que armazenam tipos.
     *
     * @return array
     */
    public static function getSelectTipoXtras()
    {
        return ['S', 'X_tipos', 'ajax_tipos', 'radio_tipos'];
    }
    /**
     * Retorna os xtra dos campos do tipo special_ que armazenam tipos.
     *
     * @return array
     */
    public static function getSpecialTipoXtras()
    {
        return ['tipos_multi', 'tipos'];
    }
    /**
     * Retorna os xtra dos campos do tipo special_ que armazenam m￺últiplos registros.
     *
     * @return array
     */
    public static function getSpecialMultiXtras()
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
    public static function getCampoHeader($campo)
    {
        $key = $campo['tipo'];
        if (strpos($key, 'special_') === 0 || strpos($key, 'func_') === 0) {
            if (is_callable($campo['nome'])) {
                return call_user_func($campo['nome'], $campo, '', 'header');
            } else {
                echo 'Função '.$campo['nome'].' não encontrada.';
            }
        } elseif (strpos($key, 'select_') === 0) {
            if ($campo['label']) {
                return $campo['label'];
            } elseif ($campo['nome'] instanceof Type) {
                return $campo['nome']->nome;
            } elseif ($campo['nome'] == 'all') {
                return 'Tipos';
            }
        } else {
            return $campo['nome'];
        }
    }
}
