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
	 * Retorna o tнtulo para o feed
	 * @return string
	 */
	public function getFeedTitle();
	
	/**
	 * Retorna os registros que serгo adicionados ao feed
	 * @return array
	 */
	public function getFeedRecords();
	
	/**
	 * Retorna a categoria dos feeds.
	 * @return string
	 */
	public function getFeedCategory();
	
	/**
	 * Retorna o mapeamento dos campos para a seзгo.
	 * @return array
	 */
	public function getFeedHelpers();
}