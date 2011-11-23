<?php

class Jp7_Feed_Entry extends Zend_Feed_Writer_Entry { 

    /**
     * @see Zend_Feed_Writer_Entry::setContent()
     */
    public function setContent($content) {
        parent::setContent(utf8_encode($content));
    }
    
    /**
     * @see Zend_Feed_Writer_Entry::setDateCreated()
     */
    public function setDateCreated($date) {
    	if ($date instanceof Jp7_Date) {
			parent::setDateCreated($date->getTimestamp());
		} else {
			parent::setDateCreated($date);
		}
    }
    
    /**
     * @see Zend_Feed_Writer_Entry::setDateModified()
     */
    public function setDateModified($date) {
    	if ($date instanceof Jp7_Date) {
        	parent::setDateModified($date->getTimestamp());
		} else {
			parent::setDateModified($date);
		}
    }
    
    /**
     * @see Zend_Feed_Writer_Entry::setDescription()
     */
    public function setDescription($description) {
		parent::setDescription(utf8_encode($description));
    }
    
    /**
     * @see Zend_Feed_Writer_Entry::setTitle()
     */
    public function setTitle($title) {
        parent::setTitle(utf8_encode($title));
    } 
}
?>