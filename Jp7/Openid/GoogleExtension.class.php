<?php

/**
 * Adds changes proposed on http://framework.zend.com/issues/browse/ZF-6905
 * 
 */
class Jp7_Openid_GoogleExtension extends Zend_OpenId_Extension {    
    /**
     * Returns $params.
     *
     * @see Jp7_Openid_GoogleExtension::$params
     */
    public function getParams() {
        return $this->params;
    }
    
    /**
     * Sets $params.
     *
     * @param object $params
     * @see Jp7_Openid_GoogleExtension::$params
     */
    public function setParams($params) {
        $this->params = $params;
    }
    
	public function getParam($key) {
		return $this->params[$key];
	}
	
	public function setParam($key, $value) {
		$this->params[$key] = $value;
	}
	
	private $params =  array(
		'openid.ns.ui' => 'http://specs.openid.net/extensions/ui/1.0',
		'openid.ns.ext1' => 'http://openid.net/srv/ax/1.0',
    	'openid.ext1.mode' => 'fetch_request',
		'openid.ext1.type.email' => 'http://axschema.org/contact/email',
		'openid.ext1.required' => 'email',
		'openid.ui.mode' => 'popup',
		'openid.ui.icon' => 'true'
	);
	
	public function prepareRequest(&$params)
    {
    	$params = $params + $this->params;
        return true;
    }
	
}
