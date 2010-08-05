<?php

class Jp7_Controller_Dispatcher extends Zend_Controller_Dispatcher_Standard {

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
    public function dispatch(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response)
    {
    	if (!$this->isDispatchable($request)) {
            $controller = $request->getControllerName();
            if (!$this->getParam('useDefaultControllerAlways') && !empty($controller)) {
            	// Abrir template
				$indexController = $this->loadClass('IndexController');
				$parentClassName = get_parent_class($indexController);
				$className = $this->_getControllerClassWithModelPrefix($request);
				eval('class ' . $className . ' extends ' . $parentClassName . ' {};');
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
    public function isDispatchable(Zend_Controller_Request_Abstract $request)
    {
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
}