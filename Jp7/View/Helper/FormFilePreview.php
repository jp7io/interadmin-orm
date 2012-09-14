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
    	$images = array('jpg', 'gif', 'png');
		if ($value instanceof InterAdminFieldFile) {
	    	if (in_array($value->getExtension(), $images)) {
	    		$previewImg = '<img src="' . $value->getUrl('interadmin_thumb') . '" title="Imagem Atual" alt="Imagem Atual" />';
			} else {
				$previewImg = $this->view->fileIcon($value);
			}
			$previewLink = '<a class="download-file" target="_blank" href="' . $value->getUrl() . '">' . $previewImg . '</a>';
		}
		
    	$xhtml = <<<XHTML
		<div class="preview">
			$previewLink
			<input type="hidden" class="current-file" name="$name" value="$value" />
		</div>
XHTML;
    	return $xhtml;
    }
	
}
