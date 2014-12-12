<?php

class Jp7_WordPress_Post extends Jp7_WordPress_RecordAbstract {
	const PK = 'ID';
	
	public function getUrl() {		
		return $this->guid;
	}
	
	public function getImagem() {
		$content = $this->post_content;
		
		if (stripos($content, '<img') !== false) {
            $imgsrc_regex = '#<\s*img [^\>]*src\s*=\s*(["\'])(.*?)\1#im';
            preg_match($imgsrc_regex, $content, $matches);
            unset($imgsrc_regex);
            unset($content);
            if (is_array($matches) && !empty($matches)) {
            	if ($url = $matches[2]) {
            		return $url;	
            	}                
            } else {
                return false;
            }
        } else {
            return false;
        }
	}
	
}