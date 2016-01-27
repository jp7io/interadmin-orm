<?php

class Jp7_View_Helper_FileIcon extends Zend_View_Helper_Abstract
{
    public function FileIcon($file)
    {
        $availableIcons = ['doc', 'docx', 'flv', 'gif', 'jpg', 'mp3', 'pdf', 'png', 'pps', 'ppt', 'wan', 'wfl', 'wmv', 'wtl', 'xls', 'zip', 'xlsx'];
        $ext = $file->getExtension();
        ob_start();
        if (in_array($ext, $availableIcons)) {
            ?>
            <img src="<?= DEFAULT_PATH ?>/img/ico_file_<?php echo $ext;
            ?>.gif" alt="<?php echo $ext;
            ?>" />
			<?php

        } else {
            ?>
			<span class="no-icon">.<?php echo $ext;
            ?></span>
			<?php

        }

        return ob_get_clean();
    }
}
