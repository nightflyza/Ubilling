<?php

/**
 * Implements lightweight InfluxDB client API
 */
class TinyInflux {

    /**
     * InfluxDB HTTP API base URL without trailing slash
     *
     * @var string
     */
    protected $baseUrl = '';

    /**
     * Default connection timeout in seconds
     *
     * @var int
     */
    protected $timeout = 2;

    /**
     * InfluxDB database name
     *
     * @var string
     */
    protected $database = '';

    /**
     * InfluxDB user name
     *
     * @var string
     */
    protected $user = '';

    /**
     * InfluxDB user password
     *
     * @var string
     */
    protected $password = '';

    /**
     * Default timestamp precision
     *
     * @var string
     */
    protected $precision = 'ns';

    /**
     * InfluxDB API version: 1 or 2
     *
     * @var int
     */
    protected $version = 1;

    /**
     * InfluxDB v2 organization
     *
     * @var string
     */
    protected $org = '';

    /**
     * InfluxDB v2 bucket
     *
     * @var string
     */
    protected $bucket = '';

    /**
     * InfluxDB v2 auth token
     *
     * @var string
     */
    protected $token = '';

    /**
     * Creates new TinyInflux instance
     *
     * @param string $host - InfluxDB host name or IP address
     * @param int $port - InfluxDB port
     * @param string $database - InfluxDB database name
     * @param string $user - InfluxDB user name - optional, if not provided, no authentication will be used
     * @param string $password - InfluxDB user password - optional, if not provided, no authentication will be used
     * @param string $precision - InfluxDB timestamp precision: ns, us, ms, s, m, h
     * @param int $version - InfluxDB version: 1 or 2
     */
    public function __construct($host = '127.0.0.1', $port = 8086, $database = '', $user = '', $password = '', $precision = 'ns', $version = 1) {
        $this->baseUrl = 'http://' . rtrim($host, '/') . ':' . intval($port);
        $this->database = $database;
        $this->user = $user;
        $this->password = $password;
        if (!empty($precision)) {
            $this->precision = $precision;
        }
        $this->version = intval($version);
    }

    /**
     * Sets InfluxDB v2 specific parameters
     *
     * @param string $org
     * @param string $bucket
     * @param string $token
     *
     * @return void
     */
    public function setV2Params($org, $bucket, $token) {
        $this->org = $org;
        $this->bucket = $bucket;
        $this->token = $token;
    }

    /**
     * Writes single point to InfluxDB
     *
     * @param string $measurement measurement name - mandatory, must be a non-empty string
     * @param array $fields fields array - mandatory, must contain at least one field
     * @param array $tags tags array - optional, if not provided, no tags will be added
     * @param mixed $timestamp timestamp in ISO 8601 format - if not provided, current timestamp will be used
     *
     * @return bool
     *
     * @throws Exception
     */
    public function writePoint($measurement, $fields, $tags = array(), $timestamp = '') {
        $result = false;

        $line = $this->buildLineProtocol($measurement, $fields, $tags, $timestamp);
        if (empty($line)) {
            throw new Exception('TinyInflux: empty line protocol payload');
        }

        if ($this->version == 2) {
            $url = $this->baseUrl . '/api/v2/write';
            $headers = array('Content-Type: text/plain; charset=utf-8');
            if (!empty($this->token)) {
                $headers[] = 'Authorization: Token ' . $this->token;
            }
            $queryParams = array();
            if (!empty($this->org)) {
                $queryParams['org'] = $this->org;
            }
            if (!empty($this->bucket)) {
                $queryParams['bucket'] = $this->bucket;
            }
            if (!empty($this->precision)) {
                $queryParams['precision'] = $this->precision;
            }
            $req = $this->doRequest($url, 'POST', $headers, $line, $queryParams);
        } else {
            $url = $this->baseUrl . '/write';
            $headers = array('Content-Type: text/plain; charset=utf-8');
            $queryParams = array();
            if (!empty($this->database)) {
                $queryParams['db'] = $this->database;
            }
            if (!empty($this->precision)) {
                $queryParams['precision'] = $this->precision;
            }
            $req = $this->doRequest($url, 'POST', $headers, $line, $queryParams, $this->user, $this->password);
        }

        if ($req['httpCode'] != 204 and $req['httpCode'] != 200) {
            $msg = 'TinyInflux write failed';
            if (!empty($req['errorMessage'])) {
                $msg .= ': ' . $req['errorMessage'];
            }
            $msg .= ' (HTTP ' . $req['httpCode'] . ')';
            if (!empty($req['response'])) {
                $msg .= '. Response: ' . $req['response'];
            }
            throw new Exception($msg);
        }

        $result = true;
        return ($result);
    }

