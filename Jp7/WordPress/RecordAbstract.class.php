<?php

abstract class Jp7_WordPress_RecordAbstract extends Jp7_WordPress_BaseAbstract {
	protected $_table;
	
	public function __construct($db, $table) {
		$this->_db = $db;
		$this->_table = $table;
	}
	
	public function updateAttributes($attributes) {
		$this->setAttributes($attributes);
		$this->_update($attributes);
	}
	
	public function setAttributes($attributes) {
		foreach ($attributes as $key => $value) {
			$this->$key = $value;
		}
	}
	
	protected function _update($attributes) {
		$db = $this->_db;
		$selfClass = get_class($this);
		
		$valuesToSave = array();
		
		foreach ($attributes as $key => $value) {
			switch (gettype($value)) {
				case 'object':
					$valuesToSave[$key] = (string) $value;
					break;
				case 'array':
					$valuesToSave[$key] = implode(',', $value);
					break;
				case 'NULL':
					$valuesToSave[$key] = '';
					break;
				default:
					$valuesToSave[$key] = $value;
					break;
			}
		}
				
		$pk = $this->staticConst('PK');
		if (!$pk || !$this->$pk) {
			throw new Exception('Undefined primary key.');	
		};
		if (!$this->_table) {
			throw new Exception('Undefined table.');	
		};
		
		if ($this->$pk) {
			$db->AutoExecute($this->_table, $valuesToSave, 'UPDATE', $pk . ' = ' .  $this->$pk) 
				or die(jp7_debug('Error while updating values in `' . $this->_table .  '` ' . $db->ErrorMsg(), print_r($valuesToSave, true)));
		} else {
			$db->AutoExecute($this->_table, $valuesToSave, 'INSERT') 
				or die(jp7_debug('Error while inserting data into `' . $this->_table . '` ' . $db->ErrorMsg(), print_r($valuesToSave, true)));
			$this->$pk = $db->Insert_ID();
		}
	}
	
	protected function staticConst($constname) {
		$constname = get_class($this) . '::' . $constname;
		if (defined($constname)) {
			return constant($constname);
		}
	}
	
	public function getTable() {
        return $this->_table;
    }
    
    public function setTable($table) {
        $this->_table = $table;
    }
}