<?php

namespace Jp7\Interadmin\Field;

use Throwable;
use Log;

class FuncField extends ColumnField
{
    protected $id = 'func';

    public function getHeaderHtml()
    {
        return $this->getFuncHtml('', 'header');
    }

    public function getCellHtml()
    {
        return $this->getFuncHtml($this->getValue(), 'list');
    }

    protected function getFuncHtml($value, $parte)
    {
        if (!is_callable($this->nome)) {
            return 'Function '.$this->nome.' not found.';
        }
        try {
            ob_start();
            // http://wiki.jp7.com.br:81/jp7/InterAdmin:Special
            // callable(array $campo, mixed $value, string $parte, stdClass $record)
            $response = call_user_func($this->nome, $this->campo, $value, $parte, $this->record);
            $response .= ob_get_clean();
            return $response;
        } catch (Throwable $e) {
            Log::error($e);
            return '(erro: '.$this->nome.')';
        }
    }

    protected function getDefaultValue()
    {
        if ($this->default) {
            return $this->default;
        }
        if (isset($_POST[$this->tipo])) {
            return $_POST[$this->tipo][0];
        }
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getEditTag()
    {
        $html = trim($this->getFuncHtml($this->getValue(), 'edit'));

        if (starts_with($html, '<tr') || ends_with($html, '</tr>')) {
             $html = '<table class="special-shim">'.$html.'</table>';
        }
        return $html;
    }
}
