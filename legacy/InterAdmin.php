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
}
