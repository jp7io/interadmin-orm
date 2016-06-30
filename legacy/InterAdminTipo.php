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
        return $this->findFirst(Record::DEPRECATED_METHOD, $options);
    }
    
    /**
     * @param array $options Default array of options. Available keys: fields, where, order, group, limit, class.
     *
     * @return InterAdmin[] Array of InterAdmin objects.
     */
    public function find($options = [])
    {
        return $this->deprecatedFind($options);
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
}
