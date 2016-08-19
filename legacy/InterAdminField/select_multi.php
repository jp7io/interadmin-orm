<script type="text/javascript">
function interadmin_ajax_busca_combo(campo,idtipo,busca){
    obj=getElm('box_'+campo)
    obj.length=1
    obj[0].value=''
    if(busca.length>2||busca=='*'){
        obj[0].text='Carregando...'
        if(busca=='*')busca=''
        ajax_function('box_'+campo,'select_multi_combo_ajax.php?id_tipo='+idtipo+'&busca='+busca,interadmin_ajax_busca_resultado)
    }else{
        obj[0].text='Inserir no mínimo três caracteres.'
    }
}

function interadmin_urlDecode(S){
    if(S){
        S=S.replace(new RegExp('\\+','g'),' ')
        return unescape(S)
    }else{
        return ''
    }
}

function interadmin_ajax_busca_resultado(obj,xml,ok){
    if(ok){
        for(var i=0;i<xml.getElementsByTagName('item').length;i++){
            var varId=request_vars(xml,'id',i)
            var varValor=request_vars(xml,'text',i)
            obj.length=i+1
            obj[i].value=varId
            obj[i].text=interadmin_urlDecode(varValor)
            obj[i].title=obj[i].text
        }
    }else{
        obj.length=1
        obj[0].value=''
        obj[0].text='Nenhum resultado encontrado.'
    }
}

function request_vars(xml,tag,i){
    var tagsel=xml.getElementsByTagName(tag).item(i)
    if(tagsel){
        if(tagsel.firstChild)return tagsel.firstChild.data
    }
}

function interadmin_select_multi_item_add(campo) {
    var itens = getElm(campo);
    var box_itens = getElm('box_' + campo);
    for (var i = 0; i < box_itens.length; i++) {
        if (box_itens[i].selected) {
            var ok = true;
            for(var j=0;j<itens.length;j++){
                if(itens[j].value==box_itens[i].value)ok=false
            }
            if(ok){
                var len=itens.length++
                itens[len].value=box_itens[i].value
                itens[len].text=box_itens[i].text
                itens[len].title=itens[len].text
            }
        }
    }
}

function interadmin_select_multi_item_del(campo){
    var itens = getElm(campo);
    for(var i = (itens.length - 1); i >= 0; i--) {
        if (itens[i].selected) {
            itens.remove(i);
        }
    }
}

