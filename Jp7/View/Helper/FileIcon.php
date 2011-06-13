<?php

class Jp7_View_Helper_FileIcon extends Zend_View_Helper_Abstract {
	
	public function FileIcon($file) {
		$availableIcons = array('doc', 'docx', 'flv', 'gif', 'jpg', 'mp3', 'pdf', 'png', 'pps', 'ppt', 'wan', 'wfl', 'wmv', 'wtl', 'xls', 'zip');
		$ext = $file->getExtension();
		ob_start();
		if (in_array($ext, $availableIcons)) { 
			?>
			<img src="/_default/img/ico_file_<?php echo $ext; ?>.gif" alt="<?php echo $ext; ?>" />
			<?php
		} else {
			echo '.' . $ext;
		}
		return ob_get_clean();
	}
}