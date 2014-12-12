<?php 

class Jp7_Vimeo {
	protected static $_cache = array();
	
	protected static function _getData($vimeoUrl) {
		$id = self::getId($vimeoUrl);
		if (!$data = self::$_cache[$id]) {
			$data = unserialize(file_get_contents('http://vimeo.com/api/v2/video/' . $id . '.php'));
			self::$_cache[$id] = $data;
		}
		return $data;
	}
	
	public static function getDuration ($vimeoUrl) {
		$data = self::_getData($vimeoUrl);
		return $data[0]['duration'];
	}
		
	public static function getEmbedLink($vimeoUrl) {
		if ($id = self::getId($vimeoUrl)) {
			return 'http://vimeo.com/moogaloop.swf?clip_id=' . $id . '&amp;server=vimeo.com&amp;show_title=0&amp;show_byline=0&amp;show_portrait=0&amp;color=ffffff&amp;fullscreen=1';
		} else {
			return $vimeoUrl;
		}
	}
	
	public static function getThumbnail($vimeoUrl) {
		$data = self::_getData($vimeoUrl);
		return $data[0]['thumbnail_medium'];
	}
	
	public static function getThumbnailLarge($vimeoUrl) {
		$data = self::_getData($vimeoUrl);
		return $data[0]['thumbnail_large'];
	}
	
	public static function getId($vimeoUrl) {
		return preg_replace('/(.*)vimeo.com\/([0-9]+)(.*)/', '\2', $vimeoUrl);
	}
	
	public static function getHtml($vimeoUrl, $width = 310, $height = 230) {
		$vimeoUrl = self::getEmbedLink($vimeoUrl);
		
		return '<object width="' . $width . '" height="' . $height  . '">
			<param name="allowfullscreen" value="true" />
			<param name="allowscriptaccess" value="always" />
			<param name="movie" value="' . $vimeoUrl . '" />
			<param name="wmode" value="transparent" />
			<embed src="' . $vimeoUrl . '" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" wmode="transparent" width="' . $width . '" height="' . $height . '"></embed>
			</object>';
	}
}