    /**
     * Performs simple query against InfluxDB and returns raw response or parsed JSON
     *
     * @param string $query
     * @param bool $decodeJson
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function query($query, $decodeJson = true) {
        $result = '';

        if (empty($query)) {
            throw new Exception('TinyInflux: empty query string');
        }

        if ($this->version == 2) {
            $url = $this->baseUrl . '/api/v2/query';
            $headers = array('Content-Type: application/json');
            if (!empty($this->token)) {
                $headers[] = 'Authorization: Token ' . $this->token;
            }
            $queryParams = array();
            if (!empty($this->org)) {
                $queryParams['org'] = $this->org;
            }
            $body = json_encode(array('query' => $query));
            $req = $this->doRequest($url, 'POST', $headers, $body, $queryParams);
        } else {
            $url = $this->baseUrl . '/query';
            $headers = array();
            $usePost = $this->isDdlQuery($query);
            if ($usePost) {
                $queryParams = array();
                $body = 'q=' . rawurlencode($query);
                if (!empty($this->database)) {
                    $body .= '&db=' . rawurlencode($this->database);
                }
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
                $req = $this->doRequest($url, 'POST', $headers, $body, $queryParams, $this->user, $this->password);
            } else {
                $queryParams = array();
                if (!empty($this->database)) {
                    $queryParams['db'] = $this->database;
                }
                $queryParams['q'] = $query;
                $req = $this->doRequest($url, 'GET', $headers, '', $queryParams, $this->user, $this->password);
            }
        }

        if ($req['httpCode'] != 200) {
            $msg = 'TinyInflux query failed';
            if (!empty($req['errorMessage'])) {
                $msg .= ': ' . $req['errorMessage'];
            }
            $msg .= ' (HTTP ' . $req['httpCode'] . ')';
            if (!empty($req['response'])) {
                $msg .= '. Response: ' . $req['response'];
            }
            throw new Exception($msg);
        }

        if ($decodeJson) {
            $decoded = json_decode($req['response'], true);
            if (is_array($decoded)) {
                $result = $decoded;
            } else {
                $result = $req['response'];
            }
        } else {
            $result = $req['response'];
        }

        return ($result);
    }

    /**
     * Returns true if the query is DDL and should be sent via POST (InfluxDB v1)
     *
     * @param string $query
     *
     * @return bool
     */
    protected function isDdlQuery($query) {
        $result = false;
        $q = strtoupper(trim($query));
        $ddlPrefixes = array(
            'CREATE DATABASE',
            'DROP DATABASE',
            'CREATE RETENTION POLICY',
            'DROP RETENTION POLICY',
            'ALTER RETENTION POLICY',
            'CREATE USER',
            'DROP USER',
            'GRANT ',
            'REVOKE ',
            'CREATE CONTINUOUS QUERY',
            'DROP CONTINUOUS QUERY',
            'KILL '
        );
        foreach ($ddlPrefixes as $prefix) {
            if (strpos($q, $prefix) === 0) {
                $result = true;
                break;
            }
        }
        return ($result);
    }

    /**
     * Performs low-level HTTP request via cURL
     *
     * @param string $url
     * @param string $method
     * @param array $headers
     * @param string $body
     * @param array $queryParams
     * @param string $user
     * @param string $password
     *
     * @return array
     */
    protected function doRequest($url, $method, $headers, $body, $queryParams, $user = '', $password = '') {
        $result = array(
            'response' => '',
            'httpCode' => 0,
            'errorCode' => 0,
            'errorMessage' => ''
        );

        if (!extension_loaded('curl')) {
            $result['errorMessage'] = 'CURL extension not loaded';
        } else {
            $fullUrl = $url;
            if (is_array($queryParams) and !empty($queryParams)) {
                $queryString = '';
                foreach ($queryParams as $key => $value) {
                    if ($queryString === '') {
                        $queryString .= '?';
                    } else {
                        $queryString .= '&';
                    }
                    $queryString .= rawurlencode($key) . '=' . rawurlencode($value);
                }
                $fullUrl .= $queryString;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $fullUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            if (strtoupper($method) === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }

            if (!empty($user) or !empty($password)) {
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);
            }

            $response = curl_exec($ch);
            $errorCode = curl_errno($ch);
            $errorMessage = curl_error($ch);
            $info = curl_getinfo($ch);
            if (is_array($info) and isset($info['http_code'])) {
                $httpCode = $info['http_code'];
            } else {
                $httpCode = 0;
            }

            if (PHP_VERSION_ID < 80000) {
                curl_close($ch);
            }

            $result['response'] = $response;
            $result['httpCode'] = $httpCode;
            $result['errorCode'] = $errorCode;
            $result['errorMessage'] = $errorMessage;
        }

        return ($result);
    }

