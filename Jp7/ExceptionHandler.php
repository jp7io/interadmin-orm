<?php

namespace Jp7;

class ExceptionHandler
{
    public static function handle($exception)
    {
        $mensagem = self::getMessage($exception);

        $subject = '['.config('app.name').'][Site][Erro] '.$exception->getMessage();
        $headers  = 'MIME-Version: 1.0'."\r\n";
        $headers .= 'Content-type: text/html; charset=iso-8859-1'."\r\n";
        
        mail('debug@jp7.com.br', $subject, $mensagem, $headers);

        return error_controller('error');
    }

    public static function getMessage($exception)
    {
        $mensagem = '<div style="font-family:monospace">';

        $mensagem .= $exception->getMessage().'<br><br>';
        $mensagem .= self::strong('FILE').$exception->getFile().'<br>';
        $mensagem .= self::strong('LINE').$exception->getLine().'<br><hr>';

        $mensagem .= self::strong('URL').(isset($_SERVER['HTTPS']) ? 'https://' : 'http://').
            $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].PHP_EOL;
        $mensagem .= '<hr />';

        $mensagem .= self::highlightCode($exception);
        $mensagem .= self::getBacktrace($exception->getTrace());

        $mensagem .= '<hr />';

        foreach (['_POST', '_GET', '_COOKIE', '_SESSION', '_SERVER'] as $superglobal) {
            $mensagem .= self::superglobal($superglobal);
        }

        $mensagem .= '</div>';

        return $mensagem;
    }

    public static function getBacktrace($backtrace = null)
    {
        if (!$backtrace) {
            $backtrace = debug_backtrace();
            krsort($backtrace);
        }

        $html = '<hr />';
        $html .= self::strong('CALL STACK').'<br />';
        $html .= '<table class="jp7_debugger_table"><tr><th>#</th><th>Function</th><th>Location</th></tr>';

        foreach ($backtrace as $key => $row) {
            $row = new RiskyArray($row);

            $html .= '<tr><td>'.(count($backtrace) - $key).'</td>';
            $html .= '<td>'.$row['class'].$row['type'].$row['function'].'()</td>';
            $html .= '<td>'.str_replace(base_path(), '', $row['file']).':'.$row['line'].'</td></tr>';
        }
        $html .= '</table>';

        return $html;
    }

    public static function superglobal($name)
    {
        if (!empty($GLOBALS[$name])) {
            return self::strong($name).'<pre>'.print_r($GLOBALS[$name], true).'</pre>';
        }
    }

    protected static function strong($caption)
    {
        return '<strong style="color:red">'.str_pad($caption, 12, ' ', STR_PAD_LEFT).':</strong> ';
    }

    public static function highlightCode($exception)
    {
        $lines = explode(PHP_EOL, file_get_contents($exception->getFile()));

        $line = $exception->getLine();
        $offset = max($line - 10, 0);

        $lines[$line - 1] .= ' // <== ERROR';
        $code = implode(PHP_EOL, array_slice($lines, $offset, 20));

        $code = ($offset > 0 ? '<?php'.PHP_EOL : '').$code;

        return highlight_string($code, true);
    }
}
