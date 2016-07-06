<?php

use Jp7\Interadmin\Record;
use Jp7\Interadmin\RecordAbstract;
use Jp7\Interadmin\FileField;
use Illuminate\Support\Collection;

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
class InterAdmin extends Record implements InterAdminAbstract
{
    const DEFAULT_NAMESPACE = '';
    
    protected static $unguarded = true;
    
    /**
     * Returns the full url for this record.
     *
     * @return string
     */
    public function getUrl($sep = null)
    {
        global $seo, $seo_sep;
        if ($seo && $this->getParent()->id) {
            $link = $this->_parent->getUrl().'/'.toSeo($this->getTipo()->getFieldsValues('nome'));
        } else {
            $link = $this->getTipo()->getUrl();
        }
        if ($seo) {
            $aliases = $this->getTipo()->getCamposAlias();
            if (array_key_exists('varchar_key', $aliases)) {
                $alias = $aliases['varchar_key'];
                if (isset($this->$alias)) {
                    $nome = $this->$alias;
                } else {
                    $nome = $this->getFieldsValues('varchar_key');
                }
            }
            if (is_null($sep)) {
                $sep = $seo_sep;
            }
            $link .= $sep.toSeo($nome);
        } else {
            $link .= '?id='.$this->id;
        }
        return $link;
    }
    
    /**
     * Sets only the editable attributes, prevents the user from setting $id_tipo, for example.
     *
     * @param array $attributes
     */
    public function setAttributesSafely(array $attributes)
    {
        $editableFields = array_flip($this->getAttributesAliases());
        $filteredAttributes = array_intersect_key($attributes, $editableFields);
        return $this->setAttributes($filteredAttributes);
    }
    
