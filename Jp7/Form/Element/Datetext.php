<?php
/**
 * Date form element
 *
 * @category   Jp7
 * @package    Jp7_Form
 * @subpackage Element
 */
class Jp7_Form_Element_Datetext extends Zend_Form_Element_Xhtml
{
	public $helper = 'formDatetext';
	
   	public function isValid($value, $context = null) {
		$newValue = $this->_foraDeFormato($value);
		if ($newValue) {
			$value = $newValue;
		}		
		return parent::isValid($value, $context);
	}
	
	public function getValue() {
		$newValue = $this->_foraDeFormato($value);
		if ($newValue) {
			$this->setValue($newValue);
		}
		return parent::getValue();
	}
	
	private function _foraDeFormato($value) {
		if (strpos($value, '/') !== false && preg_match('~^([0-9]{2})/([0-9]{2})/([0-9]{4})( [0-9]{2}:[0-9]{2})?$~', $value, $matches)) {
			list($tudo, $d, $m, $y, $time) = $matches;
			return $y . '-' . $m . '-' . $d . $time;
		}
		return false;
	}
}
