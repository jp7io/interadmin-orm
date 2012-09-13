<?php
/**
 * Select Multi
 *
 * @category   Jp7
 * @package    Jp7_View
 * @subpackage Helper
 */
class Jp7_View_Helper_FormSelectmulti extends Zend_View_Helper_FormMultiCheckbox
{
    public function formSelectmulti($name, $value = null, $attribs = null,
        $options = null, $listsep = "<br />\n")
    {
    	//multiCheckbox
    	$multiCheckbox = parent::formMultiCheckbox($name, $value, $attribs, $options, $listsep);
		
		$todos = count($options) == count($value);
		
		ob_start();
		?>
		<label class="check-all">
			<input type="checkbox" <?php echo $todos ? 'checked' : ''; ?> onclick="$(this).closest('dd').find(':checkbox').attr('checked', this.checked);" />Todos
		</label><?php echo $listsep; ?>
		<?php
		$checkall = ob_get_clean();
		
		return $checkall . $multiCheckbox;
    }
}
