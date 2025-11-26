<?php

/**
 * Mapon cars GPS location service low-level api
 */
class MaponAPI {

    /**
     * @var string Your API key
     */
    public $apiKey;

    /**
     * @var string API URL, with trailing slash
     */
    public $apiUrl;
    public $libVersion = 1.0;
    public $debug = false;

    /**
     * @param string $apiKey Your API key
     * @param string $apiUrl Your API url, with trailing slash
     */
    public function __construct($apiKey, $apiUrl) {
        $this->apiKey = $apiKey;
        $this->apiUrl = rtrim($apiUrl, '/') . '/';
    }

    /**
     * @param string $requestMethod
     * @param string $apiMethod
     * @param array $getData
     * @param array $postData
     * @return stdClass
     * @throws ApiException
     */
    public function callMethod($requestMethod, $apiMethod, $getData = array(), $postData = array()) {
        if (!function_exists('json_decode')) {
            throw new ApiException('Please enable php json extension');
        }

        $getData += array(
            'key' => $this->apiKey,
            '_phplibv' => $this->libVersion,
        );

        $url = $this->apiUrl . $apiMethod . '.json';

        $res = $this->getUrlContent($url, $requestMethod, $getData, $postData);

        $json = json_decode($res);

        if (is_null($json) || (!isset($json->data) && !isset($json->error))) {
            if ($this->debug) {
                echo "Response from API:\n" . $res . "\n";
            }
            throw new ApiException('Error while parsing API response');
        }

        if (isset($json->error)) {
            throw new ApiException($json->error->msg, $json->error->code);
        }

        return $json;
    }

    /**
     * @param string $urlDomain Domain + path WITHOUT query part
     * @param string $requestMethod get or post
     * @param array $queryData
     * @param array $postData
     * @return mixed
     * @throws ApiException
     */
    private function getUrlContent($urlDomain, $requestMethod, array $queryData = array(), array $postData = array()) {
        $isPost = $requestMethod == 'post';
        $urlDomain .= '?' . http_build_query($queryData);
        $apiHandler=new OmaeUrl($urlDomain);
  
        if ($isPost) {
            if (!empty($postData)) {
                foreach ($postData as $key => $value) {
                    $apiHandler->dataPost($key, $value);
                }
            }
        }
        
        $output = $apiHandler->response();
        $error = $apiHandler->error();

        if (!$output) {
            throw new ApiException('Could not HTTP request: ' . var_export($error, true));
        }

        return $output;
    }

    /**
     * Call GET API request
     *
     * @param string $action Class and action, for example, unit/list
     * @param array $queryData Data which will be passed as GET parameters
     * @return stdClass
     * @throws ApiException
     */
    public function get($action, $queryData = array()) {
        return $this->callMethod('get', $action, $queryData);
    }

    /**
     * Call POST API request
     *
     * @param $action Class and action, for example, object/save
     * @param array $postData Data which will be passed as POST data
     * @return stdClass
     * @throws ApiException
     */
    public function post($action, $postData = array()) {
        return $this->callMethod('post', $action, array(), $postData);
    }

    /**
     * Decodes encoded route polyline and returns lat/lng array.
     * If $speed string is given, then return array will also contain velocity for each coordinate.
     * If $start_time is given then each coordinate will also contain time.
     * All this information can be obtained by actions which return route data.
     *
     * Based on:
     * http://code.google.com/apis/maps/documentation/polylinealgorithm.html
     *
     * @param string $encoded Encoded route
     * @param string $speed Encoded speed
     * @param integer $startTime Route start time in unix timestamp
     * @return array
     */
    public function decodePolyline($encoded, $speed = null, $startTime = null) {
        if (!is_null($speed)) {
            $speed = $this->decodeSpeed($speed);
        }

        $length = strlen($encoded);
        $index = 0;
        $points = array();
        $lat = 0;
        $lng = 0;

        while ($index < $length) {

            // Temporary variable to hold each ASCII byte.
            $b = 0;

            // The encoded polyline consists of a latitude value followed by a
            // longitude value.  They should always come in pairs.  Read the
            // latitude value first.
            $shift = 0;
            $result = 0;
            do {
                // The `ord(substr($encoded, $index++))` statement returns the ASCII
                //  code for the character at $index.  Subtract 63 to get the original
                // value. (63 was added to ensure proper ASCII characters are displayed
                // in the encoded polyline string, which is `human` readable)
                $b = ord(substr($encoded, $index++)) - 63;

                // AND the bits of the byte with 0x1f to get the original 5-bit `chunk.
                // Then left shift the bits by the required amount, which increases
                // by 5 bits each time.
                // OR the value into $results, which sums up the individual 5-bit chunks
                // into the original value.  Since the 5-bit chunks were reversed in
                // order during encoding, reading them in this way ensures proper
                // summation.
                $result |= ($b & 0x1f) << $shift;
                $shift += 5;
            }
            // Continue while the read byte is >= 0x20 since the last `chunk`
            // was not OR'd with 0x20 during the conversion process. (Signals the end)
            while ($b >= 0x20);

            // Check if negative, and convert. (All negative values have the last bit
            // set)
            $dlat = (($result & 1) ? ~($result >> 1) : ($result >> 1));

            // Compute actual latitude since value is offset from previous value.
            $lat += $dlat;

            // The next values will correspond to the longitude for this point.
            $shift = 0;
            $result = 0;
            do {
                $b = ord(substr($encoded, $index++)) - 63;
                $result |= ($b & 0x1f) << $shift;
                $shift += 5;
            } while ($b >= 0x20);

            $dlng = (($result & 1) ? ~($result >> 1) : ($result >> 1));
            $lng += $dlng;

            // The actual latitude and longitude values were multiplied by
            // 1e5 before encoding so that they could be converted to a 32-bit
            // integer representation. (With a decimal accuracy of 5 places)
            // Convert back to original values.

            $points[] = array(
                'lat' => $lat * 1e-5,
                'lng' => $lng * 1e-5
            );
        }

        if (!is_null($speed)) {
            foreach ($speed as $k => $v) {
                $points[$k]['speed'] = $v[1];
                if (!is_null($startTime)) {
                    $startTime += $v[0];
                    $points[$k]['time'] = $startTime;
                }
            }
        }

        return $points;
    }

    /**
     * decodes time offsets and speeds
     * @param string $str
     * @return array
     */
    public function decodeSpeed($str) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-.';

        $points = strlen($str) / 4;

        $data = array();

        for ($i = 0; $i < $points; $i++) {

            $pos = $i * 4;

            $offset = strpos($chars, $str[$pos]) * 64;
            $offset += strpos($chars, $str[$pos + 1]);

            $speed = strpos($chars, $str[$pos + 2]) * 64;
            $speed += strpos($chars, $str[$pos + 3]);

            $data[] = array($offset, $speed);
        }

        return $data;
    }

}

/**
 * Nothing to see here
 */
class ApiException extends \Exception {
    
}
