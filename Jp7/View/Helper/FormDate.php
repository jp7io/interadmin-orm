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
        $info = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, value, attribs, options, listsep, disable
		
        // build the element
        $disabled = '';
        if ($disable) {
            // disabled
            $disabled = ' disabled="disabled"';
        }
		
        // XHTML or HTML end tag?
        $endTag = ' />';
        if (($this->view instanceof Zend_View_Abstract) && !$this->view->doctype()->isXhtml()) {
            $endTag= '>';
        }
		
		$name = $this->view->escape($name);
		$id = $this->view->escape($id);
		$value = new Jp7_Date($value);
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
		for ($i = 1910; $i <= 2030; $i++) {
			$years .= $this->_createOption($i, $i, $sel_Y);
		}
		
		$script = <<<SCRIPT
		onchange="$('#$id').val($('#${id}__Y').val() + '-' + $('#${id}__m').val() + '-' + $('#${id}__d').val());";
SCRIPT;
		
		//$this->_htmlAttribs($attribs)
        $xhtml = <<<XHTML
			<select name="${name}__d" id="${id}__d" class="date-day"$disabled $script>
				<option value="00">Dia</option>
				$days
			</select>
			<select name="${name}__m" id="${id}__m" class="date-day"$disabled $script>
				<option value="00">Mês</option>
				$months
			</select>
			<select name="${name}__Y" id="${id}__Y" class="date-day"$disabled $script>
				<option value="0000">Ano</option>
				$years
			</select>
			<input type="hidden" value="$value" name="$name" id="$id"$disabled/>	
XHTML;
        return $xhtml;
    }
	
	protected function _createOption($option, $value, $selectedValue) {
		$selected = ($value == $selectedValue) ? '" selected="selected' : '';
		return '<option value="' . $value . $selected . '">' . $option . '</option>';
	}
}
