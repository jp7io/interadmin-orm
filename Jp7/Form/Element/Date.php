<?php
/**
 * Date form element
 *
 * @category   Jp7
 * @package    Jp7_Form
 * @subpackage Element
 */
class Jp7_Form_Element_Date extends Zend_Form_Element_Xhtml
{
    /**
     * Default form view helper to use for rendering
     * @var string
     */
    public $helper = 'formDate';
	
	public function isValid($value, $context = null) {
		if (is_array($value)) {
            $value = $value['__Y'] . '-' . $value['__m'] . '-' . $value['__d'];
			if ($value == '--') {
				$value = null;
            }
        }
		return parent::isValid($value, $context);
	}

	public function getValue() {
		if (is_array($this->_value)) {
			$value = $this->_value['__Y'] . '-' . $this->_value['__m'] . '-' . $this->_value['__d'];
			if ($value == '--') {
				$value = null;
            }
			$this->setValue($value);
		}
		return parent::getValue();
	}
}
