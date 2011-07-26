<?php

class Jp7_View extends Zend_View {
	public function __construct($config = array())
    {
    	parent::__construct($config);
		$this->addHelperPath('Jp7/View/Helper', 'Jp7_View_Helper');
    }	
}