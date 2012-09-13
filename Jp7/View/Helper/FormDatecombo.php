<?php
/**
 * Helper to generate a "date" element
 *
 * @category   Jp7
 * @package    Jp7_View
 * @subpackage Helper
 */
class Jp7_View_Helper_FormDatecombo extends Zend_View_Helper_FormElement
{
    public function formDatecombo($name, $value = null, $attribs = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
		 // name, value, attribs, options, listsep, disable
		
        // build the element
        $disabled = '';
        if ($info['disable']) {
            // disabled
            $disabled = ' disabled="disabled"';
        }
		
        // XHTML or HTML end tag?
        $endTag = ' />';
        if (($this->view instanceof Zend_View_Abstract) && !$this->view->doctype()->isXhtml()) {
            $endTag= '>';
        }
		
		$name = $this->view->escape($info['name']);
		$id = $this->view->escape($info['id']);
		
		try {
    		$value = new Jp7_Date((is_string($info['value']) && $info['value']) ? $info['value'] : '0000-00-00');
		} catch (Exception $e) {
			$value = new Jp7_Date('0000-00-00');
		}
		
		$sel_d = $value->day();
		$sel_m = $value->month();
		$sel_Y = $value->year();
		
		$days = '';
		for ($i = 1; $i <= 31; $i++) {
			$day = ($i < 10) ?  '0' . $i : $i;
			$days .= $this->_createOption($day, $day, $sel_d);
		}
		$months = '';
		for ($i = 1; $i <= 12; $i++) {
			$month = ($i < 10) ?  '0' . $i : $i;
			$months .= $this->_createOption(jp7_date_month($i, true), $month, $sel_m);
		}
		$years = '';
		for ($i = 1910; $i <= 2032; $i++) {
			$years .= $this->_createOption($i, $i, $sel_Y);
		}
		
        $xhtml = <<<XHTML
			<select name="${name}[__d]" id="${id}__d" class="date-day"$disabled>
				<option value="00">Dia</option>
				$days
			</select>
			<select name="${name}[__m]" id="${id}__m" class="date-month"$disabled>
				<option value="00">Mês</option>
				$months
			</select>
			<select name="${name}[__Y]" id="${id}__Y" class="date-year"$disabled>
				<option value="0000">Ano</option>
				$years
			</select>
XHTML;
        return $xhtml;
    }
	
	protected function _createOption($option, $value, $selectedValue) {
		$selected = ($value == $selectedValue) ? '" selected="selected' : '';
		return '<option value="' . $value . $selected . '">' . $option . '</option>';
	}
}
