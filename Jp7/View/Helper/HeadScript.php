<?php

class Jp7_View_Helper_HeadScript extends Zend_View_Helper_HeadScript {
	
	public function removeFile($filename) {
		$stack = $this->getContainer();
		foreach ($stack as $key => $value) {
			if (is_array($filename) && in_array($value->attributes['src'], $filename)) {
				unset($stack[$key]);
			} elseif ($value->attributes['src'] == $filename) {
				unset($stack[$key]);
				break;
			}
		}
	}
	
	/**
	 * Replace file maintaining the same key
	 * @param string $search Current file
	 * @param string $replace New file
	 * @return void
	 */
	public function replaceFile($search, $replace) {
		$stack = $this->getContainer();
		
		foreach ($stack as $key => $value) {
			if ($value->attributes['src'] == $search) {
				$value->attributes['src'] = $replace;
				break;
			}
		}
	}
	
	public function toString($indent = null) {
		$config = Zend_Registry::get('config');
		foreach ($this as $item) {
			if ($item->attributes['src'] && $config->build) {
				$item->attributes['src'] .= (strpos($item->attributes['src'], '?') ? '&' : '?') . 'build=' . $config->build;
        	}
		}
		return parent::toString($indent);	
	}

	/**
     * Create script HTML
     *
     * @param  string $type
     * @param  array $attributes
     * @param  string $content
     * @param  string|int $indent
     * @return string
     */
    public function itemToString($item, $indent, $escapeStart, $escapeEnd)
    {
        $attrString = '';
        if (!empty($item->attributes)) {
            foreach ($item->attributes as $key => $value) {
                if (!$this->arbitraryAttributesAllowed()
                    && !in_array($key, $this->_optionalAttributes))
                {
                    continue;
                }

                if ($key == 0 && $value == 'async') {
                	$attrString .= sprintf(' %s', $value);
                	continue;
                }

                if ('defer' == $key) {
                    $value = 'defer';
                }

                $attrString .= sprintf(' %s="%s"', $key, ($this->_autoEscape) ? $this->_escape($value) : $value);
            }
        }

        $type = ($this->_autoEscape) ? $this->_escape($item->type) : $item->type;
        $html  = '<script type="' . $type . '"' . $attrString . '>';
        if (!empty($item->source)) {
              $html .= PHP_EOL . $indent . '    ' . $escapeStart . PHP_EOL . $item->source . $indent . '    ' . $escapeEnd . PHP_EOL . $indent;
        }
        $html .= '</script>';

        if (isset($item->attributes['conditional'])
            && !empty($item->attributes['conditional'])
            && is_string($item->attributes['conditional']))
        {
            $html = $indent . '<!--[if ' . $item->attributes['conditional'] . ']> ' . $html . '<![endif]-->';
        } else {
            $html = $indent . $html;
        }

        return $html;
    }
}
