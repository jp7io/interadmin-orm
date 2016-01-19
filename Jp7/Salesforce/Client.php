<?php

/**
 * Client to connect to Salesforce Web Services.
 */
class Jp7_Salesforce_Client extends SforceEnterpriseClient
{
    const MAX_RETURNED_ROWS = 2000;

    private $wsdl;

    /**
     * Executes a query using Salesforce WebServices client and returns the records.
     *
     * @param array|string $options Query String or Array of options with keys: fields, from, where, group, order, limit.
     *
     * @return QueryResult
     */
    public function query($options)
    {
        if (is_array($options)) {
            $query = $this->_parseOptions($options);
            if ($options['limit']) {
                // Begin: Faking OFFSET, LIMIT, Salesforce doesn't support OFFSET inside the LIMIT clause
                $limitArr = explode(',', $options['limit']);
                if (count($limitArr) > 1) {
                    list($offset, $limit) = array_map('intval', $limitArr);
                } else {
                    list($offset, $limit) = [0, intval($options['limit'])];
                }
                $options['limit'] = $offset + $limit;
                $query .= ' LIMIT '.$options['limit'];

                $result = parent::query($query);
                while ($offset + $limit > self::MAX_RETURNED_ROWS) {
                    $offset -= self::MAX_RETURNED_ROWS;
                    $result = $this->queryMore($result->queryLocator);
                }
                $result->records = array_slice($result->records, $offset, $limit);
                // End: Faking OFFSET, LIMIT
                return $result;
            }
        } else {
            $query = $options;
        }

        return parent::query($query);
    }

    protected function _parseOptions($options)
    {
        // Prepares SOQL parameters
        $query = 'SELECT '.implode(',', (array) $options['fields']).
            ' FROM '.$options['from'];
        if ($options['where']) {
            $query .= ' WHERE '.implode(' AND ', (array) $options['where']);
        }
        if ($options['group']) {
            $query .= ' GROUP BY '.$options['group'];
        }
        if ($options['order']) {
            $query .= ' ORDER BY '.$options['order'];
        }
        if ($options['debug']) {
            krumo($query);
        }

        return $query;
    }

    public function queryNoLimits($options)
    {
        if (is_array($options)) {
            $query = $this->_parseOptions($options);

            $result = parent::query($query);
            $allRecords = $result->records;
            while ($result->size > count($allRecords)) {
                $result = $this->queryMore($result->queryLocator);
                $allRecords = array_merge($allRecords, $result->records);
            }
            $result->records = $allRecords;

            return $result;
        } else {
            throw new Exception('Salesforce_Client->queryNoLimits expects $options to be an array.');
        }
    }

    /**
     * Login to Salesforce.com and starts a client session.
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return LoginResult
     */
    public function login($username, $password)
    {
        $this->sforce->__setSoapHeaders(null);
        if ($this->callOptions != null) {
            $this->sforce->__setSoapHeaders([$this->callOptions]);
        }
        if ($this->loginScopeHeader != null) {
            $this->sforce->__setSoapHeaders([$this->loginScopeHeader]);
        }
        // JP7 - Adicionado Cache
        $skey = 's_salesforce_cache';
        $cache = $_SESSION[$skey][$this->wsdl];

        if ($cache && $cache['saved'] > strtotime('-10 minutes')) {
            $result = $cache['result'];
        } else {
            $result = $this->sforce->login([
             'username' => $username,
             'password' => $password,
            ]);
            $result = $result->result;

            $_SESSION[$skey][$this->wsdl] = [
                'result' => $result,
                'saved' => time(),
            ];
        }
        $this->_setLoginHeader($result);

        return $result;
    }
}
