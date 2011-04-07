<?php

class Jp7_Controller_Router extends Zend_Controller_Router_Rewrite {
	
    /**
     * Generates a URL path that can be used in URL creation, redirection, etc.
     *
     * May be passed user params to override ones from URI, Request or even defaults.
     * If passed parameter has a value of null, it's URL variable will be reset to
     * default.
     *
     * If null is passed as a route name assemble will use the current Route or 'default'
     * if current is not yet set.
     *
     * Reset is used to signal that all parameters should be reset to it's defaults.
     * Ignoring all URL specified values. User specified params still get precedence.
     *
     * Encode tells to url encode resulting path parts.
     *
     * @param  array $userParams Options passed by a user used to override parameters
     * @param  mixed $name The name of a Route to use
     * @param  bool $reset Whether to reset to the route defaults ignoring URL params
     * @param  bool $encode Tells to encode URL parts on output
     * @throws Zend_Controller_Router_Exception
     * @return string Resulting URL path
     */
    public function assemble($userParams, $name = null, $reset = false, $encode = true){
    	 $config = Zend_Registry::get('config');
		 $lang = Zend_Registry::get('lang');
		 $request = Zend_Registry::get('originalRequest');
		 
		 $current = array(
		 	'lang' => $lang->lang,
		 	'module' => $request->getModuleName(),
			'controller' => $request->getControllerName(),
			'action' => $request->getActionName()
		 );
		 
		 $extraParams = array_diff_key($userParams, $current);
		 $userParams = $userParams + $current;
		 
		 $url = array();
		 foreach ($extraParams as $key => $value) {
			$url[] = $value;
			$url[] = $key;
		 }
		 if ($userParams['action'] != 'index' || $url) {
		 	$url[] = $userParams['action'];
		 }
		 if ($userParams['controller'] != 'index' || $url) {
		 	$url[] = $userParams['controller'];
		 }
		 if ($userParams['module'] != 'default') {
			 $url[] = $userParams['module'];
		 }
		 if ($userParams['lang'] != $config->lang_default) {
		 	$url[] = $userParams['lang'];
		 }
		 if ($config->server->path) {
		 	$url[] = $config->server->path;
		 }
		 krsort($url);
		 $url = '/' . implode('/', $url);
		 return $url;
	}
}