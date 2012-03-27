<?php

class Jp7_Controller_Dispatcher extends Zend_Controller_Dispatcher_Standard {
	
	protected static $default_parent_class = 'Jp7_Controller_Action';
	
    /**
     * Dispatch to a controller/action
     *
     * By default, if a controller is not dispatchable, dispatch() will throw
     * an exception. If you wish to use the default controller instead, set the
     * param 'useDefaultControllerAlways' via {@link setParam()}.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @return void
     * @throws Zend_Controller_Dispatcher_Exception
     */
    public function dispatch(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response) {
    	if (!$this->isDispatchable($request)) {
            $controller = $request->getControllerName();
            if (!$this->getParam('useDefaultControllerAlways') && !empty($controller)) {
            	// Abrir template dentro do mesmo cliente
				$className = $this->_getControllerClassWithModelPrefix($request);
				eval('class ' . $className . ' extends ' . self::getDefaultParentClass() . ' {};');
            }
        }
		return parent::dispatch($request, $response);
    }
	
	/**
     * Returns TRUE if the Zend_Controller_Request_Abstract object can be
     * dispatched to a controller.
     *
     * Use this method wisely. By default, the dispatcher will fall back to the
     * default controller (either in the module specified or the global default)
     * if a given controller does not exist. This method returning false does
     * not necessarily indicate the dispatcher will not still dispatch the call.
     *
     * @param Zend_Controller_Request_Abstract $action
     * @return boolean
     */
    public function isDispatchable(Zend_Controller_Request_Abstract $request) {
        $retornoOriginal = parent::isDispatchable($request);
		// Necessário porque ZF não verifica se uma classe com o prefixo do módulo existe
		if (!$retornoOriginal) {			
			$className = $this->_getControllerClassWithModelPrefix($request);
	        if (class_exists($className, false)) {
	            return true;
	        }
		}
        return $retornoOriginal;
    }
	
	/**
	 * Returns the name of the controller class prefixed with the model prefix.
	 * 
	 * @param Zend_Controller_Request_Abstract $request
	 * @return string
	 */
	protected function _getControllerClassWithModelPrefix($request) {
		$className = $this->getControllerClass($request);
		if (($this->_defaultModule != $this->_curModule) || $this->getParam('prefixDefaultModule')) {
            $className = $this->formatClassName($this->_curModule, $className);
        }
		return $className;
	}
	
	/**
	 * Evals a file as a child class of the current default parent class.
	 * Ok, I know it's evil, but it's needed.
	 * 
	 * @param string $filename
	 * @return void
	 */
	public static function evalAsAController($filename) {
		if (strpos($filename, 'eval') === false) {
			$class_contents = file_get_contents($filename);
			$class_contents = str_replace('__Controller_Action', self::getDefaultParentClass(), $class_contents);
			$class_contents = str_replace('return Jp7_Controller_Dispatcher::evalAsAController', '//', $class_contents);
			eval('?>' . $class_contents);
		}
	}
    
    /**
     * Returns $default_parent_class.
     *
     * @see Jp7_Controller_Dispatcher::$default_parent_class
     */
    public static function getDefaultParentClass() {
    	return self::$default_parent_class;
    }
    
    /**
     * Sets $default_parent_class.
     *
     * @param object $default_parent_class
     * @see Jp7_Controller_Dispatcher::$default_parent_class
     */
    public static function setDefaultParentClass($default_parent_class) {
        self::$default_parent_class = $default_parent_class;
    }
	
	// NAO MODIFICAR ABAIXO
	public function loadClass($className)
    {
        $finalClass  = $className;
        if (($this->_defaultModule != $this->_curModule)
            || $this->getParam('prefixDefaultModule'))
        {
            $finalClass = $this->formatClassName($this->_curModule, $className);
        }
        if (class_exists($finalClass, false)) {
            return $finalClass;
        }

        $dispatchDir = $this->getDispatchDirectory();
        $loadFile    = $dispatchDir . DIRECTORY_SEPARATOR . $this->classToFilename($className);
		
		/* Linhas da Jp7 */
		global $debugger; 
		$debugger->showFilename($loadFile);
		/* End: Linhas da Jp7 */
	
        if (Zend_Loader::isReadable($loadFile)) {
            include_once $loadFile;
        } else {
            require_once 'Zend/Controller/Dispatcher/Exception.php';
            throw new Zend_Controller_Dispatcher_Exception('Cannot load controller class "' . $className . '" from file "' . $loadFile . "'");
        }

        if (!class_exists($finalClass, false)) {
            require_once 'Zend/Controller/Dispatcher/Exception.php';
            throw new Zend_Controller_Dispatcher_Exception('Invalid controller class ("' . $finalClass . '")');
        }

        return $finalClass;
    }
}