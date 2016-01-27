<?php

class Jp7_Mail extends Zend_Mail
{
    protected $_charset = 'utf-8';
    
    /*
     * Parses an e-mail string and passes it to the given Zend_Mail method.
     *
     * @param string Name of the method to be used on Zend_Mail.
     * @param string E-mail on any of these formats: "name surname <e-mail@anything.com>" or "e-mail@anything.com".
     * @return Zend_Mail Provides fluent interface.
     * @throws Exception for empty e-mails or not supported methods.
     */
    public function parseEmailAndSet($method, $email)
    {
        $allowedMethods = ['addTo', 'setFrom', 'addBcc', 'addCc', 'setReturnPath'];

        if (!in_array($method, $allowedMethods)) {
            throw new Zend_Mail_Exception('Invalid method for this function.');
        }
        //if (!$email) throw new Zend_Mail_Exception('Cannot parse an empty email.');

        $firstPart = trim(strtok($email, '<>')); // Name or e-mail
        $secondPart = trim(strtok('<>')); // E-mail or empty

        if ($secondPart) {
            if ($method == 'setReturnPath' || $method == 'addBcc') {
                $this->$method($secondPart);
            } // Email only
            else {
                $this->$method($secondPart, $firstPart);
            } // Email, Name
        } else {
            $this->$method($firstPart); // Email
        }

        return $this;
    }

    public function setReturnPathAndTransport($email)
    {
        $tr = new Zend_Mail_Transport_Sendmail('-f'.$email);

        self::setDefaultTransport($tr);

        $this->parseEmailAndSet('setReturnPath', $email);

        ini_set('sendmail_from', $email);
    }

    public function restoreReturnPath()
    {
        self::clearDefaultTransport();
        ini_restore('sendmail_from');
    }
    
    /**
     * Formats and sends an e-mail message.
     *
     * @param string $to          Receiver, or receivers of the mail.
     * @param string $subject     Subject of the email to be sent.
     * @param string $message     Message to be sent.
     * @param string $headers     String to be inserted at the begin of the email header (only if $html is <tt>FALSE</tt>).
     * @param string $parameters  DEPRECATED
     * @param string $template    Path to the template file.
     * @param bool   $html        If <tt>FALSE</tt> will send the message on the text-only format. The default value is <tt>TRUE</tt>.
     *
     * @see http://www.php.net/manual/en/function.mail.php
     *
     * @return Jp7_Mail
     */
    public static function legacy($to, $subject, $message, $headers = '', $parameters = '', $template = '', $html = true)
    {
        global $debug, $config;
        // Mensagem alternativa em texto
        if (strpos($message, '<br>') !== false) {
            $text_hr = '';
            for ($i = 0; $i < 80; $i++) {
                $text_hr .= '-';
            }
            $message_text = str_replace("\r", '', $message);
            $message_text = str_replace("\n", '', $message_text);
            $message_text = str_replace('&nbsp;', ' ', $message_text);
            $message_text = str_replace('<hr size=1 color="#666666">', $text_hr."\r\n", $message_text);
            $message_text = str_replace('<br>', "\r\n", $message_text);
        }
        $message_text = strip_tags($message_text);
        // HTML
        if ($html) {
            $message_html = str_replace("\r\n", "\n", $message); // PC to Linux
            $message_html = str_replace("\r", "\n", $message_html); // Mac to Linux
            $message_html = str_replace("\n", "\r\n", $message_html); // Linux to Mail Format
            if (strpos($message_html, '<br>') === false && strpos($message, '<html>') === false) {
                $message_html = str_replace("\r\n", "<br>\r\n", $message_html); // Linux to Mail Format
            }
            if ($template) {
                @ini_set('allow_url_fopen', '1');
                if ((!dirname($template) || dirname($template) == '.') && @ini_get('allow_url_fopen')) {
                    $template = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']).'/'.$template;
                }
                if ($pos1 = strpos($template, '?')) {
                    //$template=mb_substr($template,0,$pos1+1).urlencode(mb_substr($template,$pos1+1));
                    $template = str_replace(' ', '%20', $template);
                }
                if (strpos($template, 'http://') !== 0) {
                    $template = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']).'/'.$template;
                }
                //valida usuário logado e caso o template inicie em http
                if ($_SERVER['PHP_AUTH_USER']) {
                    $template = str_replace('http://', 'http://'.$_SERVER['PHP_AUTH_USER'].':'.$_SERVER['PHP_AUTH_PW'].'@', $template);
                }
                $template = file_get_contents($template);

                //echo "template: ".$template;
                $message_html = str_replace('%MESSAGE%', $message_html, $template);
            }
        } else {
            $message_html = $message_text;
        }
    
        $object = new static;
        
        $headersArray = http_parse_headers($headers);
        foreach ($headersArray as $key => $value) {
            switch (strtolower($key)) {
                case 'from':
                    $object->parseEmailAndSet('setFrom', $value);
                    break;
                case 'cc':
                    foreach (explode(',', $value) as $ccAddress) {
                        $object->parseEmailAndSet('addCc', $ccAddress);
                    }
                    break;
                case 'bcc':
                    foreach (explode(',', $value) as $bccAddress) {
                        $object->parseEmailAndSet('addBcc', $bccAddress);
                    }
                    break;
                case 'content-type':
                    break;
                default:
                    $object->addHeader($key, $value);
            }
        }
        
        $object->addHeader('Return-Errors-To', 'sites@jp7.com.br');
        $object->setSubject($subject);
        
        // Send
        foreach (explode(',', $to) as $toAddress) {
            $object->parseEmailAndSet('addTo', $toAddress);
        }
        
        if ($config->server->type != InterSite::PRODUCAO) {
            $object->clearRecipients();
            $object->addTo('debug@jp7.com.br');
        }
        
        $object->setBodyHtml($message_html);
        $object->setBodyText($message_text);
        return $object;
    }
}
