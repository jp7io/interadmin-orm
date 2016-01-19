<?php

/**
 * JP7's PHP Functions.
 *
 * Contains the main custom functions and classes.
 *
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 *
 * @category Jp7
 */

/**
 * Generates links for Social Bookmarking.
 *
 * @author Diego Marangoni
 */
class Social
{
    /* Bookmarking */
    public $displaySocials = [];

    private $mainSocials = [
        'delicious' => [
            'title' => 'Delicious',
            'url' => 'http://del.icio.us/post?url=%url%&title=%title%',
        ],
        'myweb' => [
            'title' => 'Yahoo MyWeb',
            'url' => 'http://myweb2.search.yahoo.com/myresults/bookmarklet?u=%url%&t=%title%',
        ],
        'google' => [
            'title' => 'Google Bookmarks',
            'url' => 'http://www.google.com/bookmarks/mark?op=edit&bkmk=%url%&title=%title%',
        ],
        'stumbleupon' => 'StumbleUpon',
        'digg' => [
            'title' => 'Digg',
            'url' => 'http://digg.com/submit?phase=2&url=%url%&title=%title%',
        ],
        'live' => [
            'title' => 'Windows Live',
            // Idioma na string (mkt = pt-br)
            'url' => 'https://favorites.live.com/quickadd.aspx?marklet=1&mkt=pt-br&url=%url%&title=%title%&top=1',
        ],
        'reddit' => 'Reddit',
    ];
    private $extendedSocials = [
        'twitter' => [
            'title' => 'Twitter',
            'url' => 'http://twitter.com/?status=%title% - %url%',
        ],
        'linkedin' => [
            'title' => 'LinkedIn',
            'url' => 'http://www.linkedin.com/shareArticle?mini=true&url=%url%&title=%title%&summary=&source=',
        ],
        'facebook' => [
            'title' => 'Facebook',
            'url' => 'http://www.facebook.com/share.php?u=%url%',
        ],
        'myspace' => [
            'title' => 'MySpace',
            'url' => 'http://www.myspace.com/Modules/PostTo/Pages/?l=3&u=%url%&t=%title%&c=',
        ],
        'slashdot' => 'Slashdot',
        'ask' => 'Ask',
        'blinklist' => 'Blinklist',
        'multiply' => 'Multiply',
        'technorati' => [
            'title' => 'Technorati',
            'url' => 'http://www.technorati.com/faves?add=%url%',
        ],
        'yahoobkm' => [
            'title' => 'Yahoo Bookmarks',
            'url' => 'http://bookmarks.yahoo.com/toolbar/savebm?opener=tb&u=%url%&t=%title%',
        ],
    ];
    private $otherSocials = [
        'propeller' => 'Propeller',
        'backflip' => 'Backflip',
        'kaboodle' => 'Kaboodle',
        'linkagogo' => 'Link-a-Gogo',
        'segnalo' => 'Segnalo',
        'blogmarks' => 'Blogmarks',
        'magnolia' => 'Magnolia',
        'spurl' => 'Spurl',
        'diigo' => 'Diigo',
        'misterwong' => 'Mister Wong',
        'mixx' => 'Mixx',
        'tailrank' => 'Tailrank',
        'fark' => 'Fark',
        'bluedot' => 'Faves (Bluedot)',
        'aolfav' => 'myAOL',
        'favorites' => 'Favorites',
        'feedmelinks' => 'FeedMeLinks',
        'netvouz' => 'Netvouz',
        'furl' => 'Furl',
        'newsvine' => 'Newsvine',
        'yardbarker' => 'Yardbarker',
    ];
    private $allSocials = [];

    private function createSocial($theme)
    {
        // Merge all socials
        $this->allSocials = array_merge($this->mainSocials, $this->extendedSocials);
        $this->allSocials = array_merge($this->allSocials, $this->otherSocials);

        switch ($theme) {
            case 'main':
                $this->displaySocials = $this->mainSocials;
                break;
            case 'extended':
                $this->displaySocials = array_merge($this->mainSocials, $this->extendedSocials);
                break;
            case 'all':
                $this->displaySocials = $this->allSocials;
                break;
            case 'custom':
                $this->displaySocials = [];
                break;
            default:
                $this->displaySocials = $this->mainSocials;
                break;
        }
    }
    private function addSocials($add)
    {
        // Add socials
        foreach ($add as $item) {
            if (array_key_exists($item, $this->allSocials)) {
                $this->displaySocials[$item] = $this->allSocials[$item];
            } else {
                exit('The social "'.$item.'" not exists.');
            }
        }
    }
    private function removeSocials($remove)
    {
        // Remove socials
        foreach ($remove as $item) {
            if (array_key_exists($item, $this->allSocials)) {
                unset($this->displaySocials[$item]);
            } else {
                exit('This social does not exist.');
            }
        }
    }

