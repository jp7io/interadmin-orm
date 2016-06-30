<?php

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
 * Class which represents records on the table interadmin_{client name}.
 *
 * @deprecated use Record instead
 */
class InterAdmin extends Record
{
    const DEFAULT_NAMESPACE = '';
    
    /**
     * Magic method calls.
     *
     * Available magic methods:
     * - create{Child}(array $attributes = array())
     * - get{Children}(array $options = array())
     * - getFirst{Child}(array $options = array())
     * - get{Child}ById(int $id, array $options = array())
     * - get{Child}ByIdString(int $id, array $options = array())
     * - delete{Children}(array $options = array())
     *
     * @param string $methodName
     *
     * @return mixed
     */
    public function __call($methodName, $args)
    {
        // get{ChildName}, getFirst{ChildName} and get{ChildName}ById
        if (strpos($methodName, 'get') === 0) {
            // getFirst{ChildName}
            if (strpos($methodName, 'getFirst') === 0) {
                $nome_id = mb_substr($methodName, mb_strlen('getFirst'));
                if ($child = $this->_findChild($nome_id)) {
                    return $this->getFirstChild($child['id_tipo'], (array) $args[0]);
                }
            // get{ChildName}ById
            } elseif (mb_substr($methodName, -4) == 'ById') {
                $nome_id = mb_substr($methodName, mb_strlen('get'), -mb_strlen('ById'));
                if ($child = $this->_findChild($nome_id)) {
                    $options = (array) $args[1];
                    $options['where'][] = 'id = '.intval($args[0]);
                    return $this->getFirstChild($child['id_tipo'], $options);
                }
            // get{ChildName}ByIdString
            } elseif (mb_substr($methodName, -10) == 'ByIdString') {
                $nome_id = mb_substr($methodName, mb_strlen('get'), -mb_strlen('ByStringId'));
                if ($child = $this->_findChild($nome_id)) {
                    $options = (array) $args[1];
                    $options['where'][] = "id_string = '".$args[0]."'";
                    return $this->getFirstChild($child['id_tipo'], $options);
                }
            // get{ChildName}Count
            } elseif (mb_substr($methodName, -5) == 'Count') {
                $nome_id = mb_substr($methodName, mb_strlen('get'), -mb_strlen('Count'));
                if ($child = $this->_findChild($nome_id)) {
                    return $this->getChildrenCount($child['id_tipo'], (array) $args[0]);
                }
            // get{ChildName}
            } else {
                $nome_id = mb_substr($methodName, mb_strlen('get'));
                if ($child = $this->_findChild($nome_id)) {
                    return $this->getChildren($child['id_tipo'], (array) $args[0]);
                }
            }
        // create{ChildName}
        } elseif (strpos($methodName, 'create') === 0) {
            $nome_id = mb_substr($methodName, mb_strlen('create'));
            if ($child = $this->_findChild($nome_id)) {
                return $this->createChild($child['id_tipo'], (array) @$args[0]);
            }
        // delete{ChildName}
        } elseif (strpos($methodName, 'delete') === 0) {
            $nome_id = mb_substr($methodName, mb_strlen('delete'));
            if ($child = $this->_findChild($nome_id)) {
                return $this->deleteChildren($child['id_tipo'], (array) $args[0]);
            }
        } elseif ($child = $this->_findChild(ucfirst($methodName))) {
            return $this->getChildrenTipo($child['id_tipo']);
        }
        // Default error when method doesn´t exist
        $message = 'Call to undefined method '.get_class($this).'->'.$methodName.'(). Available magic methods: '."\n";
        $children = $this->getTipo()->getInterAdminsChildren();
        $patterns = [
            'get{ChildName}',
            'getFirst{ChildName}',
            'get{ChildName}ById',
            'get{ChildName}ByIdString',
            'get{ChildName}Count',
            'create{ChildName}',
            'delete{ChildName}',
        ];
        foreach (array_keys($children) as $childName) {
            foreach ($patterns as $pattern) {
                $message .= "\t\t- ".str_replace('{ChildName}', $childName, $pattern)."\n";
            }
        }
        jp7_debug($message);
    }
    
    /**
     * Sets the InterAdminTipo object for this record, changing the $_tipo property.
     *
     * @param InterAdminTipo $tipo
     */
    public function setTipo(InterAdminTipo $tipo = null)
    {
        return $this->setType($tipo);
    }
    
    /**
     * Gets the InterAdminTipo object for this record, which is then cached on the $_tipo property.
     *
     * @param array $options Default array of options. Available keys: class.
     *
     * @return InterAdminTipo
     */
    public function getTipo($options = [])
    {
        return $this->getType($options);
    }
    
    /**
     * Gets fields values by their alias.
     *
     * @param array|string $fields
     *
     * @see InterAdmin::getFieldsValues()
     *
     * @return
     */
    public function getByAlias($fields)
    {
        if (func_num_args() > 1) {
            throw new Exception('Only 1 argument is expected and it should be an array.');
        }
        if (is_string($fields)) {
            return $this->$fields;
        }
    }
    
    /**
     * Returns the first Child.
     *
     * @param int   $id_tipo
     * @param array $options [optional]
     *
     * @return InterAdmin
     */
    public function getFirstChild($id_tipo, $options = [])
    {
        $retorno = $this->getChildren($id_tipo, ['limit' => 1] + $options);
        return $retorno[0];
    }
    
    /**
     * Retrieves this record´s children for the given $id_tipo.
     *
     * @param int   $id_tipo
     * @param array $options Default array of options. Available keys: fields, where, order, group, limit, class.
     *
     * @return array Array of InterAdmin objects.
     */
    public function getChildren($id_tipo, $options = [])
    {
        $children = [];
        if ($id_tipo) {
            $options = $options + ['fields_alias' => static::DEFAULT_FIELDS_ALIAS];
            if ($childrenTipo = $this->getChildrenTipo($id_tipo)) {
                $children = $childrenTipo->find($options);
            }
        }
        return $children;
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
}
