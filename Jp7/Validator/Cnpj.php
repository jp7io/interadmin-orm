<?php

namespace Jp7\Validator;

class Cnpj
{
    public function validate($attribute, $value, $parameters)
    {
        $cnpj = trim(preg_replace('/[^0-9]/', '', $value));
        $soma = 0;
        $multiplicador = 0;
        $multiplo = 0;

        if (empty($cnpj) || strlen($cnpj) != 14) {
            return false;
        }

        for ($i = 0; $i <= 9; $i++) {
            $repetidos = str_pad('', 14, $i);
            if ($cnpj === $repetidos) {
                return false;
            }
        }

        $parte1 = substr($cnpj, 0, 12);
        $parte1_invertida = strrev($parte1);
        for ($i = 0; $i <= 11; $i++) {
            $multiplicador = ($i == 0) || ($i == 8) ? 2 : $multiplicador;
            $multiplo = ($parte1_invertida[$i] * $multiplicador);
            $soma += $multiplo;
            $multiplicador++;
        }
        $rest = $soma % 11;
        $dv1 = ($rest == 0 || $rest == 1) ? 0 : 11 - $rest;

        $parte1 .= $dv1;
        $parte1_invertida = strrev($parte1);
        $soma = 0;

        for ($i = 0; $i <= 12; $i++) {
            $multiplicador = ($i == 0) || ($i == 8) ? 2 : $multiplicador;
            $multiplo = ($parte1_invertida[$i] * $multiplicador);
            $soma += $multiplo;
            $multiplicador++;
        }
        $rest = $soma % 11;
        $dv2 = ($rest == 0 || $rest == 1) ? 0 : 11 - $rest;

        return ($dv1 == $cnpj[12] && $dv2 == $cnpj[13]) ? true : false;
    }
}
