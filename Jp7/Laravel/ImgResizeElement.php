<?php

namespace Jp7\Laravel;

use HtmlObject\Image;

class ImgResizeElement extends Image
{
    private $lazy = false;
    
    public function setLazy($status)
    {
        $this->lazy = (bool) $status;
    }
    
    
    // Add <noscript> tags if needed
    public function render()
    {
        $noscript = '';
        
        if ($this->lazy) {
            $attributes = $this->getAttributes();
            $attributes['src'] = $attributes['data-src'];
            unset($attributes['data-src']);
            unset($attributes['srcset']);
            
            $noscript = '<noscript>'.Image::create()->setAttributes($attributes).'</noscript>';
        }
        
        return parent::render().$noscript;
    }
}