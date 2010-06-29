<?php

class Jp7_InterAdmin_Soap_AutoDiscover extends Zend_Soap_AutoDiscover {
	
	public function getUsuario() {
		return $this->_reflection->getUsuario();
	}
	
	public function setUsuario(InterAdmin $usuario) {
		$this->_reflection = new Jp7_InterAdmin_Soap_Reflection($usuario);
	}
	
	public function handle($request = false)
    {
        if (!headers_sent()) {
            header('Content-Type: text/xml');
        }
        $xml = $this->_wsdl->toXml();
		
		$locationReal = self::getServiceLocation();
		echo str_replace('<soap:address location="' . $this->_uri . '"/>', '<soap:address location="' . $locationReal . '"/>', $xml);
    }
	
	public static function getServiceLocation () {
		return 'http://' . $_SERVER['HTTP_HOST'] . preg_replace('/([^?]*)(.*)/', '\1', $_SERVER['REQUEST_URI']);
	}
	
}