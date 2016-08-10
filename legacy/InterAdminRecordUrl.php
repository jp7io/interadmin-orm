<?php

class InterAdminRecordUrl
{
    /**
     * Returns the full url for this Type.
     *
     * @param string $action
     * @param array  $parameters
     *
     * @throws BadMethodCallException
     *
     * @return string
     */
    public static function getTypeUrl(Type $type)
    {
        global $config, $implicit_parents_names, $seo, $lang;
        // if ($this->_url) {
        //     return $this->_url;
        // }
        $url_arr = '';
        $parent = $type;
        while ($parent) {
            if (!isset($parent->nome)) {
                $parent->nome;
            }
            if ($seo) {
                if (!in_array($parent->nome, (array) $implicit_parents_names)) {
                    $url_arr[] = toSeo($parent->nome);
                }
            } else {
                if (toId($parent->nome)) {
                    $url_arr[] = toId($parent->nome);
                }
            }
            $parent = $parent->getParent();
            if ($parent instanceof InterAdmin) {
                $parent = $parent->getTipo();
            }
        }
        $url_arr = array_reverse((array) $url_arr);
        if ($seo) {
            $url = $config->url.$lang->path.jp7_implode('/', $url_arr);
        } else {
            $url = $config->url.$lang->path_url.implode('_', $url_arr);
            $pos = strpos($url, '_');
            if ($pos) {
                $url = substr_replace($url, '/', $pos, 1);
            }
            $url .= (count($url_arr) > 1) ? '.php' : '/';
        }
        //$this->_url = $url;
        return $url;
    }

    /**
     * Return URL from the route associated with this record.
     *
     * @param string $action Defaults to 'show'
     *
     * @return string
     *
     * @throws BadMethodCallException
     */
    public static function getRecordUrl(Record $record, $sep = null)
    {
        global $seo, $seo_sep;
        if ($seo && $record->getParent()->id) {
            $link = $record->_parent->getUrl().'/'.toSeo($record->getTipo()->nome);
        } else {
            $link = $record->getTipo()->getUrl();
        }
        if ($seo) {
            if (is_null($sep)) {
                $sep = $seo_sep;
            }
            $link .= $sep.toSeo($record->varchar_key);
        } else {
            $link .= '?id='.$record->id;
        }
        return $link;
    }
}
