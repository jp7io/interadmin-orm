<?php 
class Jp7_JSMin extends JSMin {

    public static function groupJavascript($files, $cache_dir = './cache', $cache_filename = 'head.js') {
        $cache_file = $cache_dir . '/' . $cache_filename;
        
		foreach ($files as $key => $file) {
        	// Descobrindo localização como se fosse client side
        	if (startsWith('/', $file)) {
        		// /_default/js/interdyn.js
        		$files[$key] = $_SERVER['DOCUMENT_ROOT'] . $file;
			} elseif (!startsWith('http://', $file)) {
				$files[$key] = APPLICATION_PATH . '/../' . $file;
			}
		}
		
        // determine if we need to rebuild the cache
        $cache_mtime = @filemtime($cache_file);
        $build_cache = false;
        if ($cache_mtime === false) {
            $build_cache = true;
        } else {
        	// Somente verifica os timestamp localmente, para não atrapalhar desenvolvimento
        	if (strpos($_SERVER['HTTP_HOST'], '.') === false) {
	            foreach ($files as $key => $file) {
	            	if (startsWith('http://', $file)) {
	            		continue;	
					}
					$file_mtime = filemtime($file);
	                if ($file_mtime !== false && $file_mtime > $cache_mtime) {
	                    $build_cache = true;
						break;
	                }
				}
			}
        }
        
        $min_content = '';
        
        if ($build_cache) {
            $outputs = '';
            foreach ($files as $file) {
                $contents = @file_get_contents($file);
                if ($contents == false) {
                    error_log('Jp7_JSMin: Error reading file "' . $file . '"');
                    //echo "JSERROR: " . $file . ", " . jp7_path_find($file) . "/n";
                } else {
                    $outputs .= $contents;
                    $outputs .= "\n";
                }
            }
            
            $min_content = self::minify($outputs);
            //$min_content = $outputs;
            file_put_contents($cache_file, $min_content);
        } else {
            $min_content = file_get_contents($cache_file);
        }
		
		header('Pragma: ');
		header('Cache-Control: ');
		header('Content-Type: application/javascript');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Expires: ' . gmdate('D, d M Y H:i:s', strtotime('+1 month')) . ' GMT');
        return $min_content;
    }
}
