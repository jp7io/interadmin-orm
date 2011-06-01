<?php

class Jp7_Model_SiteSettingsTipo extends Jp7_Model_TipoAbstract {
	public $isSubTipo = true;
	
	public $attributes = array(
		'id_tipo' => 'SiteSettings',
		'nome' => 'Configurações Gerais',
		'campos' => 'tit_1{,}Cabeçalho{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}tit_1{;}varchar_key{,}Título{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}header_title{;}varchar_1{,}Sub-Título{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}header_subtitle{;}tit_2{,}Template{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}tit_2{;}special_1{,}Jp7_Model_SiteSettingsTipo::teste{,}{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}Template{,}{,}{,}template_path{;}varchar_2{,}Cabeçalho Fundo{,}{,}{,}{,}{,}cor{,}{,}{,}{,}{,}{,}{,}{,}{,}header_background{;}varchar_3{,}Cabeçalho Título{,}{,}{,}{,}{,}cor{,}{,}{,}{,}{,}{,}{,}{,}{,}header_title_color{;}varchar_4{,}Cabeçalho Subtítulo{,}{,}{,}{,}S{,}cor{,}{,}{,}{,}{,}{,}{,}{,}{,}header_subtitle_color{;}varchar_5{,}Menu Fundo{,}{,}{,}{,}{,}cor{,}{,}{,}{,}{,}{,}{,}{,}{,}menu_background{;}varchar_6{,}Menu Item{,}{,}{,}{,}{,}cor{,}{,}{,}{,}{,}{,}{,}{,}{,}menu_color{;}varchar_7{,}Menu Item Ativo{,}{,}{,}{,}S{,}cor{,}{,}{,}{,}{,}{,}{,}{,}{,}menu_active_color{;}varchar_9{,}Conteúdo Fundo{,}{,}{,}{,}{,}cor{,}{,}{,}{,}{,}{,}{,}{,}{,}content_background{;}varchar_8{,}Conteúdo Título{,}{,}{,}{,}{,}cor{,}{,}{,}{,}{,}{,}{,}{,}{,}content_title_color{;}varchar_10{,}Conteúdo Subtítulo{,}{,}{,}{,}{,}cor{,}{,}{,}{,}{,}{,}{,}{,}{,}content_subtitle_color{;}varchar_11{,}Conteúdo Texto{,}{,}{,}{,}{,}cor{,}{,}{,}{,}{,}{,}{,}{,}{,}content_text_color{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => '',
		'template' => '',
		'editpage' => '',
		'class' => '',
		'class_tipo' => '',
		'model_id_tipo' => 0,
		'tabela' => '',
		'unico' => ''
	);
	
	public static function teste($campo, $valor, $parte = 'edit') {
		switch ($parte) {
			case 'header':
				return $campo['label'];
				break;
			case 'list':
				// Retorna alguma coisa
				return $value;
				break;
			case 'edit':
				$campo['tipo_de_campo'] = 'select';
				$campo['separador'] = '';
				$campo['opcoes'] = array();
				
				foreach (glob(ROOT_PATH . '/_default/templates/*', GLOB_ONLYDIR) as $templateDir) {
					$relativeDir = str_replace(ROOT_PATH, '', $templateDir);
					$campo['opcoes'][$relativeDir] = basename($relativeDir);
				}
				$field = new InterAdminField($campo);
				return $field->getHtml();
				break;
		}
	}
	
}