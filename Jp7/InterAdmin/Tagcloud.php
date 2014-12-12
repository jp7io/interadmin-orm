<?php
class Jp7_InterAdmin_Tagcloud
{
	public $maxFontSize = 20;
	public $minFontSize = 9;
	public $limit = 10;
	public function getTags($class = 'InterAdmin') {
		
		global $db, $db_prefix, $lang;
		$DbNow = $db->BindTimeStamp(date("Y-m-d H:i:s"));
		$tags_arr = array();
		$sql = "SELECT registros.id, registros.hits" .
			" FROM " . $db_prefix . $lang->prefix . " AS registros" .
			" INNER JOIN " . $db_prefix . $lang->prefix . "_tags AS tags" .
			" ON registros.id = tags.parent_id" .
			" WHERE registros.hits > 0" .
			" AND registros.char_key <> ''" .
			" AND registros.deleted = ''" .
			" AND (registros.date_publish <= '" . $DbNow . "' OR registros.date_publish IS NULL)" .
			" AND (registros.date_expire > '" . $DbNow . "' OR registros.date_expire IS NULL OR registros.date_expire='0000-00-00 00:00:00')" .
			" GROUP BY registros.id" .
			" ORDER BY DATE(registros.date_hit) DESC, HOUR(registros.date_hit) DESC, registros.hits DESC" .
			" LIMIT " . $this->limit * 2;
		
		$rs = $db->Execute($sql) or die(jp7_debug($db->ErrorMsg(), $sql));
		while ($row = $rs->FetchNextObj()) {
			$interadminCloud = new $class($row->id);
			$interadminCloud->hits = $row->hits;
			$tags_arr = array_merge($tags_arr, $interadminCloud->getTags());
		}
		$rs->Close();
				
		$tags_arr_unique = array();
		foreach ($tags_arr as $tag) {
			$tag_key = ($tag->id) ? $tag->id_tipo . ';' . $tag->id : $tag->id_tipo;
			$tags_arr_unique[$tag_key]['obj'] = $tag;
			$tags_arr_unique[$tag_key]['hits'] += $tag->interadmin->hits;
		}
		$min = 1000;
		$max = 0;
		
		foreach ($tags_arr_unique as $tag) {
			if ($tag['hits'] < $min) {
				$min = $tag['hits'];
			}
			if ($tag['hits'] > $max) {
				$max = $tag['hits'];
			}
		}
		
		$diff = $max - $min;
		$fontSizeDiff = $this->maxFontSize - $this->minFontSize;
		foreach ($tags_arr_unique as $key => $tag) {
			$obj = $tag['obj'];
			if ($max > $min) {
				if ($this->type == 'linear') {
					// Linear
					$weight = ($tag['hits'] - $min) / $diff;
				} else {
					// Logarítmo
					$weight = (log($tag['hits']) - log($min)) / (log($max) - log($min));
				}
				// Final
				$tags_arr_unique[$key]['fontSize'] = $this->minFontSize + round($fontSizeDiff * $weight);
			} else {
				$tags_arr_unique[$key]['fontSize'] = $this->minFontSize + round($fontSizeDiff / 2);
			}
		}
		return array_slice($tags_arr_unique, 0, $this->limit);
	}
}
?>