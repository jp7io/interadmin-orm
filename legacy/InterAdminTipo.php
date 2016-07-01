<?php

use Jp7\Interadmin\Type;
use Jp7\Interadmin\Record;

/**
 * JP7's PHP Functions.
 *
 * Contains the main custom functions and classes.
 *
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 *
 * @category Jp7
 */

/**
 * Class which represents records on the table interadmin_{client name}_tipos.
 *
 * @deprecated use Type instead
 */
class InterAdminTipo extends Type
{
    const DEFAULT_NAMESPACE = '';
    
    /**
     * Retrieves the unique record which have this id.
     *
     * @param int   $id      Search value.
     * @param array $options
     *
     * @return InterAdmin First InterAdmin object found.
     */
    public function findById($id, $options = [])
    {
        $options['where'][] = 'id = '.intval((string) $id);
        return $this->deprecatedFindFirst($options);
    }
    
    /**
     * @param array $options Default array of options. Available keys: fields, where, order, group, class.
     *
     * @return InterAdmin First InterAdmin object found.
     */
    public function findFirst($options = [])
    {
        $result = $this->find(['limit' => 1] + $options);
        return reset($result);
    }
    
    /**
     * @param array $options Default array of options. Available keys: fields, where, order, group, limit, class.
     *
     * @return InterAdmin[] Array of InterAdmin objects.
     */
    public function find($options = [])
    {
        return $this->deprecatedFind($options)->all();
    }
   
    /**
     * Returns the number of InterAdmins using COUNT(id).
     *
     * @param array $options Default array of options. Available keys: where.
     *
     * @return int Count of InterAdmins found.
     */
    public function count($options = [])
    {
        if ($options['group'] == 'id') {
            // O COUNT() precisa trazer a contagem total em 1 linha
            // Caso exista GROUP BY id, ele traria em várias linhas
            // Esse é um tratamento especial apenas para o ID
            $options['fields'] = ['COUNT(DISTINCT id) AS count_id'];
            unset($options['group']);
        } elseif ($options['group']) {
            // Se houver GROUP BY com outro campo, retornará a contagem errada
            throw new Exception('GROUP BY is not supported when using count().');
        } else {
            $options['fields'] = ['COUNT(id) AS count_id'];
        }
        $retorno = $this->deprecatedFindFirst($options);
        return intval($retorno->count_id);
    }
    
    /**
     * Retrieves the children of this InterAdminTipo.
     *
     * @param array $options Default array of options. Available keys: fields, where, order, class.
     *
     * @return array Array of InterAdminTipo objects.
     */
    public function getChildren($options = [])
    {
        return $this->deprecatedGetChildren($options);
    }
    
    /**
     * Returns the full url for this InterAdminTipo.
     *
     * @return string
     */
    public function getUrl()
    {
        if ($this->_url) {
            return $this->_url;
        }
        global $config, $implicit_parents_names, $seo, $lang;
        $url = '';
        $url_arr = '';
        $parent = $this;
        while ($parent) {
            if (!isset($parent->nome)) {
                $parent->getFieldsValues('nome');
            }
            if ($seo) {
                if (!in_array($parent->nome, (array) $implicit_parents_names)) {
                    $url_arr[] = toSeo($parent->nome);
                }
            } else {
                if (toId($parent->nome)) {
                    $url_arr[] = toId($parent->nome);
                }
            }
            $parent = $parent->getParent();
            if ($parent instanceof InterAdmin) {
                $parent = $parent->getTipo();
            }
        }
        $url_arr = array_reverse((array) $url_arr);
        if ($seo) {
            $url = $config->url.$lang->path.jp7_implode('/', $url_arr);
        } else {
            $url = $config->url.$lang->path_url.implode('_', $url_arr);
            $pos = strpos($url, '_');
            if ($pos) {
                $url = substr_replace($url, '/', $pos, 1);
            }
            $url .= (count($url_arr) > 1) ? '.php' : '/';
        }
        return $this->_url = $url;
    }
    
    /**
     * Returns all records having an InterAdminTipo that uses this as a model (model_id_tipo).
     *
     * @param array $options [optional]
     *
     * @return InterAdmin[]
     */
    public function getInterAdminsUsingThisModel($options = [])
    {
        $this->_prepareInterAdminsOptions($options, $optionsInstance);
        $tipos = $this->getTiposUsingThisModel();
        $options['where'][] = 'id_tipo IN ('.implode(',', $tipos).')';
        $rs = $this->_executeQuery($options);
        $records = [];
        foreach ($rs as $row) {
            $record = InterAdmin::getInstance($row->id, $optionsInstance, $tipos[$row->id_tipo]);
            $this->_getAttributesFromRow($row, $record, $options);
            $records[] = $record;
        }
        return $records;
    }
    
    public function getFieldsValues($fields, $forceAsString = false, $fieldsAlias = false)
    {
        if ($forceAsString) {
            throw new Exception('Not implemented');
        }
        if (is_array($fields)) {
            $retorno = (object) [];
            // returns only the fields requested on $fields
            foreach ($fields as $key => $value) {
                if (is_array($value)) {
                    $retorno->$key = $this->$key;
                } else {
                    $retorno->$value = $this->$value;
                }
            }
            return $retorno;
        }
        return $this->$fields;
    }
    
     /**
     * Gets the first child.
     *
     * @param array $options [optional]
     *
     * @return InterAdminTipo
     */
    public function getFirstChild($options = [])
    {
        $retorno = $this->getChildren(['limit' => 1] + $options);
        return $retorno[0];
    }
    
    /**
     * Retrieves the first child of this InterAdminTipo with the given "model_id_tipo".
     *
     * @param string|int $model_id_tipo
     * @param array      $options       Default array of options. Available keys: fields, where, order, class.
     *
     * @return InterAdminTipo
     */
    public function getFirstChildByModel($model_id_tipo, $options = [])
    {
        $retorno = $this->getChildrenByModel($model_id_tipo, ['limit' => 1] + $options);
        return $retorno[0];
    }
    /**
     * Retrieves the first child of this InterAdminTipo with the given "nome".
     *
     * @param array $options Default array of options. Available keys: fields, where, order, class.
     *
     * @return InterAdminTipo
     */
    public function getFirstChildByNome($nome, $options = [])
    {
        $options['where'][] = "nome = '".$nome."'";
        return $this->getFirstChild($options);
    }
    /**
     * Retrieves the children of this InterAdminTipo which have the given model_id_tipo.
     *
     * @param array $options Default array of options. Available keys: fields, where, order, class.
     *
     * @return Array of InterAdminTipo objects.
     */
    public function getChildrenByModel($model_id_tipo, $options = [])
    {
        $options['where'][] = "model_id_tipo = '".$model_id_tipo."'";
        // Necessário enquanto algumas tabelas ainda tem esse campo numérico
        $options['where'][] = "model_id_tipo != '0'";
        return $this->getChildren($options);
    }
    
    /**
     * Creates a record with id_tipo, mostrar, date_insert and date_publish filled.
     *
     * @param array $attributes Attributes to be merged into the new record.
     *
     * @return InterAdmin
     */
    public function createInterAdmin(array $attributes = [])
    {
        return $this->deprecated_createInterAdmin($attributes);
    }
}
