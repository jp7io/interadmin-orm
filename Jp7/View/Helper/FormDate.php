<?php
/**
 * Helper to generate a "date" element
 *
 * @category   Jp7
 * @package    Jp7_View
 * @subpackage Helper
 */
class Jp7_View_Helper_FormDate extends Zend_View_Helper_FormElement
{
    public function formDate($name, $value = null, $attribs = null)
    {
    	try {
    		$dateObj = new Jp7_Date($value ? $value : '0000-00-00');
		} catch (Exception $e) {
			$dateObj = new Jp7_Date('0000-00-00');
		}
		$showTime = $attribs['showTime'];
		unset($attribs['showTime']);
		
		$valueDate = $dateObj->isValid() ? $dateObj->format('d/m/Y') : '';
		$date = $this->view->formText($name . '[date]', $valueDate, $attribs + array(
			'class' => 'date',
			'placeholder' => 'Dia/Mês/Ano'
		));
		
		unset($attribs['id']); // nao repetir ID no time
		if ($showTime) {
			$valueTime = $dateObj->isValid() ? $dateObj->format('H:i') : '';
			$time = $this->view->formText($name . '[time]', $valueTime, $attribs + array(
				'class' => 'time',
				'placeholder' => '00:00'
			)); 
		}
		
		return $date . $time;
		
    }
}