    /**
     * Builds InfluxDB line protocol string for single point
     *
     * @param string $measurement
     * @param array $fields
     * @param array $tags
     * @param mixed $timestamp
     *
     * @return string
     */
    protected function buildLineProtocol($measurement, $fields, $tags = array(), $timestamp = '') {
        $result = '';

        $measurement = trim($measurement);
        if (!empty($measurement) and is_array($fields) and !empty($fields)) {
            $escapedMeasurement = $this->escapeKey($measurement);

            $tagsPart = '';
            if (is_array($tags) and !empty($tags)) {
                foreach ($tags as $tagKey => $tagValue) {
                    if ($tagValue === '' or $tagValue === null) {
                        continue;
                    }
                    $tagsPart .= ',' . $this->escapeKey($tagKey) . '=' . $this->escapeTagValue($tagValue);
                }
            }

            $fieldsPart = '';
            $firstField = true;
            foreach ($fields as $fieldKey => $fieldValue) {
                if ($fieldValue === null) {
                    continue;
                }
                if ($firstField) {
                    $firstField = false;
                } else {
                    $fieldsPart .= ',';
                }
                $fieldsPart .= $this->escapeKey($fieldKey) . '=' . $this->formatFieldValue($fieldValue);
            }

            if (!empty($fieldsPart)) {
                $result = $escapedMeasurement . $tagsPart . ' ' . $fieldsPart;
                if ($timestamp !== '' and $timestamp !== null) {
                    $ts = $this->normalizeTimestamp($timestamp);
                    if ($ts !== '') {
                        $result .= ' ' . $ts;
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Converts timestamp to InfluxDB line protocol format (integer in configured precision).
     * Accepts: Unix seconds (int), date string (e.g. Y-m-d H:i:s), or already precision integer string.
     *
     * @param mixed $timestamp
     *
     * @return string
     */
    protected function normalizeTimestamp($timestamp) {
        $result = '';
        $precision = strtolower($this->precision);
        if ($precision === '') {
            $precision = 'ns';
        }

        $unixSeconds = null;
        if (is_int($timestamp)) {
            $unixSeconds = $timestamp;
        } else {
            if (is_numeric($timestamp)) {
                $num = floatval($timestamp);
                if ($num > 1e15) {
                    $result = (string)(int)$num;
                } else {
                    if ($num <= 2147483647 and $num >= -2147483648) {
                        $unixSeconds = (int)$num;
                    }
                }
            }
            if ($unixSeconds === null and $result === '') {
                $parsed = strtotime($timestamp);
                if ($parsed !== false) {
                    $unixSeconds = $parsed;
                }
            }
        }

        if ($unixSeconds !== null) {
            if ($precision === 's') {
                $result = (string)$unixSeconds;
            } else {
                if ($precision === 'ms') {
                    $result = (string)($unixSeconds * 1000);
                } else {
                    if ($precision === 'u') {
                        $result = (string)($unixSeconds * 1000000);
                    } else {
                        $result = (string)($unixSeconds * 1000000000);
                    }
                }
            }
        }

        return ($result);
    }

    /**
     * Escapes measurement, tag key or field key
     *
     * @param string $key
     *
     * @return string
     */
    protected function escapeKey($key) {
        $result = '';
        $key = (string)$key;
        if ($key !== '') {
            $result = str_replace(
                array(' ', ',', '='),
                array('\\ ', '\\,', '\\='),
                $key
            );
        }
        return ($result);
    }

    /**
     * Escapes tag value
     *
     * @param string $value
     *
     * @return string
     */
    protected function escapeTagValue($value) {
        $result = '';
        $value = (string)$value;
        if ($value !== '') {
            $result = str_replace(
                array(' ', ',', '='),
                array('\\ ', '\\,', '\\='),
                $value
            );
        }
        return ($result);
    }

    /**
     * Formats field value according to InfluxDB line protocol rules
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function formatFieldValue($value) {
        $result = '';
        if (is_bool($value)) {
            if ($value) {
                $result = 'true';
            } else {
                $result = 'false';
            }
        } else {
            if (is_int($value)) {
                $result = $value . 'i';
            } else {
                if (is_float($value)) {
                    $result = sprintf('%F', $value);
                    $result = rtrim(rtrim($result, '0'), '.');
                } else {
                    $stringValue = (string)$value;
                    $stringValue = str_replace('"', '\\"', $stringValue);
                    $result = '"' . $stringValue . '"';
                }
            }
        }
        return ($result);
    }
}