    public function newBookmark($theme = 'main', $add = [], $remove = [])
    {
        // Create a social
        self::createSocial($theme);
        // Add socials if passed
        if ($add) {
            self::addSocials($add);
        }
        // Remove socials if passed
        if ($remove) {
            self::removeSocials($remove);
        }
    }
    public function displayBookmark($limiter = 5, $url = false, $title = '', $target = '_blank', $customUrl = [])
    {
        // Must create a social first
        if (!$this->displaySocials) {
            exit('You must creat a social first.');
        }

        // Set the default configuration
        if (!$url) {
            $url = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        }

        /*
         * Por form, que tem um bug de resize no firefox, ou por script JS que usa a função add_this e abre sempre em nova janela
        $html .= '<script type="text/javascript">' . "\n";
        $html .= 'function sets(val) {' . "\n";
        $html .= 'elt = document.getElementById(\'bookmarkingMys\');' . "\n";
        $html .= 'elt.value = val;' . "\n";
        $html .= 'elt = document.getElementById(\'bookmarkingWinname\');' . "\n";
        $html .= 'elt.value = window.name;' . "\n";
        $html .= 'elt = document.getElementById(\'bookmarkingForm\');' . "\n";
        $html .= 'elt.submit();' . "\n";
        $html .= '}' . "\n";
        $html .= '</script>' . "\n";

        $html .= '<form id="bookmarkingForm" action="http://www.addthis.com/bookmark.php" method="post" target="' . $target . '">' . "\n";
        $html .= '<input type="hidden" id="bookmarkingAte" name="ate" value="AT-internal/-/-/-/-/-" />' . "\n";
        $html .= '<input type="hidden" id="bookmarkingMys" name="s" value="" />' . "\n";
        $html .= '<input type="hidden" id="bookmarkingPub" name="pub" value="" />' . "\n";
        $html .= '<input type="hidden" id="bookmarkingUrl" name="url" value="' . $url . '" />' . "\n";
        $html .= '<input type="hidden" id="bookmarkingTitle" name="title" value="' . $title . '" />' . "\n";
        $html .= '<input type="hidden" id="bookmarkingLng" name="lng" value="" />' . "\n";
        $html .= '<input type="hidden" id="bookmarkingWinname" name="winname" value="" />' . "\n";
        $html .= '<input type="hidden" id="bookmarkingContent" name="content" value="" />' . "\n";
        $html .= '</form>' . "\n";
        */

        $html = '<div id="bookmark">'."\n";
        $html .= '<ul>'."\n";
        $count = 1;
        foreach ($this->displaySocials as $key => $value) {
            if (is_array($value)) {
                if ($customUrl[$key]) {
                    $url = $customUrl[$key];
                }

                $value['url'] = str_replace('%url%', urlencode($url), $value['url']);
                $value['url'] = str_replace('%title%', urlencode($title), $value['url']);
                $html .= '<li class="at15t at15t_'.$key.'"><a href="'.$value['url'].'" id="social_'.$key.'" target="'.$target.'">'.$value['title'].'</a></li>'."\n";
            } else {
                #$html .= '<li><a href="javascript:sets(\'' . $key . '\');"><span class="at15t at15t_' . $key . '">' . $value . '</span></a></li>' . "\n";
                $html .= '<li><a href="javascript:void(0);" id="social_'.$key.'" onclick="return addthis_sendto(\''.$key.'\');"><span class="at15t at15t_'.$key.'">'.$value.'</span></a></li>'."\n";
            }

            if ($count == $limiter) {
                $html .= '</ul>'."\n";
                $html .= '<ul>'."\n";
                $count = 0;
            }
            $count++;
        }
        $html .= '</ul>'."\n";
        $html .= '<div style="clear: both;"></div>'."\n";
        $html .= '</div>'."\n";

        return $html;
    }
    /* Bookmarking */

    /* Send to a friend */
    public function displaySendFriend($id_tipo, $url = false, $title = '', $template = 'default')
    {
        global $xtra_disabledfields;
        // Set the default configuration
        if (!$url) {
            $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }
        $html = '<form action="" id="interadminForm">'."\n";
        $html .= '<input type="hidden" name="url" value="'.$url.'" />'."\n";
        $html .= '<input type="hidden" name="title" value="'.$title.'" />'."\n";
        $html .= '<input type="hidden" name="template" value="'.$template.'" />'."\n";
        $html .= '<table class="form">'."\n";

        $form = new InterAdminTipo($id_tipo);
        $fields = $form->getCampos();

        ob_start();
        foreach ($fields as $field) {
            if ($field['tipo'] != 'varchar_key' && !in_array($field['tipo'], (array) $xtra_disabledfields)) { // Must be different from URL field
                interadmin_returnCampo($field);
            }
        }
        $html .= ob_get_contents();
        ob_end_clean();

        $html .= '<tr>'."\n";
        $html .= '<td></td>'."\n";
        $html .= '<td><input type="button" value="ENVIAR" id="sendfriend_send" /></td>'."\n";
        $html .= '</tr>'."\n";
        $html .= '</table>'."\n";
        $html .= '</form>'."\n";

        return $html;
    }
    /* Send to a friend */

    /* Embedeed */
    public function displayEmbedded($title = 'Clique aqui', $url = false, $type = 'link', $label = 'Endereço:', $divId = 'embedded')
    {
        if (!$url) {
            $url = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        }

        $html = '<div id="'.$divId.'">'."\n";
        if ($label) {
            $html .= '<label for="embeddedUrl'.ucfirst(mb_strtolower($key)).'">'.$label.'</label>'."\n";
        }
        $html .= '<textarea id="embeddedUrl'.ucfirst(mb_strtolower($key)).'" readonly="readonly" onclick="javascript:document.getElementById(\'embeddedUrl'.ucfirst(mb_strtolower($key)).'\').focus();document.getElementById(\'embeddedUrl'.ucfirst(mb_strtolower($key)).'\').select();">'."\n";
        /*if ($type == 'link') {
            $html .= '<a href="' . $url . '" target="_blank">' . $title . '</a>';
        } else {
            exit('This type is not implemented yet');
        }*/
        $html .= '</textarea>'."\n";
        $html .= '<div style="clear:both;"></div>';
        $html .= '</div>'."\n";

        return $html;
    }
    /* Embedeed */
}
