<?php

/**
 * Utilizado somente para ambiente Zend Framework - MVC
 */
class Jp7_InterAdmin_Tipo extends InterAdminTipo
{
	public function getUrl()
	{
		if ($this->_url) {
			return $this->_url;
		}
		$config = Zend_Registry::get('config');

		$urlPath = array();
		$parent = $this;

		while ($parent && $parent->id_tipo) {
			$urlPath[] = toSeo($parent->getFieldsValues('nome'));
			$parent = $parent->getParent();
			if ($parent instanceof InterAdmin) {
				$parent = $parent->getTipo();
			}
		}

		$urlPath = array_reverse($urlPath);

		$frontController = Zend_Controller_Front::getInstance();
		$baseUrl = $frontController->getBaseUrl();
		$lang = $frontController->getRequest()->getParam('lang');

		$url = $baseUrl . '/' . $lang  . '/' . join('/', $urlPath);

		return $this->_url = $url;
	}

	public function getChildren($options = array()){
		if (!$options['class']) {
			$options['class'] = 'Jp7_InterAdmin_Tipo';
		}
		return parent::getChildren($options);
	}

	public function getParent($options = array()){
		if (!$options['class']) {
			$options['class'] = 'Jp7_InterAdmin_Tipo';
		}
		return parent::getParent($options);
	}
}