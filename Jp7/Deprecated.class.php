<?php
class Jp7_Deprecated {
	/**
	 * Updates a record on the given table using values from global variables.
	 *
	 * @param string $table Name of the table where it will update data.
	 * @param string $table_id_name Name of the key field.
	 * @param mixed $table_id_value Value expected for the key field.
	 * @param string $fields Names of the fields that will be updated separated by comma (,). e.g. 'name1,name2,name3'.
	 * @global ADOConnection
	 * @return NULL Nothing is returned.
	 * @author JP
	 * @version (2006/04/18)
	 */
	public static function jp7_db_update($table, $table_id_name, $table_id_value, $fields) {
		global $db;
		$fields_arr=explode(",",$fields);
		// Vari·veis
		foreach($fields_arr as $field){
			$fields_arr_db[]=(strpos($field,"_")===0)?substr($field,1):$field;
		}
		foreach($fields_arr_db as $field_db){
			eval("global \$".$field_db.";");
		}
		// Update Concatenado (_)
		$sql = "SELECT ".implode(",",$fields_arr_db)." FROM ".$table." WHERE ".$table_id_name."=".$table_id_value;
		$rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(),$sql));
		if ($row =(array)$rs->FetchNextObj()){
			foreach($fields_arr as $field){
				if(strpos($field,"_")===0){
					$field=substr($field,1);
					eval("\$".$field.".=\"".$row[$field]."\";");
				}
			}
		}
		$rs->Close();
		// Update
		$sql = "UPDATE ".$table." SET ";
		for($i = 0;$i<count($fields_arr_db);$i++){
			eval("\$field_value=\$".$fields_arr_db[$i].";");
			$sql.=$fields_arr_db[$i]."='".$field_value."'";
			if($i!=count($fields_arr_db)-1)$sql.=",";
		}
		$sql.=" WHERE ".$table_id_name."=".$table_id_value;
		$rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(),$sql));
	}
	
	/**
	 * Runs a SQL query and returns its recordset.
	 *
	 * @param string $sql SQL query which will be executed.
	 * @param ADOConnection $sql_db Database which will be used, the default value is "".
	 * @param bool $sql_debug Formats and prints the SQL string for debug purposes, the default value is <tt>FALSE</tt>.
	 * @param int $numrows Number of records to be retrieved from the database, the default value is <tt>NULL</tt>.
	 * @param int $offset Number of ignored records before is starts retrieving, the default value is <tt>NULL</tt>.
	 * @return ADORecordSet Recordset object.
	 * @todo Check if 'if($rs&&$sql)eval("global \$".$rs.";\$".$rs."=\$rs_pre;");' is needed.
	 * @author JP
	 * @version (2007/03/04)
	 */
	public static function interadmin_query($sql, $sql_db = "", $sql_debug = FALSE, $numrows = NULL, $offset = NULL){
		global $config;
		global $c_path_upload;
		global $s_session;
		global $db;
		global $db_prefix;
		global $lang;
		global $debugger;
	
		$DbNow = $db->BindTimeStamp(date("Y-m-d H:i:s"));
	
		// Debug - Before SQL injection
		$debugger->showSql($sql, $sql_debug, 'color:#FFFFFF;background:#444444;');
	
		// Split
		$sql_slipt = preg_replace(array('/([	 ])(FROM )/','/([	 ])(WHERE )/','/([ 	])(ORDER BY )/'), '{;}\1\2', $sql, 1);
		$sql_slipt = explode("{;}", $sql_slipt);
		foreach ($sql_slipt as $value) {
			if(!$sql_select && strpos($value, "SELECT ") !== FALSE) $sql_select = $value;
			if(!$sql_from && strpos($value, "FROM ") !== FALSE) $sql_from = $value;
			if(!$sql_where && strpos($value, "WHERE ") !== FALSE) $sql_where = $value;
			if(!$sql_final && strpos($value, "ORDER BY ") !== FALSE) $sql_final = $value;
		}
		// Parser
		preg_match_all("(([^ ,]+) AS ([^ ,]+))", $sql_from, $out, PREG_PATTERN_ORDER);
		if (count($out[1])) {
			// Com Alias
			foreach ($out[1] as $key=>$value) {
				$alias = $out[2][$key];
				if (strpos($value, '_tipos') === (strlen($value) - strlen('_tipos'))) {
					$sql_where = str_replace("WHERE ","WHERE (" . $alias . ".mostrar<>'' OR " . $alias . ".mostrar IS NULL) AND (" . $alias . ".deleted_tipo='' OR " . $alias . ".deleted_tipo IS NULL) AND ", $sql_where);
				} elseif (strpos($value, '_tags') === (strlen($value) - strlen('_tags'))) {
					// do nothing
				} elseif (strpos($value, $db_prefix . $lang->prefix . '_arquivos')!==false || strpos($value, $db_prefix . '_arquivos') !== false) {
					$sql_where = str_replace("WHERE ","WHERE " . $alias . ".mostrar<>'' AND (" . $alias . ".deleted='' OR " . $alias . ".deleted IS NULL) AND ", $sql_where);
				} else {
					$sql_where_replace = '' .
							"WHERE (" . $alias . ".date_publish<='" . $DbNow . "' OR " . $alias . ".date_publish IS NULL)" .
							" AND (" . $alias . ".date_expire>'" . $DbNow . "' OR " . $alias . ".date_expire IS NULL OR " . $alias . ".date_expire='0000-00-00 00:00:00')" .
							" AND (" . $alias . ".char_key<>'' OR " . $alias . ".char_key IS NULL)" .
							" AND (" . $alias . ".deleted='' OR " . $alias . ".deleted IS NULL)" .
							(($config->interadmin_preview && !$s_session['preview']) ? " AND (" . $alias . ".publish<>'' OR " . $alias . ".publish IS NULL)" : "") . " AND ";
					$sql_where = str_replace("WHERE ", $sql_where_replace, $sql_where);
				}
				if ($c_path_upload) {
					$sql_select = preg_replace('/([ ,])' . $alias . '.file_([0-9])/', '\1REPLACE(' . $alias . '.file_\2,\'../../upload/\',\'' . $c_path_upload . '\') AS file_\2', $sql_select);
				}
			}
		} else {
			// Sem Alias
			preg_match_all("([ ,]+[".$db_prefix."][^ ,]+)", $sql_from, $out, PREG_PATTERN_ORDER);
			foreach ($out[0] as $key=>$value) {
				if (strpos($value, $db_prefix."_tipos")!==false) {
					$sql_where = str_replace("WHERE ","WHERE mostrar<>'' AND (deleted_tipo='' OR deleted_tipo IS NULL) AND ", $sql_where);
				} elseif (strpos($value, $db_prefix."_tags")!==false) {
					// do nothing
				} elseif (strpos($value, $db_prefix . $lang->prefix . '_arquivos') !== false || strpos($value, $db_prefix . '_arquivos') !== false) {
					$sql_where = str_replace("WHERE ","WHERE mostrar<>'' AND (deleted LIKE '' OR deleted IS NULL) AND ", $sql_where);
				} else {
					$sql_where = str_replace("WHERE ", "WHERE" .
							" date_publish <= '" . $DbNow . "'" .
							" AND char_key <> ''" .
							" AND (deleted LIKE '' OR deleted IS NULL)" .
							" AND (date_expire > '" . $DbNow . "' OR date_expire IS NULL OR date_expire = '0000-00-00 00:00:00')" .
							(($config->interadmin_preview && !$s_session['preview']) ? " AND (publish <> '' OR publish IS NULL)" : "") . " AND ", $sql_where);
				}
			}
			if ($c_path_upload) {
				$sql_select = preg_replace('/([ ,])file_([0-9])/','\1REPLACE(file_\2,\'../../upload/\',\''.$c_path_upload.'\') AS file_\2', $sql_select);
			}
		}
		// Join
		$sql = $sql_select . $sql_from . $sql_where . $sql_final;
		// Debug - After SQL injection
		$debugger->showSql($sql, $sql_debug);
	
		// Return
		if ($debugger->active) $debugger->startTime();
		if($sql_db){
			if(isset($numrows) && isset($offset))
				$rs_pre = $sql_db->SelectLimit($sql, $numrows, $offset) or die(jp7_debug($db->ErrorMsg(), $sql));
			else
				$rs_pre = $sql_db->Execute($sql) or die(jp7_debug($sql_db->ErrorMsg(), $sql));
		} else{
			if (isset($numrows) && isset($offset))
				$rs_pre = $db->SelectLimit($sql, $numrows, $offset) or die(jp7_debug($db->ErrorMsg(), $sql));
			else
				$rs_pre = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
		}
		if ($debugger->active) $debugger->addLog($sql, 'sql', $debugger->getTime($_GET['debug_sql']));
	
		if ($rs && $sql) eval("global \$" . $rs . ";\$" . $rs . "=\$rs_pre;");
		else return $rs_pre;
	}
	/**
	 * Creates a list from values on the database.
	 *
	 * @param string $table Name of the table containing the itens.
	 * @param int $id_tipo ID of the type.
	 * @param int $id ID of the current item.
	 * @param string $type Type of the list, the available values are: "combo" or "list", the default value is "list".
	 * @param string $order SQL string to be placed after the "ORDER BY" statement, the default value is "int_key,date_publish,varchar_key".
	 * @param string $field Name of the field which will be used as label on the list, the default value is "varchar_key".
	 * @param string $sql_where Additional SQL string to be placed after the "WHERE" statement, it must start with "AND ", the default value is "".
	 * @param bool $seo.
	 * @global ADOConnection
	 * @global bool
	 * @global string
	 * @return string Generated HTML code for a combobox or a list.
	 * @author JP
	 * @version (2009/06/13)
	 */
	public static function interadmin_list($table,$id_tipo,$id,$type="list",$order="int_key,date_publish,varchar_key",$field="varchar_key",$sql_where="",$seo=FALSE) {
		global $db, $s_session, $l_selecione, $config;
		//global $id;
		if($type=="list"){
			$S="".
					"<div class=\"lista\">\n".
					"<ul class=\"nivel-3\">\n";
		}elseif($type=="combo"){
			$S="".
					"<option value=\"\">".$l_selecione."</option>\n".
					"<option value=\"\">--------------------</option>\n";
		}
		$sql = "SELECT id,".$field." AS field FROM ".$table.
		" WHERE id_tipo=".$id_tipo.
		" AND char_key<>''".
		(($s_session['preview'] || !$config->interadmin_preview)?"":" AND publish<>''").
		" AND (deleted='' OR deleted IS NULL)".
		" AND date_publish<='".date("Y/m/d H:i:s")."'".
		$sql_where.
		" ORDER BY ".$order;
		$rs=$db->Execute($sql)or die(jp7_debug($db->ErrorMsg(),$sql));
		while ($row = $rs->FetchNextObj()) {
			if($seo){
				if($type=="combo")$S.="<option value=\"".toSeo($row->field)."\"".((toId($row->field)==$id)?" selected=\"selected\" class=\"on\"":"").">".toHTML($row->field)."</option>\n";
				else $S.="<li".(($row->id==$id)?" class=\"on\"":"")."><a href=\"?id=".$row->id."\">".toHTML($row->field)."</a></li>\n";
			}else{
				if($type=="combo")$S.="<option value=\"".$row->id."\"".(($row->id==$id)?" selected=\"selected\" class=\"on\"":"").">".toHTML($row->field)."</option>\n";
				else $S.="<li".(($row->id==$id)?" class=\"on\"":"")."><a href=\"?id=".$row->id."\">".toHTML($row->field)."</a></li>\n";
			}
		}
		$rs->Close();
		if($type=="list"){
			$S.="".
					"</ul>\n".
					"</div>\n";
		}
		return $S;
	}
	/**
	 * Gets values from a specified record on the database. It has 3 behaviors as explained on the parameters' description.
	 *
	 * @param int|string $table_or_id If it is numeric, it will be the ID value, otherwise it will be the name of the table.
	 * @param int|string $field_or_id If $table_or_id is numeric it will be the name of the fields, if $table_or_id is not numeric and it is numeric, it will be the ID value, otherwise it will be the name of the key field.
	 * @param string $id_value If $table_or_id is not numeric and $field_or_id is numeric, it will be the name of the fields, if both are not numeric it will be the ID value.
	 * @param string $field_name If $table_or_id and $field_or_id are not numeric, it will be the name of the fields.
	 * @param bool $OOP If <tt>TRUE</tt> an object will be returned even when there is only one result, the default value is <tt>FALSE</tt>.
	 * @global ADOConnection
	 * @global string
	 * @global string
	 * @return mixed Returns an object containing the values. If there is only one value it returns the value itself, except if $OOP is <tt>TRUE</tt>.
	 * @author JP
	 * @todo ($GLOBALS["jp7_app"]=='intermail') will not be TRUE, since the previous condition ($GLOBALS['db_type']) is TRUE on the Intermail.
	 * @version (2008/09/17)
	 */
	public static function jp7_fields_values($table_or_id, $field_or_id = '', $id_value = '', $field_name = '', $OOP = false) {
		global $db;
		global $s_session;
		// Force objects as strings (eg.: select_key, etc.)
		if (is_object($table_or_id)) {
			$table_or_id = strval($table_or_id);
		}
		if (is_object($field_or_id)) {
			$field_or_id = strval($field_or_id);
		}
		if (is_numeric($table_or_id)) {
			// ($id,$field)
			global $db_prefix, $lang;
			$table = $db_prefix . $lang->prefix;
			$table_id_name = 'id';
			$table_id_value = $table_or_id;
			$fields = $field_or_id;
		} elseif (is_numeric($field_or_id)) {
			// ($table,$id,$field)
			$table = $table_or_id;
			$table_id_name = 'id';
			$table_id_value = $field_or_id;
			$fields = $id_value;
		} else {
			// ($table,$table_id_name,$table_id_value,$field)
			$table = $table_or_id;
			$table_id_name = $field_or_id;
			$table_id_value = $id_value;
			$fields = $field_name;
		}
	
		if(!$fields)$fields="varchar_key";
		if(is_array($fields)){
			$fields_arr = $fields;
			$fields	= implode(',', $fields_arr);
		} else {
			$fields_arr = explode(',', $fields);
		}
		if ($table_id_value) {
			$sql = "SELECT ".$fields.
			" FROM ".$table.
			" WHERE ".$table_id_name."='".$table_id_value."'";
			if (!$GLOBALS['jp7_app'] && strpos($table, '_tipos') === false) {
				$sql .= "" .
						(($GLOBALS['c_publish']&&!$s_session['preview']) ? " AND publish <> ''" : "") .
						" AND (deleted = '' OR deleted IS NULL)" .
						" AND date_publish <= '".date("Y/m/d H:i:s")."'";
			}
			if ($GLOBALS['db_type']) {
				$rs = $db->Execute($sql)or die(jp7_debug($db->ErrorMsg(), $sql));
				if ($row = $rs->FetchNextObj()) {
					if (count($fields_arr) > 1 || $OOP) {
						foreach ($fields_arr as $field) {
							$O->$field = $row->$field;
						}
					} else $O = $row->$fields;
				}
				$rs->Close();
				return $O;
			} else {
				$rs = ($GLOBALS["jp7_app"]=='intermail') ? $db-Execute($sql) : interadmin_mysql_query($sql);
				if ($row = $rs->FetchNextObj()) {
					if (count($fields_arr) > 1) {
						foreach ($fields_arr as $field){
							$O->$field = $row->$field;
						}
					} else $O = $row->$fields;
				}
				$rs->Close();
				return $O;
			}
		}
	}
	// moveFiles (2003/03/21)
	public static function moveFiles($from_path,$to_path){
		if(!file_exists($to_path))mkdir($to_path,0777);
		$this_path=getcwd();
		if(is_dir($from_path)){
			chdir($from_path);
			$handle=opendir(".");
			while(($file=readdir($handle))!==false){
				if(($file!=".")&&($file!="..")){
					if(is_dir($file)){
						@copyDir($from_path."/".$file,$to_path."/".$file);
						chdir($from_path);
					}
					if(is_file($file)){
						copy($from_path."/".$file,$to_path."/".$file);
						unlink($from_path."/".$file);
					}
				}
			}
			closedir($handle);
		}
		chdir($this_path);
	}
	
	/**
	 * Performs common tasks on index pages, caching and redirecting to the home page.
	 *
	 * @param string $lang Current language.
	 * @global Browser
	 * @global string
	 * @global bool
	 * @global bool
	 * @global string
	 * @return NULL
	 * @author JP
	 * @version (2008/01/11)
	 * @deprecated jp7_index() is not needed since the redirects are managed by RewriteRule in .htaccess
	 */
	public static function jp7_index($lang=""){
		session_start();
		//global $HTTP_ACCEPT;
		global $is, $path, $publish, $s_session, $config;
		$path=dirname($_SERVER["SCRIPT_NAME"]);
		$path=jp7_path("http://".$_SERVER['HTTP_HOST'].$path);
		// Publish Check
		$admin_time=@filemtime("interadmin.log");
		$index_time=@filemtime("site/home/index_P.htm");
		if($admin_time>$index_time||date("d")!=date("d",$index_time))$publish=true;
		// Redirect
		//if(strpos($_SERVER['HTTP_ACCEPT'],"/vnd.wap")!==false)header("Location: ".$path."wap/home/index.php");
		//elseif($is->v<4&&!$is->robot)header("Location: /_default/oldbrowser.htm");
		//else{
		$path=$path.(($lang&&$lang!=$config->lang_default)?$lang:"site")."/home/".(($publish||!$admin_time||!$index_time)?"index.php":"index_P.htm").(($s_session['preview'])?"?s_interadmin_preview=".$s_session['preview']:"");
		@ini_set("allow_url_fopen","1");
		//if(!@include $path.(($s_session['preview'])?"&":"?")."HTTP_USER_AGENT=".urlencode($_SERVER['HTTP_USER_AGENT']))header("Location: ".$path);
		if(!@readfile($path.(($s_session['preview'])?"&":"?")."HTTP_USER_AGENT=".urlencode($_SERVER['HTTP_USER_AGENT'])))header("Location: ".$path);
		//}
	}
	
	/**
	 * Generates the code for inserting Flash(.swf) files, or an image when its not a flash file.
	 *
	 * @param string $src URL of the Flash file.
	 * @param int $w Width.
	 * @param int $h Height.
	 * @param string $alt Alternative text for the image.
	 * @param string $id ID of the "object" tag.
	 * @param string $xtra Additional parameters for the "object" tag.
	 * @param string $parameters Additional "param" tags.
	 * @global Browser
	 * @return string Generated HTML code.
	 * @version (2005/11/18)
	 */
	public static function jp7_flash($src,$w,$h,$alt="",$id="",$xtra="",$parameters=""){
		$pos1=strpos($src,"?");
		$ext=($pos1)?substr($src,0,$pos1):$src;
		$pos1=strrpos($ext,".")+1;
		$ext=substr($ext,$pos1);
		if($ext=="swf"){
			if(!$parameters)$parameters=array(wmode=>"transparent");
			global $is;
			foreach($parameters as $key=>$value){
				$S2.="<param name=\"".$key."\" value=\"".$value."\" />\n";
			}
			$S="".
					"<object".(($id)?" id=\"".$id."\"":"").
					" type=\"application/x-shockwave-flash\"".
					//(($is->ie&&$is->win)?" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0\"":"").
			" data=\"".$src."\"".
			(($w&&$h)? " width=\"".$w."\" height=\"".$h."\"":"").
			(($xtra)?" ".$xtra:"").">\n";
			$S.="".
					"<param name=\"pluginurl\" value=\"http://www.macromedia.com/go/getflashplayer\" />\n".
					"<param name=\"movie\" value=\"".$src."\" />\n".
					"<param name=\"quality\" value=\"high\" />\n".
					$S2.
					"</object>";
			if($is->ie&&$is->win){
				if($id){
					$S.="".
							"<script type=\"text/vbscript\" language=\"vbscript\">\n".
							"on error resume next\n".
							"sub ".$id."_FSCommand(ByVal command,ByVal args)\n".
							"call flash_DoFSCommand(command,args)\n".
							"end sub\n".
							"</script>\n";
				}
			}else{
				if($id){
					$S.="".
							"<script type=\"text/javascript\">\n".
							"function ".$id."_DoFSCommand(command,args){\n".
							"flash_DoFSCommand(command,args)\n".
							"}\n".
							"</script>\n";
				}
			}
			return $S;
		}else{
			if($w=="100%"||$h=="100%"){
				$w="";
				$h="";
			}
			return "<img src=\"".$src."\"".(($w&&$h)? " width=".$w." height=".$h:"")." border=\"0\" alt=\"".$alt."\"".(($id)?" name=\"".$id."\"":"").(($xtra)?" ".$xtra:"")."/>";
		}
	}
	
	/**
	 * Takes off diacritics from a string and replaces linebreaks by <br/>.
	 *
	 * @param string $S The input string.
	 * @global bool
	 * @return string Formatted string.
	 * @todo It still needs to be documented the usage of global $html.
	 * @version (2005/08/10)
	 */
	public static function wap_toHTML($S){
		global $html;
		if(!$html)$S = str_replace("$","$$",$S);
		$S=str_replace(chr(13),"<br/>",$S);
		$S=str_replace("<br>","<br/>",$S);
		$S=preg_replace("([·‡„‚‰™])","a",$S);
		$S=preg_replace("([ÈËÍÎ&])","e",$S);
		$S=preg_replace("([ÌÏÓÔ])","i",$S);
		$S=preg_replace("([ÛÚıÙˆ∫])","o",$S);
		$S=preg_replace("([˙˘˚¸])","u",$S);
		$S=preg_replace("([Á])","c",$S);
		$S=preg_replace("([Ò])","n",$S);
		$S=preg_replace("([¡¿√¬ƒ])","A",$S);
		$S=preg_replace("([…» À&])","E",$S);
		$S=preg_replace("([ÕÃŒœ])","I",$S);
		$S=preg_replace("([”“’‘÷])","O",$S);
		$S=preg_replace("([⁄Ÿ€‹])","U",$S);
		$S=preg_replace("([«])","C",$S);
		$S=preg_replace("([—])","N",$S);
		return $S;
	}
	
	/**
	 * Changes the case of common HTML tags to lowercase, changes the align attribute on <p>, and close <br> tags, adapting it to XHTML standards. The affected tags are: <P>, <BR>, <IMG>, <TABLE>, <TR>, <TH> and <TD>.
	 *
	 * @param string $S HTML string.
	 * @return string XHTML string.
	 * @version (2005/10/19)
	 */
	public static function toXHTML($S){
		$S=str_replace("<P>","<p>",$S);
		$S=str_replace("<P ","<p ",$S);
		$S=str_replace("</P>","</p>",$S);
		$S=str_replace("<BR>","<br />",$S);
		$S=str_replace("<IMG ","<img ",$S);
		$S=str_replace("<TABLE","<table",$S);
		$S=str_replace("<TR","<tr",$S);
		$S=str_replace("<TH","<th",$S);
		$S=str_replace("<TD","<td",$S);
		$S=str_replace("</TABLE","</table",$S);
		$S=str_replace("</TR","</tr",$S);
		$S=str_replace("</TH","</th",$S);
		$S=str_replace("</TD","</td",$S);
		$S=str_replace("<p align=left>","<p>",$S);
		$S=str_replace("<p align=justify>","<p>",$S);
		$S=str_replace("<p align=center>","<p style=\"text-align:center\">",$S);
		$S=str_replace("<p align=right>","<p style=\"text-align:right\">",$S);
		return $S;
	}
	
	/**
	 * Formats and prints the elements of an array or object, using the print_r() function and adding the "pre" tag around it.
	 *
	 * @param mixed $var Array or object that will have its elements printed.
	 * @param bool $return If <tt>TRUE</tt> the formatted string is returned, otherwise its printed, default value is <tt>FALSE</tt>.
	 * @param bool $hideProtectedVars If <tt>TRUE</tt> the print_r will not show protected properties of an object. This feature is not recursive.
	 * @param string @varPrefix If <tt>TRUE</tt> it will only print the keys starting by this prefix. Is is useful when printing large arrays, like $GLOBALS.
	 * @return string|NULL Formatted string or <tt>NULL</tt>.
	 * @version (2008/02/06)
	 * @author JP
	 */
	public static function jp7_print_r($var, $return = FALSE, $hideProtectedVars = FALSE, $varPrefix = '') {
	
		if ($hideProtectedVars) {
			if (is_object($var)) {
				$array[0] = (array) $var;
			} elseif (is_array($var) && is_object(reset($var))) {
				foreach ($var as $key => $value) {
					$array[$key] = (array) $value;
				}
			}
		} elseif ($varPrefix && is_array($var)) {
			$array = $var;
		}
	
		if ($array) {
			foreach ($array as $key => $value) {
				if ($varPrefix && strpos($key, $varPrefix) !== 0)  continue;
				if ($hideProtectedVars) {
					foreach ($value as $valueKey => $valueValue) {
						if (strpos($valueKey, chr(0) . chr(42) . chr(0)) === 0) {
							$array[$key][substr($valueKey, 2) . ':protected'] = '*PROTECTED*';
							unset($array[$key][$valueKey]); // Retira os valores protected
						}
					}
				}
			}
			if (is_object($var) && $hideProtectedVars) $array = $array[0];
			$S = print_r($array, TRUE);
		} else {
			$S = print_r($var, TRUE);
		}
	
		$S = "<pre style=\"text-align:left\">" . $S . "</pre>";
	
		if ($return) return $S;
		else echo $S;
	}
	

	/**
	 * Searches for a value on the database and creates global variables from the result.
	 *
	 * @param string $table Name of the table where it will search.
	 * @param string $table_id_name Name of the key field.
	 * @param mixed $table_id_value Value expected for the key field.
	 * @param string $var_prefix Prefix used when creating the global variables from the result, on the format: prefix + field name, the default value is "".
	 * @global ADOConnection
	 * @global bool
	 * @return NULL Nothing is returned, but the function creates global variables.
	 * @version (2006/08/23)
	 */
	public static function jp7_db_select($table,$table_id_name,$table_id_value,$var_prefix=""){
		global $db, $jp7_app;
		$sql = "SELECT * FROM ".$table." WHERE ".$table_id_name."=".$table_id_value;
		$rs=$db->Execute($sql)or die(jp7_debug($db->ErrorMsg(),$sql));
		while ($row = $rs->FetchNextObj()) {
			$meta_cols=$db->MetaColumns($table, FALSE);
			foreach ($meta_cols as $meta){
				$name=$meta->name;
				// Dates
				if(strpos($meta->type,"date")!==FALSE){
					$GLOBALS[$var_prefix.$name]=$row->$name;
					$GLOBALS[$var_prefix.$name."_split"]=jp7_date_split($row->$name);
					$GLOBALS[$var_prefix.$name."_time"]=strtotime($row->$name);
				}else{
					if($jp7_app)$GLOBALS[$var_prefix.$name]=$row->$name;
					else $GLOBALS[$var_prefix.$name]=$row->$name;
				}
			}
		}
		$rs->Close();
	}
	

	/**
	 * Inserts or updates a record on the given table using values from global variables.
	 *
	 * @param string $table Name of the table where it will insert or update data.
	 * @param string $table_id_name Name of the key field.
	 * @param mixed $table_id_value Value expected for the key field, the default value is 0. If a value is given the row is updated, otherwise it is inserted.
	 * @param mixed $var_prefix Prefix used to get values from global variables, the default value is "". e.g. For the field name "varchar_1" and the global variable "pre_varchar_1", the prefix should be "pre_". If it is passed as an array, the values from this array will be used instead of globals.
	 * @param bool $var_check If <tt>FALSE</tt> prepares the data for empty and null values before updating, the default value is <tt>TRUE</tt>.
	 * @global ADOConnection
	 * @return int When updating: $table_id_value on success or 0 on error. When inserting: the inserted record¥s ID.
	 * @author JP, Cristiano
	 * @version (2007/12/17)
	 */
	public static function jp7_db_insert($table, $table_id_name, $table_id_value = 0, $var_prefix = "", $var_check = TRUE, $force_magic_quotes_gpc = FALSE) {
		global $db;
	
		$table_columns = $db->MetaColumnNames($table);
		array_shift($table_columns); // ID is the first value
		$table_columns_num = count($table_columns);
		if ($table_id_value) {
			// Update
			$sql = "UPDATE ".$table." SET ";
			$j = 0;
			foreach ($table_columns as $table_field_name) {
				if (is_array($var_prefix)) {
					$var_isset = array_key_exists($table_field_name, $var_prefix);
					$table_field_value = $var_prefix[$table_field_name];
				} else {
					$var_isset = isset($GLOBALS[$var_prefix . $table_field_name]);
					$table_field_value = $GLOBALS[$var_prefix . $table_field_name];
				}
				if (!$var_check || $var_isset) {
					//se for definido valor ou campo for inteiro
					if (($table_field_value!=="" && !is_null($table_field_value))||strpos($table_field_name,"int_")===0) {
						$sql .= ((!$j)?" ":",")."".$table_field_name."=".toBase($table_field_value,$force_magic_quotes_gpc);
						//se n„o for definido valor e for mysql salva branco
					} elseif(($table_field_value==="" || is_null($table_field_value)) && ($GLOBALS['db_type']==""||$GLOBALS['db_type']=="mysql")) {
						$sql .= ((!$j)?" ":",")."".$table_field_name."=''";
						//se n„o for definido valor e for != de mysql
					} else {
						$sql .= ((!$j)?" ":",")."".$table_field_name."=NULL";
					}
					$j++;
				}
			}
			$sql .= " WHERE " . $table_id_name . "=" . $table_id_value;
			$rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
			return ($rs) ? $table_id_value : 0;
		} else {
			// Insert
			$i = 1;
			foreach ($table_columns as $table_field_name) {
				if (is_array($var_prefix)) {
					$table_field_value = $var_prefix[$table_field_name];
				} else {
					$table_field_value = $GLOBALS[$var_prefix . $table_field_name];
				}
				$sql_campos .= " " . $table_field_name . " " . (($i == $table_columns_num) ? ") " : ",\n");
				//se for definido valor
				if (($table_field_value !== "" && !is_null($table_field_value)) || strpos($table_field_name, "int_") === 0) {
					$valores .= toBase($table_field_value,$force_magic_quotes_gpc) . (($i == $table_columns_num) ? ")" : ",\n");
					//se n„o for definido valor e for mysql salva branco
				} elseif (($table_field_value==="" || is_null($table_field_value)) && ($GLOBALS['db_type']==""||$GLOBALS['db_type']=="mysql")){
					$valores .= "''". (($i == $table_columns_num) ? ")" : ",\n");
					//se n„o for definido valor e for != de mysql
				} else {
					$valores .= "NULL" . (($i==$table_columns_num) ? ")" : ",\n");
				}
				$i++;
			}
			$sql = "INSERT INTO ".$table." (".$sql_campos."VALUES (".$valores;//echo $sql ."<br /><hr /><br />";
			$rs = $db->Execute($sql)or die(jp7_debug($db->ErrorMsg(), $sql));
	
			// Last ID
			if (!is_array($var_prefix)) {
				$GLOBALS[$var_prefix . $table_id_name] = $db->Insert_ID();
			}
	
			return $db->Insert_ID();
		}
	}
}