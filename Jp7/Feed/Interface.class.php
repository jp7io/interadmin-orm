<?php
/**
 * Interface for Feed support
 */
interface Jp7_Feed_Interface {
	
	/**
	 * Retorna url para o feed
	 * @return string URL absoluta do feed
	 */
	public function getFeedUrl();
	
	/**
	 * Retorna o título para o feed
	 * @return string
	 */
	public function getFeedTitle();
}
?>