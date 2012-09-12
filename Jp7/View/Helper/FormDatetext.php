<?php
/**
 * Helper to generate a "date" element
 *
 * @category   Jp7
 * @package    Jp7_View
 * @subpackage Helper
 */
class Jp7_View_Helper_FormDatetext extends Zend_View_Helper_FormElement
{
    public function formDatetext($name, $value = null, $attribs = null)
    {
		if (preg_match('~^([0-9]{4})-([0-9]{2})-([0-9]{2})(.*)~', $value, $matches)) {
			list($tudo, $y, $m, $d, $time) = $matches;
			$value = $d . '/' . $m . '/' . $y . $time;			
		}
		return $this->view->formText($name, $value, $attribs);
    }
}
