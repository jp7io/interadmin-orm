<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category JP7
 * @package InterAdmin
 */
 
/**
 * Generates the HTML output for a field based on its type, such as varchar, int or text.
 *
 * @package InterAdmin
 */
class InterAdminField{
	public $id;
	public $id_tipo;
	/**
	 * @param int $id
	 * @param varchar $_db_prefix
	 * @return object
	 */
	function __construct($field = ''){
		$this->field = $field;
	}
	function __toString(){
		return $this->field;
	}	
	/**
	 * @return mixed
	 */
	function getHtml(){
		global $xtra_disabledfields_arr_final;
		global $is;
		global $id;
		global $db;
		global $db_prefix;
		global $s_user;
		global $s_session;
		global $iframes_i;
		global $lang;
		global $quantidade;
		global $j;
		global $registros;
		global $select_campos_sql_temp;
		global $tit_start;
		$campo = $this->field;
		if (is_array($campo)) {
			$campo_array = $campo;
			$campo_nome = (string) $campo["nome"];
			$ajuda = stripslashes($campo["ajuda"]);
			$tamanho = $campo["tamanho"];
			$obrigatorio = ($quantidade>1)?"":$campo["obrigatorio"];
			$separador = $campo["separador"];
			$xtra = $campo["xtra"];
			$valor_default = $campo["default"];
			$readonly = $campo["readonly"];
			$campo = $campo["tipo"];
		}
		if (array_key_exists('value', $campo_array)) {
			$valor = $campo_array['value'];
		} else {
			$valor = stripslashes($GLOBALS[$campo]);
		}
		if (!$valor && !$id) {
			$valor = $valor_default;
		}
		$_th = "<th title=\"".$campo. ' (' . (($campo_array['nome_id']) ?  $campo_array['nome_id']  :  toId($campo_nome)) . ')' . "\"".(($obrigatorio||$readonly)?" class=\"".(($obrigatorio)?"obrigatorio":"").(($readonly)?" disabled":"")."\"":"").">".$campo_nome.":</th>";
		if($ajuda)$S_ajuda="<input type=\"button\" value=\"?\" tabindex=\"-1\" class=\"bt_ajuda\" onclick=\"alert('".$ajuda."')\" />";
		if($readonly=="hidden")$readonly_hidden=true;
		if($readonly||($campo_array[permissoes]&&$campo_array[permissoes]!=$s_user['tipo']&&!$s_user['sa']))$readonly=" disabled";
		
		if(strpos($campo,"tit_")===0){
			if($tit_start){
				echo "</tbody>";
				$tit_start=false;
			}
			echo "<tr><th colspan=\"4\" class=\"inserir_tit_".(($xtra=="hidden")?"closed":"opened")."\" onclick=\"interadmin_showTitContent(this)\">".$campo_nome."</th></tr><tbody".(($xtra=="hidden")?" style=\"display:none\"":"").">";
			$tit_start=true;
		}elseif(strpos($campo,"text_")===0){
			$form="<textarea".(($xtra)?" textarea_trigger=\"true\"":"")." name=\"".$campo."[]\" id=\"".$campo."_".$j."\" rows=".($tamanho+(($xtra)?((($xtra=="html_light"&&$tamanho<=5)||$quantidade>1)?2:5):0)).(($xtra)?" wrap=\"off\"":"")." xtra=\"".$xtra."\" class=\"inputs_width\" style=\"width:".(($s_session['screenwidth']<=800)?"400":"470")."px;".(($xtra)?";color:#000066;font-family:courier new;font-size:11px;visibility:hidden":"")."\"".(((($campo=="text_0"||$campo=="text_1")&&$tamanho<=5)||$quantidade>1)?" smallToolbar=\"true\"":"").$readonly.">".$valor."</textarea>";
			if($xtra)$form.="<script type=\"text/javascript\">interadmin_iframes[".$iframes_i."]='".$campo."_".$iframes_i++."'</script>";
		} elseif(strpos($campo, "char_") === 0) {
			if ($xtra && !$id) {
				$GLOBALS[$campo] = "S";
			}
			$form = jp7_db_checkbox($campo."[".$j."]","S",$campo,$readonly, "", ($valor) ? $valor : null);
		}elseif(strpos($campo,"select_multi_")===0){
			if(!$readonly_hidden){
				$form="<div class=\"select_multi\">";
				ob_start();
				if ($xtra == 'X') {
					include 'site/aplicacao/select_multi.php';
					$campo_nome = trim($campo_nome);
					$campo_nome = interadmin_tipos_nome((is_numeric($campo_nome)) ? $campo_nome : 0);
				} elseif ($xtra) {
					interadmin_tipos_combo(explode(',', $valor), (is_numeric($campo_nome)) ? $campo_nome : 0, 0, "", $campo_array['where'], "checkbox", $campo . "[".$j."][]", false, $readonly, $obrigatorio, $campo_array['opcoes']);
					$campo_nome = 'Tipos';
				} else {
					$temp_campo_nome=interadmin_tipos_nome((is_numeric($campo_nome))?$campo_nome:0);
					echo interadmin_combo(explode(",",$valor),(is_numeric($campo_nome))?$campo_nome:0,0,"","","checkbox",$campo."[".$j."][]",$temp_campo_nome,$obrigatorio, $readonly);
					$campo_nome=$temp_campo_nome;
				}
				$form.=ob_get_contents();
				ob_end_clean();
				$form.="</div>";
				if($campo_array[label])$campo_nome=$campo_array[label];
			}
		}elseif(strpos($campo,"select_")===0){
			if ($campo_array['label']) $campo_nome_2 = $campo_array['label'];
			else $campo_nome_2 = ($campo_nome == "all" && $xtra) ? "Tipos" : interadmin_tipos_nome($campo_nome);
			$form = "" .
			"<select name=\"" . $campo . "[]\" label=\"" . $campo_nome_2 . "\"" . (($obrigatorio) ? " obligatory=\"yes\"" : "") . $readonly . " class=\"inputs_width\">" .
			"<option value=\"0\">Selecione</option>" .
			"<option value=\"0\">--------------------</option>";
			if ($xtra == 'radio') {
				$temp_campo_nome=interadmin_tipos_nome((is_numeric($campo_nome))?$campo_nome:0);
				$form = interadmin_combo($valor, (is_numeric($campo_nome)) ? $campo_nome : 0, 0, '', '', 'radio', $campo . '[' . $j . ']', $temp_campo_nome, $obrigatorio);
			} elseif ($xtra == 'radio_tipos') {
				$form = interadmin_tipos_combo($valor, (is_numeric($campo_nome)) ? $campo_nome : 0, 0, '', '', 'radio', $campo . '[' . $j . ']', true, $readonly, $obrigatorio);
			} elseif ($xtra == 'ajax') {
				$form = "<select name=\"" . $campo . "[]\" label=\"" . $campo_nome_2 . "\" xtype=\"ajax\" ajax_function=\"interadmin_combo_ajax(" . $campo_nome . ", 'search', 'callback')\"" . (($obrigatorio) ? " obligatory=\"yes\"" : "") . $readonly." class=\"inputs_width\">" .
				"<option value=\"0\">Selecione ou Procure" . (($select_campos_2_nomes) ? $select_campos_2_nomes : "") . "</option>" .
				"<option value=\"0\">--------------------</option>" .
				"<option value=\"0\">Mínimo de 3 caracteres para começar a busca...</option>";
				//interadmin_combo($valor, (is_numeric($campo_nome)) ? $campo_nome : 0, 0, "", "", "combo", $campo . "[".$j."]", $temp_campo_nome, $obrigatorio);
				if ($valor) {
					$tipoObj = new InterAdminTipo($campo_nome);
					$options = array(
						'where' => " AND id=".$valor
					);
					$rows = $tipoObj->getInterAdmins($options);
					foreach ($rows as $row) {
						$form.="<option value=\"".$row->id."\" value=\"".$row->id."\"".(($row->id==$valor)?" selected":"").">".toHTML($row->getStringValue())."</option>";
					}
				}
			} elseif ($xtra == 'ajax_tipos') {	
				$form = "<select name=\"" . $campo . "[]\" label=\"" . $campo_nome_2 . "\" xtype=\"ajax\" ajax_function=\"interadmin_combo_ajax(" . intval($campo_nome) . ", 'search', 'callback', true)\"" . (($obrigatorio) ? " obligatory=\"yes\"" : "") . $readonly." class=\"inputs_width\">" .
				"<option value=\"0\">Selecione ou Procure" . (($select_campos_2_nomes) ? $select_campos_2_nomes : "") . "</option>" .
				"<option value=\"0\">--------------------</option>";
				if ($valor) {
					$form.="<option value=\"".$valor."\" value=\"".$valor."\" selected>".toHTML(interadmin_tipos_nome($valor))."</option>";
				}
			} elseif ($xtra) {
				if ($campo_nome == "all") {
					ob_start();
					interadmin_tipos_combo($valor,0);
					$form.=ob_get_contents();
					ob_end_clean();
				}else{
					$sql = "SELECT id_tipo,nome FROM ".$db_prefix."_tipos".
					" WHERE parent_id_tipo=".$campo_nome.
					" ORDER BY ordem,nome";
					$rs=$db->Execute($sql)or die(jp7_debug($db->ErrorMsg(),$sql));;
					while ($row = $rs->FetchNextObj()) {
						$form.="<option value=\"".$row->id_tipo."\"".(($row->id_tipo==$valor)?" SELECTED":"").">".toHTML($row->nome)."</option>";
					}
					$rs->Close();
				}
			} else {
				$form = "<select name=\"" . $campo . "[]\" label=\"" . $campo_nome_2 . "\" xtype=\"autocomplete\"" . (($obrigatorio) ? " obligatory=\"yes\"" : "") . $readonly." class=\"inputs_width\">" .
				"<option value=\"0\">Selecione ou Procure" . (($select_campos_2_nomes) ? $select_campos_2_nomes : "") . "</option>" .
				"<option value=\"0\">--------------------</option>" .
				interadmin_combo($valor, (is_numeric($campo_nome)) ? $campo_nome : 0, 0, "", $campo_array['where'], "combo", $campo . "[".$j."]", $temp_campo_nome, $obrigatorio);
			}
			$form .= "</select>";
			$campo_nome = $campo_nome_2;
		}elseif(strpos($campo,"int_")===0||strpos($campo,"float_")===0){
			$onkeypress=" onkeypress=\"return DFonlyThisChars(true,false,' -.,()')\"";
			if($campo=="int_key"&&!$valor&&$quantidade>1)$valor=$registros+1+$j;
			if (strpos($campo,"float_")===0 && $xtra == 'moeda') $valor = number_format($valor, '2', ',', '.');
			$form="<input type=\"text\" name=\"".$campo."[]\" label=\"".$campo_nome."\" value=\"".$valor."\" maxlength=\"255\"".(($obrigatorio)?" obligatory=\"yes\"":"")." style=\"width:".(($tamanho)?$tamanho."em":"70px")."\"".$readonly.$onkeypress." />";
		}else{
			$onkeypress="";
			if(strpos($campo,"varchar_")===0){
				switch($xtra){
					case "id": // ID
						$onkeypress=" onkeypress=\"return DFonlyThisChars(true,true,'_')\" onblur=\"ajax_function(this,'interadmin_inserir_checkuniqueid.php?id_tipo=".$GLOBALS["id_tipo"]."&campo=".$campo."&valor_atual=".$valor."&valor='+value,interadmin_inserir_checkUniqueId)\"";
						if ($id && !$s_user['sa']) $onkeypress .= " disabled=\"disabled\""; // Impede alteração
						break;
					case "id_email": // ID E-Mail
						$onkeypress=" onkeypress=\"return DFonlyThisChars(true,true,'_@.-')\" onblur=\"ajax_function(this,'interadmin_inserir_checkuniqueid.php?id_tipo=".$GLOBALS["id_tipo"]."&campo=".$campo."&valor_atual=".$valor."&valor='+value,interadmin_inserir_checkUniqueId)\"";
						break;
					case "email": // E-Mail
						$onkeypress=" xtype=\"email\" onkeypress=\"return DFonlyThisChars(true,true,'_@-.')\"";
						break;
					case "num": // Número
						$onkeypress=" onkeypress=\"return DFonlyThisChars(true,false,' -.,()')\"";
						break;
					case "cpf": // CPF
						$onkeypress=" xtype=\"cpf\"";
						break;
				}
			}
			$form = "<input type=\"".((strpos($campo,"password_")===0)?"password":"text")."\" name=\"".$campo."[]\" label=\"".$campo_nome."\" value=\"".toForm($valor)."\" title=\"".$ajuda."\" maxlength=\"" . (($tamanho) ? $tamanho : 255) . "\"".(($obrigatorio)?" obligatory=\"yes\"":"").$readonly." class=\"inputs_width\"".(($tamanho)?" style=\"width:".$tamanho."em\"":"").$onkeypress." xtra=\"".$xtra."\" />";
		}
		$form.="<input type=\"hidden\" name=\"".$campo."_xtra[]\" value=\"".$xtra."\"".$readonly." />";
		if($readonly&&$valor_default)$form.="<input type=\"hidden\" name=\"".$campo."[]\" value=\"".$valor."\" />";
		
		if($campo_nome){
			if(strpos($campo,"tit_")===0){
				
			}elseif(strpos($campo,"file_")===0){
				$url = interadmin_uploaded_file_url($valor);
				echo "".
				"<tr>".
					$_th.
					"<td><input type=\"text\" name=\"".$campo."[".$j."]\" value=\"".$valor."\" maxlength=\"255\"".$readonly." class=\"inputs_width_file_search\"><input type=\"button\" value=\"Procurar...\" style=\"width:80px\" onclick=\"interadmin_arquivos_banco(this,'".$campo."[".$j."]',false,'".$tamanho."')\" /></td>".
					"<td rowspan=2".(($valor)?" align=\"center\" onclick=\"openPopup('".$url."','arquivo_preview',400,400,'left=36,top=36,resizable=1')\" class=\"image_preview\" style=\"cursor:pointer\">".interadmin_arquivos_preview($url):">")."</td>".
					"<td rowspan=2>".$S_ajuda."</td>".
				"</tr>\n".
				"<tr>".
					"<th".(($obrigatorio||$readonly)?" class=\"".(($readonly)?"disabled":"")."\"":"").">Créditos/Leg.:</th>".
					"<td><input type=\"text\" name=\"".$campo."_text[]\" value=\"".$GLOBALS[$campo."_text"]."\" maxlength=\"255\"".$readonly." class=\"inputs_width_file\" /></td>".
				"</tr>\n";
			}elseif(strpos($campo,"date_")===0){
				$S="".
				"<tr>".
					$_th.
					"<td colspan=\"2\">".
						((strpos($xtra,"calendar_")!==false)?"<input type=\"hidden\" id=\"".$campo."_calendar_value_".$j."\" value=\"".$valor."\" value=\"".$xtra."\">":"").
						"<table width=\"100%\">".
							"<tr>".
								"<td>".jp7_app_createSelect_date($campo,(($xtra=="S"||(strpos($xtra,"datetime")===false&&$xtra))?"style=\"visibility:hidden\"":"").(($xtra=="calendar_datetime"||$xtra=="calendar_date")?" onchange=\"interadmin_calendar_update_bycombo(this,'".$campo."','".$j."')\"":"").$readonly,false,$j,$readonly . (($obrigatorio) ? 'obligatory="yes" label="' . $campo_nome .  '"' : '' ) . (($xtra=="calendar_datetime"||$xtra=="calendar_date")?" onchange=\"interadmin_calendar_update_bycombo(this,'".$campo."','".$j."')\"":""),$xtra)."</td>".
								"<td width=\"99%\" align=\"right\">".
									"<input type=\"button\" value=\"Atualizar".((strpos($xtra,"calendar")===false)?" Data".(($xtra!="S")?" - Hora":""):"")."\"".$readonly." tabindex=\"-1\" onclick=\"refreshDate('".$campo."',".$j.",'','".$xtra."')".(($xtra=="calendar_datetime"||$xtra=="calendar_date")?";interadmin_calendar_update_bycombo(this,'".$campo."','".$j."')\"":"")."\" />".
									((strpos($xtra,"calendar")!==false)?"<input type=\"button\" id=\"".$campo."_calendar_".$j."\" value=\"Calendário\"".$readonly." tabindex=\"-1\" style=\"margin-left:10px\">":"").
								"</td>".
							"</tr>".
						"</table>".
					"</td>".
					"<td>".$S_ajuda."</td>".
				"</tr>";
				echo $S;
			}elseif(strpos($campo,"password_")===0&&$valor&&$xtra){
				echo "".
				"<tr>".
					$_th.
					"<td colspan=\"2\">".
						"<table width=\"100%\">".
							"<tr>".
								"<td width=\"99%\" style=\"display:none\"><input type=\"password\" name=\"".$campo."[".$j."]\" disabled style=\"width:100%\"><input type=\"hidden\" name=\"".$campo."_xtra[".$j."]\" value=\"".$xtra."\"></td>".
								"<td><input type=\"button\" value=\"Alterar...\" onclick=\"interadmin_inserir_password(this,'".$campo."[".$j."]')\"><input type=\"text\" disabled style=\"width:1px;visibility:hidden\"></td>".
							"</tr>".
						"</table>".
					"</td>".
					"<td>".$S_ajuda."</td>".
				"</tr>\n";
			}elseif(strpos($campo,"plugin_")===0){
				$plugin_function = 'interadmin_plugin_' . $xtra;
				$plugin_include = '../../plugins/' . $xtra . '.php';
				if (file_exists($plugin_include)) include $plugin_include;
				else echo 'Include ' . $plugin_include . ' não encontrado.<br />';
				if (function_exists($plugin_function)) echo $plugin_function($campo_array,$valor);
				else echo 'Função ' . $plugin_function . ' não encontrada.<br />';
			}elseif(strpos($campo,"special_")===0 || strpos($campo,"func_")===0){
				if (function_exists($campo_nome)) echo $campo_nome($campo_array,$valor);
				else echo 'Função ' . $campo_nome . ' não encontrada.<br />';
			}else{
				if(!$readonly_hidden){
					echo "".
					"<tr".(($s_session['mode']=="light"&&strpos($campo,"text_")===0&&$xtra)?" style=\"display:none\"":"").">".
						"<th title=\"" . $campo . ' (' . (($campo_array['nome_id']) ?  $campo_array['nome_id']  :  toId($campo_nome)) . ')' . "\"".(($obrigatorio||$readonly)?" class=\"".(($obrigatorio)?"obrigatorio":"").(($readonly)?" disabled":"")."\"":"").">".$campo_nome.":</th>".
						"<td colspan=\"2\">".$form."</td>".
						"<td>".$S_ajuda."</td>".
					"</tr>\n";
				}else{
					echo $form;
				}
			}
		}
		if($separador){
			if($tit_start){
				echo "</tbody>";
				$tit_start=false;
			}
			echo "<tr><td height=\"".(($quantidade>1||$s_session['screenwidth']<=800)?5:10)."\" colspan=\"4\" style=\"padding:0px\"></td></tr>\n";
		}
	}
}
?>