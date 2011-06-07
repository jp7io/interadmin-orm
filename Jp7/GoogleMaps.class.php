<?php

class Jp7_GoogleMaps {
	const URL_WEBSERVICE = 'http://maps.google.com/maps/api/geocode/xml';
	
	// $unidade->endereco . ', ' . $unidade->bairro . ', ' . $unidade->cidade . ' - '. $unidade->estado->sigla
	public static function getLatLngByEndereco($endereco = '') {
		if ($endereco) {
			$url = self::URL_WEBSERVICE . '?address=' . urlencode(utf8_encode($endereco)) . '&sensor=false';
			$xmlFile = @file_get_contents($url);
			
			if (!$xmlFile) {
				return null;
			}
			
			$xmlDom = new SimpleXMLElement($xmlFile);
			return $xmlDom->result->geometry->location;
		}
	}	
}

