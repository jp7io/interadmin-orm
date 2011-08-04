<?php

abstract class Jp7_WordPress_BaseAbstract {
	protected $_db;
	
	/**
     * Returns $_db.
     *
     * @see Jp7_WordPress::$_db
     * @return ADOConnection
     */
    public function getDb() {
        return $this->_db;
    }
    
    /**
     * Sets $_db.
     *
     * @param ADOConnection $_db
     * @see Jp7_WordPress::$_db
     */
    public function setDb($db) {
        $this->_db = $db;
    }
	
	public static function formatQuery($options) {
		$sql = "SELECT " . implode(',', (array) $options['fields']) .
			" FROM " . $options['from'];
		if ($options['where']) {
			$sql .= " WHERE " . implode(' AND ', (array) $options['where']);
		}
		if ($options['group']) {
			$sql .= " GROUP BY " . $options['group'];
		}
		if ($options['order']) {
			$sql .= " ORDER BY " . $options['order'];
		}
		if ($options['limit']) {
			$sql .= " LIMIT " . $options['limit'];
		}
		if ($options['debug']) {
			krumo($sql);
		}
		return $sql;
	}
	
	public static function retrieveObjects($db, $options, $className) {
		$sql = self::formatQuery($options);
		$rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
		
		$array = array();
		while ($row = $rs->FetchNextObj()) {
			$object = new $className($db, $options['from']);
			foreach ($row as $key => $value) {
				$object->$key = $value;
			}
			$array[] = $object;
		}		
		return $array;
	}
}