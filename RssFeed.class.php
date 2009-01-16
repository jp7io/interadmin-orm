<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category JP7
 * @package RssFeed
 */

/**
 * Generates a RSS Feed.
 *
 * @version (2008/07/11)
 * @author Carlos Rodrigues
 * @package RssFeed
 */
class RssFeed extends DOMDocument {
	/**
	 * @var DOMElement Tag channel on the RSS Document.
	 */
	protected $_channel = NULL;
	/**
	 * @var array Array of DOMElements, which are the itens on the RSS Feed.
	 */
	protected $_itens = array();
	/**
	 * @var array Matches between the array keys and the XML tag names.
	 */
	protected $_keysMatch = array(
		'title' => 'varchar_key',
		'link' => 'varchar_1',
		'description' => 'text_1',
		/*'author' => '',
		'category' => '',
		'comments' => '',*/
		'pubDate' => 'date_publish'/*,
		'guid' => ''*/
	);
	/**
	 * @var array Configuration of channel properties, i.e. title, link, description, docs, generator, managingEditor and webMaster.
	 */
	protected $_channelData = array(
		'title' => '',
		'link' => '',
		'description' => '',
		'lang' => '', 
		'pubDate' => '',
		'lastBuildDate' => '',
		'docs' => 'http://blogs.law.harvard.edu/tech/rss',
		'generator' => 'JP7 InterAdmin',
		'managingEditor' => '',
		'webMaster' => 'debug@jp7.com.br'
	);
	/**
	 * @var bool Sets whether contents (description) will be wrapped by a CDATA tag or not.
	 */
	protected $_cdata = FALSE;
	/**
	 * Creates a RssFeed object.
	 * 
 	 * @param string RSS version of the document.
	 * @param string The encoding of the document as part of the XML declaration. 
 	 * @param string The version number of the document as part of the XML declaration. 
	 */
	function __construct($rssVersion = '2.0', $encoding = 'ISO-8859-1', $xmlVersion = '1.0') {
		global $lang;

		parent::__construct($xmlVersion, $encoding);
		$this->formatOutput = TRUE;
	
		$this->_channelData['lang'] = $lang->lang;
		$this->_channelData['pubDate'] = date('r');
		$this->_channelData['lastBuildDate'] = date('r');
		
		$rss = $this->appendChild($this->createElement('rss'));
		$rss->setAttribute('version', $rssVersion);
		$this->_channel = $rss->appendChild($this->createElement('channel'));
	}
	/**
	 * Adds data to the RSS feed parsing an array of objects or arrays, each of them with indexes matching the keysMatch property.
	 *
	 * @param array Array with keys matching the itens on $_keysMatch.
 	 * @return string RSS Document.
	 * @todo UT8 Support needs to be tested. DOMDocument does not accept any ISO special characters. It is a problem that forces us to use a fake ISO with utf8_encode().
	 */
	public function add($rows) {
		foreach((array)$rows as $row) {
			$row = (array) $row;
			$item = $this->createElement('item');
			foreach($this->_keysMatch as $rssItem => $rowItem) {
				if (!$rowItem) continue;
				if ($this->actualEncoding == 'ISO-8859-1') $row[$rowItem] = utf8_encode($row[$rowItem]);
				if ($this->isCdata() && $rssItem == 'description') {
					$item->appendChild($this->createElement($rssItem))->appendChild($this->createCDATASection($row[$rowItem]));
				} else {
					$item->appendChild($this->createElement($rssItem, $this->xmlEntities($row[$rowItem])));
				}
			}
			$this->_itens[] = $item;
		}
	}
	/**
	 * Removes all itens on the RSSFeed.
	 *
 	 * @return void
	 */
	public function clear() {
		$this->_itens = array();
	}
	/**
	 * Sets the item identified by the given index.
	 * 
	 * @param int $i Index of the array of itens.
	 * @param DOMElement $node DOMElement to be stored.
 	 * @return void
	 */
	public function setItem($i, $node) {
		$this->_itens[$i] = $node;
	}
	/**
	 * Gets the item identified by the given index.
	 * 
	 * @param int $i Index of the itens array.
 	 * @return DOMElement
	 */
	public function getItem($i) {
		$this->_itens[$i];
	}
	/**
	 * Gets the total number of itens.
	 * 
 	 * @return int Itens count.
	 */
	public function itensCount() {
		return count($this->_itens);
	}
	/**
	 * Sets the title.
	 * @param string $title
	 * @return void
	 **/
	public function setTitle($title){
		$this->_channelData['title'] = ($this->actualEncoding == 'ISO-8859-1') ? utf8_encode($title) : $title;
	}
	/**
	 * Gets the title.
	 * @return string Returns the title.
	 **/
	public function getTitle(){
		return $this->_channelData['title'];
	}
	/**
	 * Sets the link.
	 * @param string $link
	 * @return void
	 **/
	public function setLink($link){
		$this->_channelData['link'] = $link;
	}
	/**
	 * Gets the title.
	 * @return string Returns the title.
	 **/
	public function getLink(){
		return $this->_channelData['link'];
	}
	/**
	 * Sets the description.
	 * @param string $description
	 * @return void
	 **/
	public function setDescription($description){
		$this->_channelData['description'] = ($this->actualEncoding == 'ISO-8859-1') ? utf8_encode($description) : $description;
	}
	/**
	 * Gets the title.
	 * @return string Returns the title.
	 **/
	public function getDescription(){
		return $this->_channelData['description'];
	}
	/**
	 * Sets CDATA sections TRUE or FALSE.
	 * @param bool $cdata
	 * @return void
	 **/
	public function setCdata($cdata){
		$this->_cdata = (bool) $cdata;
	}
	/**
	 * Gets the CDATA value.
	 * @return bool Returns the CDATA state.
	 **/
	public function isCdata(){
		return $this->_cdata;
	}
	/**
	 * Sets keysMatch array.
	 * @param array $keysMatch
	 * @return void
	 **/
	public function setKeysMatch($keysMatch){
		$this->_keysMatch = (array) $keysMatch;
	}
	/**
	 * Gets the keysMatch array.
	 * @return array Array of matches.
	 **/
	public function getKeysMatch(){
		return $this->_keysMatch;
	}
	/**
	 * Binds the given tag to the given key on the matches array.
	 * @return void
	 **/
	public function bind($tag, $key){
		$this->_keysMatch[$tag] = $key;
	}
	/**
	 * Unbinds the given tag from its key on the matches array.
	 * @return void
	 **/
	public function unbind($tag){
		$this->_keysMatch[$tag] = '';
	}
	/**
	 * Returns XML file contents for this RSS Feed.
	 * @param bool $headers Sets whether it will send headers(content-type, charset) before saving the XML.
	 * @return void
	 **/
	public function saveXML($headers = TRUE){
		foreach ($this->_channelData as $child=>$value) {
			if ($value) $this->_channel->appendChild($this->createElement($child, $value));
		}
		foreach ($this->_itens as $item) {
			$this->_channel->appendChild($item);
		}
		
		if ($headers) header("content-type: application/xml; charset=" . $this->encoding );
		return parent::saveXML();
	}
	/**
	 * Converts HTML entities to UTF-8 NCR (Numeric Character Reference) since it is not handled by DOMDocument.
	 *
	 * @param string Input string.
 	 * @return string String with NCRs replacing special characters.
	 */
	public function xmlEntities($str){
		static $conversionTable;
		if (!$conversionTable) $conversionTable = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);
		$str = str_replace('&', '&amp;', $str);
		foreach ($conversionTable  as $k => $v) {
			if ($k == '&') continue;
			$str = str_replace($v, '&#' . ord($k) . ';', $str);
		}
		return $str;
	}
}
?>