<?php
 
namespace Jp7\AssetPipeline\Filters;

use Assetic\Filter\FilterInterface;
use Assetic\Asset\AssetInterface;
  
class PhpFilter implements FilterInterface {
	
	public function __construct()
    {

    }
    
	/**
     * Filters an asset after it has been loaded.
     *
     * @param AssetInterface $asset An asset
     */
    public function filterLoad(AssetInterface $asset) {
    	
    }
    
    /**
     * Filters an asset just before it's dumped.
     *
     * @param AssetInterface $asset An asset
     */
    public function filterDump(AssetInterface $asset) {
    	$content = $asset->getContent();
    	
    	ob_start();
		eval('?>' . $content);
		$content = ob_get_clean();
		
        $asset->setContent($content);
    }
}