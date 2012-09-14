<?php
/**
 * Helper to generate a "date" element
 *
 * @category   Jp7
 * @package    Jp7_View
 * @subpackage Helper
 */
class Jp7_View_Helper_FormFilePreview extends Zend_View_Helper_FormElement
{
    public function formFilePreview($name, $value = null, $attribs = null)
    {
    	$xhtml = <<<XHTML
		<div class="preview">
			<input type="text" class="current-file" name="$name" value="$value" />
		</div>
XHTML;
		
    	return $xhtml;
    }
	
}
