<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category Jp7
 * @package Pagination
 */

/**
 * Creates HTML pagination.
 *
 * @version (2007/06/13)
 * @package Pagination
 */
class Pagination {
	public $records;
	/**
	 * Total of pages.
	 * @var $total
	 */
	public $total;
	/**
	 * Current page.
	 * @var $page
	 */
	public $page;
	/**
	 * Offset item.
	 * @var $init
	 */
	public $init;
	/**
	 * Itens per page.
	 * @var $limit
	 */
	public $limit;
	/**
	 * MySQL LIMIT statement.
	 * @var $sql_limit
	 */
	public $sql_limit;
	
	/**
	 * Creates pagination based on a SQL query, the pagination can be retrieved using its "htm" propertie ($this->htm).
	 *
	 * @param array|string 	$options_or_sql	Array of options | SQL string, by now it needs "records" as a column alias for the total of records, e.g. "SELECT COUNT(id) as records". The default value is <tt>NULL</tt>.
	 * @param int 			$limit 			Itens per page, the default value is 10.
	 * @param int 			$page 			Current page, the default value is $_GET['p_page'].
 	 * @param string 		$type 			Type of the pagination, the available types are "combo", "numbers-top", "numbers-bottom", the default value is "".
	 * @param int 			$numbers_limit 	Maximum number of pages listed, the default value is 10.
	 * @param string 		$parameters		Values to be inserted before the query string when creating links for the pages, the default value is "".
	 * @param string		$separator 		Separator which will be placed between two pages, default value is "|".
	 * @param string 		$next_char 		Character used on the "Next" button or link.
	 * @param string 		$back_char 		Character used on the "Back" button or link.
	 * @param string 		$last_char 		Character used on the "Last" button or link.
	 * @param string 		$first_char 	Character used on the "First" button or link.
	 * @param string 		$records 		Total number of records, it is only used if no $sql is given. The default value is <tt>NULL</tt>.
	 * @param string 		$request_uri 	URI of the current page.
	 * @global ADOConnection
	 * @global string
	 * @return string|Pagination If neither $sql nor $records is given the string "[aa]" is returned.
	 * @author JP, Cristiano
	 * @version (2008/06/26) Update by Carlos
	 */
	function __construct($options_or_sql = null, $limit = 10, $page = null, $type = '', $numbers_limit = 10, $parameters = '', $separator = '|', $next_char = '&gt;', $back_char = '&lt;', $last_char = '&raquo;', $first_char = '&laquo;', $records = null, $request_uri = null) {
		// Para receber options
		if (func_num_args() == 1 && is_array($options_or_sql)) {
			$sql = null;
			$options = &$options_or_sql;
			extract($options);
		} else {
			$sql = $options_or_sql;
		}
		
		global $db, $seo;
		
		if (is_null($page)) {
			$page = $_GET['p_page'];
		}
		
		if ($sql) {
			if ($GLOBALS["jp7_app"]) $rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
			else $rs = interadmin_query($sql);	
			$row = $rs->FetchNextObj();
			$this->records = $row->records;
			$rs->Close();
		} else {
			if (isset($records)) {
				$this->records = $records;
			} else {
			 	return '[aa]';	
			}
		}
		
		$this->total = ceil($this->records / $limit); // Total de Paginas
		$this->page = ($page > $this->total) ? $this->total : $page; // Pagina Atual

		if (!intval($this->page)) $page = $this->page = 1;
		
		$this->init = (($this->page - 1) * $limit); // Item inicial
		$this->limit = $limit; // Itens por pagina
		
		$this->sql_limit = " LIMIT " . $this->init . "," . $this->limit;
		
		// HTM
		$this->query_string = preg_replace('([&]?p_page=[0-9]+)', '', $_SERVER['QUERY_STRING']); // Retira a pagina atual da Query String
		if ($seo) $this->query_string = preg_replace('([&]?baseurl=true)', '', $this->query_string); // Retira a baseurl se a pagina tiver S.E.O.
		$this->query_string = preg_replace('([&]?go_url=' . $_GET['go_url'] . ')', '', $this->query_string); // Retira a GO Url da Query String
		if ($this->query_string[0] == '&') {
			$this->query_string = substr($this->query_string,1); // Limpa & que sobrou no começo da string
		}
		$this->parameters = $parameters;
		$this->request_uri = (is_null($request_uri)) ? preg_replace('/[?](.*)/', '', $_SERVER['REQUEST_URI']): $request_uri;
		
		//$this->query_string=substr($this->query_string,1);
		
		foreach ($_POST as $key=>$value) {
			if ($key != 'p_page') $this->query_string .= '&' . $key . '=' . $value; // Adiciona valores do POST na Query String
		}

		if ($this->total) { // Se houverem paginas
			if ($this->total > 1) { // E se houver mais de uma pagina
				// Numbers
				$this->htm_numbers_extra = $this->htm_numbers = '<div class="numbers"><ul>';

				$min = $page - ceil($numbers_limit / 2);  // Codigo novo. Exemplo: 1 2 3 4 [5] 6 7 8 9 10
				$max = $min + $numbers_limit - 1;
				if ($min < 1) {
					$min = 1;
					$max = $min + $numbers_limit - 1;
				}
				if ($max > $this->total) $max = $this->total;
				
				if ($page !=1 && $this->total > 2 && $page > 2) $this->htm_numbers_extra .= $this->_createLink(1, $first_char , ' class="' . (($page == 1) ? 'back-off"' :'bgleft_plus"'));
				$this->htm_numbers_extra .= $this->_createLink($page - 1, $back_char, ' class="' . (($page == 1) ? 'back-off"' :'bgleft"'));
				for ($i = $min; $i <= $max; $i++) {
					$this->htm_numbers .= $this->_createLink($i, $i, ($i == $page) ? ' class="on"' : '');
					$this->htm_numbers_extra .= $this->_createLink($i, $i, ($i == $page) ? ' class="on"' : '');
					if ($i != $max) {
						$this->htm_numbers .= '<li class="separator">' . $separador."</li>";
						$this->htm_numbers_extra .= '<li class="separator">' . $separador . '</li>';
					}
				}
				rs . $this->query_string . '&p_page=' . $this->total . '\'">' . $go_char_plus . '</li>';
				$this->htm_numbers_extra .= $this->_createLink($page + 1, $next_char, ' class="' . (($page == $this->total) ? 'go-off"' : 'bgright"'));
				if ($page != $this->total && $this->total > 2 && $page < ($this->total - 1)) $this->htm_numbers_extra .= $this->_createLink($this->total, $last_char, ' class="' . (($page == $this->total) ? 'go-off"' : 'bgright_plus"'));
				$this->htm_numbers_extra .= '</ul></div>';
				$this->htm_numbers .= '</ul></div>';
			}
			// Combo
			$this->htm_combo = '<div class="text">Página</div>' .
			'<select onchange="location=\'' . $this->request_uri  . '?' . $parameters . $this->query_string . '&p_page=\'+this[selectedIndex].value">' . "\n" .
			'<script>jp7_num_combo(1,' . $this->total . ',' . $page . ')</script>' .
			'</select>' . "\n" . '<div class="text">de ' . $this->total . '</div>' . "\n";
			// Buttons
			
			$this->htm_back = '<input type="button" class="back' . (($page == 1) ? ' back-off' : '') . '" onclick="location=\'' . $this->request_uri . '?' . $parameters . $this->query_string . '&p_page=' . ($page - 1) . '\'"' . (($page == 1) ? ' disabled' : '') . '>' . "\n";
			$this->htm_go = '<input type="button" class="go' . (($page == $this->total) ? ' go-off' : '') . '" onclick="location=\'' . $this->request_uri . '?' . $parameters . $this->query_string . '&p_page=' . ($page + 1) . '\'"' . (($page == $this->total) ? ' disabled' : '') . '>' . "\n";
				
			// Types
			$this->htm = '<div class="jp7_db_pages" style="width:auto"><div class="' . $type . '">' . "\n";
			if ($type == 'combo') $this->htm .= $this->htm_back . $this->htm_combo . $this->htm_go;
			elseif ($type == 'numbers-top') $this->htm .= $this->htm_numbers . $this->htm_back . $this->htm_go;
			elseif ($type == 'numbers-bottom') $this->htm .= $this->htm_back . $this->htm_go . $this->htm_numbers;
			else $this->htm .= $this->htm_back . $this->htm_numbers . $this->htm_go;
			$this->htm .= '</div></div>' . "\n";
		}
	}
	/**
	 * Creates links for the pagination numbers.
	 *
	 * @param int $pageNumber Number of the page the link will point to.
	 * @param int $pageLabel Label of the link.
	 * @param int $className Class used in the link, e.g. class="on".
 	 * @return string Returns a "li" tag containing an "a" tag with a link.
	 * @author Carlos
	 * @version (2008/06/13)
	 */
	private function _createLink($pageNumber, $pageLabel, $className = '') {
		return '<li' . $className . '><a href="' .  $this->request_uri . '?' . $this->parameters . $this->query_string . (($this->parameters || $this->query_string) ? '&' : '') . 'p_page=' . $pageNumber . '">' . $pageLabel . '</a></li>';
	}

	public function __toString() {
		return $this->init . ',' . $this->limit;	
	}
}
