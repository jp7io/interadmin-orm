<?php

class Jp7_Feed extends Zend_Feed_Writer_Feed {
	
	/**
	 * Datetime do ъltimo registro inserido, que servirб como ъltima modificaзгo
	 * no feed.
	 * 
	 * @var timestamp $lastModified
	 */
	private $lastDateModified = 0;
	
	/**
	 * Analisa objetos InterAdmin para criar uma entrada
	 * @param array $interAdmins [optional] InterAdmin[]
	 * @param array $helpers [optional] Regras que ajudam ou forзam a leitura 
	 * de atributos do objeto InterAdmin.
	 * 
	 * @example Defaults: array(
	 * 	'content' => 'texto', // string
	 * 	'title' => 'titulo', // string
	 * 	'link' => 'link' | getUrl(), // string Link | getUrl
	 * 	'category' => null, // array('term' => , slug)
	 * 	'description' => 'resumo', // string
	 * 	'id' => link value, // valid URI/IRI
	 * 	'date_modified' => 'date_modify', // Timestamp value
	 * 	'date_created' => 'date_publish' // Timestamp value
	 * )
	 * 
	 * @param string $category [optional]
	 */
	public function parserInterAdmins($interAdmins = array(), $category = '', $helpers = array()) {
		if (!is_array($interAdmins)) {
			throw new Jp7_Feed_Exception("Undefined value for 'id'");
		}
		
		$helpers = array_merge(array(
			'content' => 'texto', // string
			'title' => 'titulo', // string
			'link' => 'link', // string This or getUrl()
			'category' => null, // array('term' => , slug)
			'description' => 'resumo', // string
			'id' => 'this.link', // valid URI/IRI
			'date_modified' => 'date_modify', // Timestamp value
			'date_created' => 'date_publish' // Timestamp value
		), $helpers);
		
		if (substr($helpers['id'], 0, 5) == 'this.') {
			$methodId = 'get' . ucfirst(strtolower(ltrim($helpers['id'], 'this.')));
		}
		
		foreach ($interAdmins as $entryData) {
			if ($entryData instanceof InterAdmin) {
				$entry = $this->createEntry();
				$entry->setTitle($entryData->$helpers['title']);
				$entry->setLink($entryData->$helpers['link'] ? $entryData->$helpers['link'] : $entryData->getUrl());
				if ($category) {
					$entry->addCategory(array('term' => $category));
				}
				if ($entryData->$helpers['content']) {
					$entry->setContent($entryData->texto);
				}
				
				$entry->setId($methodId ? $entry->$methodId() : $helpers['id']);
				
				$dateModified = $entryData->getByAlias($helpers['date_modified'])->getTimestamp();
				
				$entry->setDateModified($dateModified);
				$entry->setDateCreated($entryData->getByAlias($helpers['date_created'])->getTimestamp());
				
				if ($entryData->$helpers['description']) {
					$entry->setDescription($entryData->$helpers['description']);
				}
				
				$this->addEntry($entry);
				
				if ($dateModified > $lastDateModified) {
					$this->lastDateModified = $dateModified;
				}
			} else {
				throw new Jp7_Feed_Exception('Um objeto ou mais nгo й uma instвncia de InterAdmin.');
			}
		}
	}
	
	/**
	 * Retorna o valor concatenado dos campos descritos.
	 * @param InterAdmin $object
	 * @param array     $fields Campos do objeto a serem concatenados.
	 * @param string     $glue [optional] Separador
	 * @return string Valor dos campos concatenados
	 */
	private function concatenateFields(InterAdmin $object, $fields, $glue = ' - ') {
		if (is_array($fields)) {
			$concatenated = array();
			
			foreach ($fields as $field) {
				if ($str = $object->$field) {
					$concatenated[] = $str;
				}
			}
			
			return implode($glue, $concatenated);
		} else {
			return $object->$fields;
		}
	}
	
	public function getEntries() {
		return $this->_entries;
	}
	
    /**
     * @see Zend_Feed_Writer_Feed_FeedAbstract::setDescription()
     */
    public function setDescription($description) {
		parent::setDescription(utf8_encode($description));
    }
	
    /**
     * @see Zend_Feed_Writer_Feed_FeedAbstract::setTitle()
     */
    public function setTitle($title) {
		parent::setTitle(utf8_encode($title));
    }
	 
    /**
     * @see Zend_Feed_Writer_Feed_FeedAbstract::setCopyright()
     */
    public function setCopyright($copyright) {
    	parent::setCopyright(utf8_encode($copyright));
    }
	 
    /**
     * @see Zend_Feed_Writer_Feed::createEntry()
     */
    public function createEntry() {
        $entry = new Jp7_Feed_Entry;
        if ($this->getEncoding()) {
            $entry->setEncoding($this->getEncoding());
        }
        $entry->setType($this->getType());
        return $entry;
    }
	
	public function getLastDateModified() {
		return $this->lastDateModified;
	}
	
		 
    /**
     * @see Zend_Feed_Writer_Feed::export()
     */
    public function export($type, $ignoreExceptions = false) {
    	if (!$this->getDateModified()) {
			$this->setDateModified($this->lastDateModified);
		}
		return parent::export($type, $ignoreExceptions = false);
    }
}
?>