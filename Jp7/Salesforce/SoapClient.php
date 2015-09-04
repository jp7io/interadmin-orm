<?php

class Jp7_Salesforce_SoapClient extends SoapClient
{
    public function query($params)
    {
        $response = parent::query($params);
        // FIX: https://github.com/developerforce/Force.com-Toolkit-for-PHP/issues/44
        if (isset($response->result) && is_object($response->result->records)) {
            $response->result->records = [$response->result->records];
        }
        return $response;
    }
}
