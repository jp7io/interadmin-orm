<?php

class Jp7_View extends Zend_View {
	
	static $_currentFiles = array();
	static $_errorsLog = array();
	
	public function __construct($config = array())
    {
    	parent::__construct($config);
		
		$this->doctype('XHTML1_STRICT');
		$this->setEncoding('ISO-8859-1');
		// Permite o uso de Helpers customizados da Jp7
		$this->addHelperPath('Jp7/View/Helper', 'Jp7_View_Helper');
    }
	
	protected function _run()
    {
    	$filename = func_get_arg(0);
    	
    	$erros = self::$_errorsLog[$filename];
    	if ($erros && $erros[reset(explode('?', $_SERVER['REQUEST_URI']))] > strtotime('-5 minutes')) {
    		echo 'Em manutenção.';
    	} else {
    		self::addCurrentFile($filename);
	    	
			if ($this->_useViewStream && $this->useStreamWrapper()) {
				include 'zend.view://' . $filename;
			} else {
				global $debugger;
				$debugger->showFilename($filename);			
				include $filename;
			}
			
			self::removeCurrentFile();
    	}
    }
    
    public static function addCurrentFile($filename) {
    	self::$_currentFiles[] = $filename;
    }
    
    public static function removeCurrentFile() {
    	array_pop(self::$_currentFiles);
    }
    
    public static function getCurrentFile() {
    	return end(self::$_currentFiles);
    }
	
    public static function readErrors() {
    	$viewsLog = self::getErrorsFilename();
    	if (is_file($viewsLog)) {
			self::$_errorsLog = unserialize(file_get_contents($viewsLog));
		}
	}
	
	public static function getErrorsFilename() {
		return sys_get_temp_dir() . DIRECTORY_SEPARATOR . '__errors_views.log';
	}
	
	public static function writeError($filename) {
		$viewsLog = self::getErrorsFilename();
		$semQuery = reset(explode('?', $_SERVER['REQUEST_URI']));
		self::$_errorsLog[$filename][$semQuery] = time();
		
		file_put_contents($viewsLog, serialize(self::$_errorsLog));
		// Como é impossível continuar o processamento após um fatal error, irá recarregar a página
		header('Location: ' . $_SERVER['REQUEST_URI']);
		exit;
	}
	
	public static function logError() {
		if ($viewfile = self::getCurrentFile()) {
			self::writeError($viewfile);
		}
	}
}