function interadmin_select_multi_item_up(campo) {
    var selected = $(getElm(campo)).find('option:selected:first');
    selected.prev().before(selected);
}
function interadmin_select_multi_item_down(campo) {
    var selected = $(getElm(campo)).find('option:selected:last');
    selected.next().after(selected);
}
</script>
<table border=0 cellspacing=0 cellpadding=0 style="background:#DDD">
    <tr>
        <td style="background:#DDD">Itens Atuais:</td>
        <td colspan="2" align="right" style="background:#DDD"><input type="text" name="busca_ajax_<?= $campo ?>[<?= $j ?>]" title="* para listar todas as opções" style="width:150px" onkeypress="if(event.keyCode==13){interadmin_ajax_busca_combo('<?= $campo ?>[<?= $j ?>]','<?= $campo_nome ?>',value);return false}" /><input type="button" value="Localizar" style="width:70px" onclick="interadmin_ajax_busca_combo('<?= $campo ?>[<?= $j ?>]','<?= $campo_nome ?>',form['busca_ajax_<?= $campo ?>[<?= $j ?>]'].value)" /></td>
    </tr>
    <tr>
        <td style="background:#DDD">
            <select name="<?= $campo ?>[<?= $j ?>][]" id="<?= $campo ?>[<?= $j ?>]" select_multi="true" size="10" multiple="multiple"  class="select_multi_half" />
                <?php
                $sql = "SELECT campos, tabela FROM " . $db_prefix . "_tipos" .
                    " WHERE id_tipo=" . $campo_nome;
                $rs = $db->Execute($sql);
                if ($rs === false) {
                    throw new Jp7_Interadmin_Exception($db->ErrorMsg());
                }
                while ($row = $rs->FetchNextObj()) {
                    $campos = interadmin_tipos_campos($row->campos);
                    $selectMultiTabela = $row->tabela;
                }
                $rs->Close();
                // Combo Fields
                if ($campos) {
                    foreach ($campos as $select_campo) {
                        if ($select_campo[combo]) {
                            if (strpos($select_campo[tipo], "special_") === 0) {
                                $select_campos_2_nomes .= " - " . $select_campo[nome]("select_campos_sql_temp", $select_campos_sql_temp, "header");
                            } else {
                                $select_campos_2_nomes .= " - " . ((intval($select_campo[nome]) > 0)?interadmin_tipos_nome(intval($select_campo[nome])):(($select_campo[nome] == "all")?"Tipos":$select_campo[nome]));
                            }
                            if ($select_campo[tipo] != "varchar_key") {
                                $select_campos_2 .= "," . $select_campo[tipo];
                                $select_campos_2_array[] = $select_campo[tipo];
                                $select_campos_2_xtra[] = $select_campo[xtra];
                                $select_campos_2_nomes_arr[] = $select_campo[nome];
                            }
                        }
                    }
                }
                // Loop
                if ($valor) {
                    $sql = "SELECT id,varchar_key" . $select_campos_2 . " FROM " . $db_prefix . (($selectMultiTabela) ? '_' . $selectMultiTabela : '') .
                        " WHERE id_tipo=" . $campo_nome .
                        " AND id IN (" . trim($valor, ', ') . ")" .
                        $sql_where .
                        " AND deleted=''" .
                        " ORDER BY FIND_IN_SET(id,'" . trim($valor, ', ') . "')";
                    $rs = $db->Execute($sql);
                    if ($rs === false) {
                        throw new Jp7_Interadmin_Exception($db->ErrorMsg());
                    }
                    $S = '';
                    for ($i = 0; $i < $nivel * 5; $i++) {
                        if ($i < $nivel * 5 - 1) {
                            $S .= '-';
                        } else {
                            $S .= '> ';
                        }
                    }
                    while ($row = $rs->FetchNextObj()) {
                        if(is_array($current_id)) $selected = in_array($row->id, $current_id);
                        else $selected = ($row->id == $current_id);
                        if ($row->select_key && !in_array("select_key", $select_campos_2_array)){
                            if ($campos['select_key']['xtra']) $row->varchar_key = interadmin_tipos_nome($row->select_key);
                            else $row->varchar_key = jp7_fields_values($row->select_key);
                        }
                        // Combo Fields
                        $select_campos_sql = "";
                        if ($select_campos_2_array) {
                            foreach ($select_campos_2_array as $key => $value) {
                                $select_campos_sql_temp = $row->$value;
                                if ($select_campos_sql_temp) {
                                    if (strpos($value, "special_") === 0) {
                                        $select_campos_sql .= (($key || $row->varchar_key)?" - ":"") . $select_campos_2_nomes_arr[$key]("select_campos_sql_temp", $select_campos_sql_temp, "list");
                                    } else {
                                        if(is_numeric($select_campos_sql_temp) && strpos($value, "varchar_") === false && strpos($value, "int_") === false) $select_campos_sql_temp = ($select_campos_2_xtra[$key])?interadmin_tipos_nome($select_campos_sql_temp):jp7_fields_values($select_campos_sql_temp);
                                        $select_campos_sql .= (($key || $row->varchar_key)?" - ":"") . $select_campos_sql_temp;
                                    }
                                }
                            }
                        }
                        // Output
                        if ($style == "checkbox")echo "<input type=\"checkbox\" name=\"" . $field_name . "\" id=\"" . $field_name . "_" . $row->id . "\" value=\"" . $row->id . "\"" . (($selected)?" checked style=\"color:blue\"":"") . (($row->id == $id)?" style=\"color:red\"":"") . ((interadmin_tipos_nome($parent_id_tipo_2) == "Classes")?" style=\"background:#DDD\"":"") . "><label for=\"" . $field_name . "_" . $row->id . "\" unselectable=\"on\"" . (($selected)?" style=\"color:blue\"":"") . ">" . $S . $row->varchar_key . jp7_string_left($select_campos_sql, 100) . "</label><br>\n";
                        else {
                            $buscaValor = $S . $row->varchar_key . jp7_string_left($select_campos_sql, 100);
                            echo "<option value=\"" . $row->id . "\"" . (($selected)?" SELECTED style=\"color:blue\"":"") . (($row->id == $id)?" style=\"color:red\"":"") . ((interadmin_tipos_nome($parent_id_tipo_2) == "Classes")?" style=\"background:#DDD\"":"") . " title=\"" . $buscaValor . "\">" . /*mb_substr($row->varchar_key,0,1).")".*/$buscaValor . "</option>\n";
                        }
                        //if ($style!="checkbox"||$nivel<2)interadmin_tipos_combo($current_id_tipo,$row->id_tipo,$nivel+1,$prefix,"",$style,$field_name);
                    }
                    $rs->Close();
                }
                ?>
            </select>
        </td>
        <td style="background:#DDD">
            <input type="button" value="&laquo;" title="Adicionar" style="width:20px;height:40px;margin:0px;color:green;font-weight:bold" onclick="interadmin_select_multi_item_add('<?= $campo ?>[<?= $j ?>]')" /><br />
            <input type="button" value="X" title="Remover" style="width:20px;height:40px;margin:5px 0px;color:red;font-weight:bold" onclick="interadmin_select_multi_item_del('<?= $campo ?>[<?= $j ?>]')" /><br />

            <input type="button" value="^" title="Mover para Cima" style="font-family:Arial;width:20px;height:20px;margin-top:20px;font-size: 15px;color:blue;font-weight:bold" onclick="interadmin_select_multi_item_up('<?= $campo ?>[<?= $j ?>]')" /><br />
            <input type="button" value="v" title="Mover para Baixo" style="font-family:Arial;width:20px;height:20px;margin:0px;color:blue;font-size: 12px;font-weight:bold" onclick="interadmin_select_multi_item_down('<?= $campo ?>[<?= $j ?>]')" />
        </td>
        <td style="background:#DDD">
            <select id="box_<?= $campo ?>[<?= $j ?>]" size="10" class="select_multi_half" multiple="multiple" />
            <?php
            $buscaTipo = new InterAdminTipo($campo_nome);
            $count = $buscaTipo->count();
            // Se os registros forem poucos e couberem no select, são carregados na inicialização
            if ($count > 0 && $count < 20) {
            ?>
                <script type="text/javascript">
                    interadmin_ajax_busca_combo('<?= $campo ?>[<?= $j ?>]','<?= $campo_nome ?>','%%%');
                </script>
            <?php
            }
            ?>
            </select>
        </td>
    </tr>
</table>