    /**
     * Sets this object´s attributes with the given array keys and values.
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->$key = $value;
        }
    }
    
    /**
     * Returns this object´s varchar_key and all the fields marked as 'combo', if the field
     * is an InterAdmin such as a select_key, its getStringValue() method is used.
     *
     * @return string For the city 'Curitiba' with the field 'state' marked as 'combo' it would return: 'Curitiba - Paraná'.
     */
    public function getStringValue()
    {
        $camposCombo = $this->getTipo()->getCamposCombo();
        if (!$camposCombo) {
            return $this->id;
        }
        $valoresCombo = $this->getFieldsValues($camposCombo);
        $stringValue = [];
        foreach ($valoresCombo as $value) {
            if ($value instanceof FileField) {
                continue;
            } elseif ($value instanceof RecordAbstract) {
                $value = $value->getStringValue();
            }
            $stringValue[] = $value;
        }
        
        return implode(' - ', $stringValue);
    }

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
                if ($child = $this->_deprecatedFindChild($nome_id)) {
                    return $this->getFirstChild($child['id_tipo'], (array) $args[0]);
                }
            // get{ChildName}ById
            } elseif (mb_substr($methodName, -4) == 'ById') {
                $nome_id = mb_substr($methodName, mb_strlen('get'), -mb_strlen('ById'));
                if ($child = $this->_deprecatedFindChild($nome_id)) {
                    $options = (array) $args[1];
                    $options['where'][] = 'id = '.intval($args[0]);
                    return $this->getFirstChild($child['id_tipo'], $options);
                }
            // get{ChildName}ByIdString
            } elseif (mb_substr($methodName, -10) == 'ByIdString') {
                $nome_id = mb_substr($methodName, mb_strlen('get'), -mb_strlen('ByStringId'));
                if ($child = $this->_deprecatedFindChild($nome_id)) {
                    $options = (array) $args[1];
                    $options['where'][] = "id_string = '".$args[0]."'";
                    return $this->getFirstChild($child['id_tipo'], $options);
                }
            // get{ChildName}Count
            } elseif (mb_substr($methodName, -5) == 'Count') {
                $nome_id = mb_substr($methodName, mb_strlen('get'), -mb_strlen('Count'));
                if ($child = $this->_deprecatedFindChild($nome_id)) {
                    return $this->getChildrenCount($child['id_tipo'], (array) $args[0]);
                }
            // get{ChildName}
            } else {
                $nome_id = mb_substr($methodName, mb_strlen('get'));
                if ($child = $this->_deprecatedFindChild($nome_id)) {
                    return $this->getChildren($child['id_tipo'], (array) $args[0]);
                }
            }
        // create{ChildName}
        } elseif (strpos($methodName, 'create') === 0) {
            $nome_id = mb_substr($methodName, mb_strlen('create'));
            if ($child = $this->_deprecatedFindChild($nome_id)) {
                return $this->createChild($child['id_tipo'], (array) @$args[0]);
            }
        // delete{ChildName}
        } elseif (strpos($methodName, 'delete') === 0) {
            $nome_id = mb_substr($methodName, mb_strlen('delete'));
            if ($child = $this->_deprecatedFindChild($nome_id)) {
                return $this->deleteChildren($child['id_tipo'], (array) $args[0]);
            }
        }
        return parent::__call($methodName, $args);
    }
    
    protected function _loadRelationship($relationships, $name)
    {
        $result = parent::_loadRelationship($relationships, $name);
        if ($result && $result instanceof Collection) {
            return $result->all();
        }
        return $result;
    }
    
    public function relationFromColumn($column)
    {
        $alias = $this->getType()->getCamposAlias($column);
        if (starts_with($column, 'select_multi_')) {
            $relationship = substr($alias, 0, -4); // _ids = 4 chars
        } elseif (starts_with($column, 'select_')) {
            $relationship = substr($alias, 0, -3); // _id = 3 chars
        } else {
            throw InvalidArgumentException('$column must start with select_ or select_multi_.');
        }
        return $this->$relationship;
    }
    
    protected function _aliasToColumn($alias, $aliases)
    {
        if (isset($aliases[$alias])) {
            return $aliases[$alias];
        }
        if (isset($aliases[$alias.'_id'])) {
            return $aliases[$alias.'_id'];
        }
        if (isset($aliases[$alias.'_ids'])) {
            return $aliases[$alias.'_ids'];
        }
        return $alias;
    }
    
    /**
     * Finds a Child Tipo by a camelcase keyword.
     *
     * @param string $nome_id CamelCase
     *
     * @return array
     */
    protected function _deprecatedFindChild($nome_id)
    {
        $children = $this->getTipo()->getInterAdminsChildren();
        if (empty($children[$nome_id])) {
            $nome_id = explode('_', Jp7_Inflector::underscore($nome_id));
            $nome_id[0] = Jp7_Inflector::plural($nome_id[0]);
            $nome_id = Jp7_Inflector::camelize(implode('_', $nome_id));
        }
        if (empty($children[$nome_id])) {
            $nome_id = Jp7_Inflector::plural($nome_id);
        }
        return $children[$nome_id];
    }
    
    /**
     * Creates and returns a child record.
     *
     * @param int   $id_tipo
     * @param array $attributes Attributes to be merged into the new record.
     *
     * @return
     */
    public function createChild($id_tipo, array $attributes = [])
    {
        return $this->getChildrenTipo($id_tipo)->createInterAdmin($attributes);
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
     * Returns the number of children using COUNT(id).
     *
     * @param int   $id_tipo
     * @param array $options Default array of options. Available keys: where.
     *
     * @return int Count of InterAdmins found.
     */
    public function getChildrenCount($id_tipo, $options = [])
    {
        $options['fields'] = ['COUNT(DISTINCT id)'];
        $retorno = $this->getFirstChild($id_tipo, $options);
        return intval($retorno->count_distinct_id);
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
     * Returns the first Child by ID.
     *
     * @param int   $id_tipo
     * @param int   $id
     * @param array $options [optional]
     *
     * @return InterAdmin
     */
    public function getChildById($id_tipo, $id, $options = [])
    {
        $options['limit'] = 1;
        $options['where'][] = 'id = '.intval($id);
        $retorno = $this->getChildren($id_tipo, $options);
        return $retorno[0];
    }
    /**
     * Deletes all the children of a given $id_tipo.
     *
     * @param int   $id_tipo
     * @param array $options [optional]
     *
     * @return int Number of deleted children.
     */
    public function deleteChildren($id_tipo, $options = [])
    {
        $children = $this->getChildren($id_tipo, $options);
        foreach ($children as $child) {
            $child->delete();
        }
        return count($children);
    }
    /**
     *  Deletes the children of a given $id_tipo forever.
     *
     * @param int   $id_tipo
     * @param array $options [optional]
     *
     * @return int Count of deleted InterAdmins.
     */
    public function deleteChildrenForever($id_tipo, $options = [])
    {
        if ($id_tipo) {
            $tipo = $this->getChildrenTipo($id_tipo);
            return $tipo->deleteInterAdminsForever($options);
        }
    }
    /**
     * Creates a new InterAdminArquivo with id_tipo, id and mostrar set.
     *
     * @param array $attributes [optional]
     *
     * @return InterAdminArquivo
     */
    public function createArquivo(array $attributes = [])
    {
        $className = static::DEFAULT_NAMESPACE.'InterAdminArquivo';
        if (!class_exists($className)) {
            $className = 'InterAdminArquivo';
        }
        $arquivo = new $className();
        $arquivo->setParent($this);
        $arquivo->setTipo($this->getTipo());
        $arquivo->mostrar = 'S';
        $arquivo->setAttributes($attributes);
        return $arquivo;
    }
    /**
     * Retrieves the uploaded files of this record.
     *
     * @param array $options Default array of options. Available keys: fields, where, order, limit.
     *
     * @return array Array of InterAdminArquivo objects.
     */
    public function getArquivos($options = [])
    {
        return $this->deprecated_getArquivos($options)->all();
    }
    
    public function getFirstArquivo($options = [])
    {
        $retorno = $this->getArquivos($options + ['limit' => 1]);
        return $retorno[0];
    }
    /**
     * Deletes all the InterAdminArquivo records related with this record.
     *
     * @param array $options [optional]
     *
     * @return int Number of deleted arquivos.
     */
    public function deleteArquivos($options = [])
    {
        $arquivos = $this->getArquivos($options);
        foreach ($arquivos as $arquivo) {
            $arquivo->delete();
        }
        return count($arquivos);
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
    
    /**
     * Updates all the attributes from the passed-in array and saves the record.
     *
     * @param array $attributes Array with fields names and values.
     */
    public function updateAttributes($attributes)
    {
        $this->setRawAttributes($attributes);
        $this->_update($attributes);
    }
    
    protected function _convertForDatabase($attributes, $aliases)
    {
        $valuesToSave = parent::_convertForDatabase($attributes, $aliases);
        foreach ($this->getTipo()->getRelationships() as $name => $data) {
            if (isset($valuesToSave[$name])) {
                if ($data['multi']) {
                    $alias = $aliases[$name.'_ids'];
                } else {
                    $alias = $aliases[$name.'_id'];
                }
                if ($alias) {
                    $valuesToSave[$alias] = $valuesToSave[$name];
                    unset($valuesToSave[$name]);
                }
            }
        }
        return $valuesToSave;
    }
    
    /**
     * Reloads all the attributes.
     *
     * @todo Not implemented yet. Won't work with recursive objects and alias.
     */
    public function reload($fields = null)
    {
        if (is_null($fields)) {
            $fields = array_keys($this->attributes);
            $existingFields = array_merge($this->getAttributesAliases(), $this->getAttributesNames(), $this->getAdminAttributes());
            $fields = array_intersect($fields, $existingFields);
        }
        // Esvaziando valores para forçar atualização
        foreach ($fields as $key) {
            unset($this->attributes[$key]);
        }
        $isAliased = static::DEFAULT_FIELDS_ALIAS;
        $this->getFieldsValues($fields, false, $isAliased);
    }
    
    /**
     * Creates a object of the given Class name with the same attributes.
     *
     * @param object $className
     *
     * @return InterAdminAbstract An instance of the given Class name.
     */
    public function becomes($className)
    {
        $newobject = new $className();
        $newobject->attributes = $this->attributes;
        return $newobject;
    }
    
    /**
     * Returns the tags.
     *
     * @param array $options Available keys: where, group, limit.
     *
     * @return array
     */
    public function getTags($options = [])
    {
        if (!$this->_tags || $options) {
            $db = $this->getDb();
            $options['where'][] = 'parent_id = '.$this->id;
            $sql = 'SELECT * FROM '.$db->getTablePrefix().'tags '.
                //'WHERE '.implode(' AND ', $options['where']).
                (($options['group']) ? ' GROUP BY '.$options['group'] : '').
                (($options['limit']) ? ' LIMIT '.$options['limit'] : '');
            $rs = $db->select($sql);
            $this->_tags = [];
            foreach ($rs as $row) {
                if ($tag_tipo = InterAdminTipo::getInstance($row->id_tipo)) {
                    $tag_text = $tag_tipo->getFieldsValues('nome');
                    if ($row->id) {
                        $options = [
                            'fields' => ['varchar_key'],
                            'where' => ['id = '.$row->id],
                        ];
                        if ($tag_registro = $tag_tipo->findFirst($options)) {
                            $tag_text = $tag_registro->varchar_key.' ('.$tag_tipo->nome.')';
                            $tag_registro->interadmin = $this;
                            $retorno[] = $tag_registro;
                        }
                    } else {
                        $tag_tipo->interadmin = $this;
                        $retorno[] = $tag_tipo;
                    }
                }
            }
        } else {
            $retorno = $this->_tags;
        }
        if (!$options) {
            $this->_tags = $retorno; // cache somente para getTags sem $options
        }
        return (array) $retorno;
    }
    
    /**
     * Sets the tags for this record. It DELETES the previous records.
     *
     * @param array $tags Array of object to be saved as tags.
     */
    public function setTags(array $tags)
    {
        $db = $this->getDb();
        $sql = 'DELETE FROM '.$db->getTablePrefix().'tags WHERE parent_id = '.$this->id;
        foreach ($tags as $tag) {
            $sql = 'INSERT INTO '.$db->getTablePrefix().'tags (parent_id, id, id_tipo) VALUES
                ('.$this->id.','.
                (($tag instanceof self) ? $tag->id : 0).','.
                (($tag instanceof self) ? $tag->getFieldsValues('id_tipo') : $tag->id_tipo).')';
            if (!$db->insert($sql)) {
                throw new Jp7_Interadmin_Exception($db->ErrorMsg());
            }
        }
    }
    
    public function getTagFilters()
    {
        return '(tags.id = '.$this->id." AND tags.id_tipo = '".$this->getTipo()->id_tipo."')";
    }
    
    public function setFieldBySearch($attribute, $searchValue, $searchColumn = 'varchar_key')
    {
        return $this->setAttributeBySearch($attribute, $searchValue, $searchColumn);
    }
}
