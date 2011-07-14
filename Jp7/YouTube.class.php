<?php

class Jp7_YouTube {
	const URL_PREFIX = 'http://www.youtube.com/v/';
	const SHORT_URL_PREFIX = 'http://youtu.be/';
	
	/**
	 * Gets the link for embedding from the YouTube URL.
	 * 
	 * @param string $youTubeVideoUrl
	 * @return string
	 */
	public static function getEmbedLink($youTubeVideoUrl) {
		if ($id = self::getId($youTubeVideoUrl)) {
			return self::URL_PREFIX . $id;
		} else {
			return $youTubeVideoUrl;
		}
	}
	
	/**
	 * Gets the thumbnail address from the YouTube URL.
	 * 
	 * @param string $youTubeVideoUrl
	 * @param int $size [optional] Default is 0.
	 * @return string
	 */
	public static function getThumbnail($youTubeVideoUrl, $size = 0) {
		if ($id = self::getId($youTubeVideoUrl)) {
			return 'http://img.youtube.com/vi/' . $id . '/' . $size . '.jpg';
		}
	}
	
	/**
	 * Gets the HTML for embedding a Youtube Video.
	 * 
	 * @param string $youTubeVideoUrl
	 * @param int $width [optional] Default is 310.
	 * @param int $height [optional] Default is 230.
	 * @return string
	 */
	public static function getHtml($youTubeVideoUrl, $width = 310, $height = 230) {
		if (startsWith(self::SHORT_URL_PREFIX, $youTubeVideoUrl)) {
			$youTubeVideoUrl = str_replace(self::SHORT_URL_PREFIX, self::URL_PREFIX, $youTubeVideoUrl);
		}
		
		
		$youTubeVideoUrl = self::getEmbedLink($youTubeVideoUrl);
		return '
			<object width="' . $width . '" height="' . $height . '">
				<param name="movie" value="' . $youTubeVideoUrl . '&hl=pt-br&fs=1&"></param>
				<param name="wmode" value="transparent"></param>
				<param name="allowFullScreen" value="true"></param>
				<param name="allowscriptaccess" value="always"></param>
				<param name="allowFullScreen" value="true"></param>
				<embed src="' . $youTubeVideoUrl . '&hl=pt-br&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" wmode="transparent" width="' . $width .'" height="' . $height .'"></embed>
			</object>';
	}
	
	/**
	 * Gets the ID from the YouTube URL. 
	 * 
	 * @param string $youTubeVideoUrl
	 * @return string
	 */
	public static function getId($youTubeVideoUrl) {
		if (strpos($youTubeVideoUrl, 'http://www.youtube.com/user/') === 0) {
			// Channels
			return preg_replace('~(.*)/u/([0-9]*)/(.*)~', '\3', $youTubeVideoUrl);
		} else {
			// Normal link
			return preg_replace('/.*(\?v=|v\/|&v=)([^&]*).*/', '\2', $youTubeVideoUrl);
		}
	}
	
}
