<?php
/**
 * Date form element
 *
 * @category   Jp7
 * @package    Jp7_Form
 * @subpackage Element
 */
class Jp7_Form_Element_Datecombo extends Zend_Form_Element_Xhtml
{
    /**
     * Default form view helper to use for rendering
     * @var string
     */
    public $helper = 'formDatecombo';
	
	public function isValid($value, $context = null) {
		$newValue = $this->_foraDeFormato($value);
		if ($newValue !== false) {
			$value = $newValue;
		}		
		return parent::isValid($value, $context);
	}

	public function getValue() {
		$newValue = $this->_foraDeFormato($value);
		if ($newValue !== false) {
			$this->setValue($newValue);
		}
		return parent::getValue();
	}
	
	private function _foraDeFormato($value) {
		if (is_array($value)) {
            $newValue = $value['__Y'] . '-' . $value['__m'] . '-' . $value['__d'];
			if ($newValue == '--' || $newValue == '0000-00-00') {
				return null;
            }
			if ($value['__H']) {
				$newValue .= ' ' .$value['__H'] . ':' . $value['__i'];
			}
			return $newValue;
        }
		return false;
	}
}
