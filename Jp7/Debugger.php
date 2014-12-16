<?php

class Jp7_Debugger {
	
	public static function syntaxHighlightSql($sql, $style = 'background:#efefef;padding: 2px;margin-top:2px;border-top: 2px solid purple;height:20px;overflow:hidden;') {
		if (!defined('PARSER_LIB_ROOT')) {
			// TODO usar composer
			define('PARSER_LIB_ROOT', base_path() . '/../inc/3thparty/sqlparserlib/');
			echo '<style>';
			readfile(PARSER_LIB_ROOT . 'sqlsyntax.css');
			echo '</style>';
		}
		require_once PARSER_LIB_ROOT . 'sqlparser.lib.php';
		return '<div class="debug_sql" onclick="this.style.height = \'auto\'" style="' . $style . '">' . PMA_SQP_formatHtml(PMA_SQP_parse($sql)) . '</div>';
	}
	
}
