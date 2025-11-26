<?php
/**
 * Mikrotik API implementation
 */
class RouterOS {
    // Socket resource:
    private $socket;
    private $port;
    
    // Public variables:
    public $connected = false;
    public $debug_str = null;
    public $error_str = null;
    public $error_num = null;
    
    // Constatns of class:
    //const PORT     = 8728;
    const DEBUG    = false;
    const ATTEMPTS = 3;
    const TIMEOUT  = 5;
    const DELAY    = 0;
    
    /**
     * Fills in the `$this->debug_str` variable if self::DEBUG is enabled
     * 
     * @param   string  $string Debug message string
     * @return  boolean         
     */
    private function debug($string) {
        if ( self::DEBUG ) {
            $this->debug_str .= $string . '<br />' . PHP_EOL;
        }
    }

    /**
     * Establishes connection with RouterOS device via API
     * 
     * @param   string  $hostname   Device`s IP or hostname
     * @param   string  $username   Username
     * @param   string  $password   Password
     * @param   bool    $UseNewConnMode
     * @param   string  $apiPort
     *
     * @return  boolean $this->connected
     */
    public function connect($hostname, $username, $password, $UseNewConnMode = false, $apiPort = '8728') {
        // Connect to device:
        for ( $attempt = 1; $attempt <= self::ATTEMPTS; $attempt++ ) {
            $this->connected = false;
            $this->port = $apiPort;
            $this->debug('Connection attempt #' . $attempt . ' to ' . $hostname . ':' . $this->port);
            $this->socket = @fsockopen($hostname, $this->port, $this->error_num, $this->error_str, self::TIMEOUT);

            if ( $this->socket ) {
                socket_set_timeout($this->socket, self::TIMEOUT);

                if ($UseNewConnMode) {
                    $this->write('/login', false);
                    $this->write('=name=' . $username, false);
                    $this->write('=password=' . $password);

                    $response = $this->read(false);
                    if (isset($response[0]) && $response[0] == '!done') {
                        $this->connected = true;
                        break;
                    }
                } else {
                    $this->write('/login');
                    $response = $this->read(false);
                    if (isset($response[0]) && $response[0] == '!done') {
                        if (preg_match_all('/[^=]+/i', $response[1], $matches)) {
                            if ($matches[0][0] == 'ret' && strlen($matches[0][1]) == 32) {
                                $this->write('/login', false);
                                $this->write('=name=' . $username, false);
                                $this->write('=response=00' . md5(chr(0) . $password . pack('H*', $matches[0][1])));
                                $response = $this->read(false);
                                if (isset($response[0]) && $response[0] == '!done') {
                                    $this->connected = true;
                                    break;
                                }
                            }
                        }
                    }
                }
                fclose($this->socket);
            }
            sleep(self::DELAY);
        }
        
        // Throw debug-message:
        if ( $this->connected ) {
            $this->debug('Connection with device is established...');
        } else {
            $this->debug('Couldn`t establish connection with device!');
        }
        
        // Return connection state:
        return $this->connected;
    }
    
    /**
     * Encodes length
     * 
     * @param   string  $length Length to encode
     * @return  string
     */
    private function encode_length($length) {
        switch ( true ) {
            case ( $length < 0x80 ):
                $length = chr($length);
                break;
            case ( $length < 0x4000 ):
                $length |= 0x8000;
                $length = chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
                break;
            case ( $length < 0x200000 ):
                $length |= 0xC00000;
                $length = chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
                break;
            case ( $length < 0x10000000 ):
                $length |= 0xE0000000;
                $length = chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
                break;
            case ( $length >= 0x10000000 ):
                $length = chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
                break;
        }
        return $length;
    }
    
    /**
     * Parses RouterOS device`s reply to 'comfortable' array
     * 
     * @param   array   $response   RouterOS device`s reply
     * @return  array
     */
    private function parse_response($response) {
        $return = array();
        if ( is_array($response) ) {
            $current = null;
            $single = null;
            foreach ( $response as $key ) {
                if ( in_array($key, array('!fatal', '!re', '!trap')) ) {
                    if ( $key == '!re' ) {
                        $current = & $return[];
                    } else $current = & $return[$key][];
                } else if ( $key != '!done' ) {
                    if ( preg_match_all('/[^=]+/i', $key, $matches) ) {
                        if ( $matches[0][0] == 'ret' ) {
                            $single = $matches[0][1];
                        }
                        $current[$matches[0][0]] = ( isset($matches[0][1] ) ? $matches[0][1] : null);
                    }
                }
            }
            if ( empty($return) && !is_null($single) ) {
                $return = $single;
            }
        }
        return $return;
    }
    
    /**
     * Reads data from RouterOS device via API
     * 
     * @param   boolean $parse_response
     * @return  array   
     */
    public function read($parse_response = true) {
        $response = array();
        $_ = '';
        $_done = false;

        while ( true ) {
            $byte = ord(fread($this->socket, 1));
            $length = 0;
            if ($byte & 128) {
                if (($byte & 192) == 128) {
                    $length = (($byte & 63) << 8) + ord(fread($this->socket, 1));
                } else {
                    if (($byte & 224) == 192) {
                        $length = (($byte & 31) << 8) + ord(fread($this->socket, 1));
                        $length = ($length << 8) + ord(fread($this->socket, 1));
                    } else {
                        if (($byte & 240) == 224) {
                            $length = (($byte & 15) << 8) + ord(fread($this->socket, 1));
                            $length = ($length << 8) + ord(fread($this->socket, 1));
                            $length = ($length << 8) + ord(fread($this->socket, 1));
                        } else {
                            $length = ord(fread($this->socket, 1));
                            $length = ($length << 8) + ord(fread($this->socket, 1));
                            $length = ($length << 8) + ord(fread($this->socket, 1));
                            $length = ($length << 8) + ord(fread($this->socket, 1));
                        }
                    }
                }
            } else $length = $byte;

            if ($length > 0) {
                $retlen = 0;
                $_ = '';

                while ($retlen < $length) {
                    $toread = $length - $retlen;
                    $_ .= fread($this->socket, $toread);
                    $retlen = strlen($_);
                }

                $response[] = $_;
                $this->debug('>>> [' . $retlen . '/' . $length . '] bytes read.');
            }

            if ($_ == '!done') { $_done = true; }

            $status = socket_get_status($this->socket);

            if ($length > 0) {$this->debug('>>> [' . $length . ', ' . $status['unread_bytes'] . ']' . $_);}

            if ( (!$this->connected && !$status['unread_bytes']) || ($this->connected && !$status['unread_bytes'] && $_done) ) {
                break;
            }
        }

        return ( $parse_response ) ? $this->parse_response($response) : $response;
    }

    /**
     * Writes command to RouterOS device via API
     * 
     * @param   string  $command    Sending command's string
     * @param   boolean $param      ...
     * @return  boolean
     */
    public function write($command, $param = true) {
        if ( $command ) {
            $data = explode('\n', $command);

            foreach ( $data as $cmd ) {
                $cmd = trim($cmd);
                fwrite($this->socket, $this->encode_length(strlen($cmd)) . $cmd);
                $this->debug('<<< [' . strlen($cmd) . '] ' . $cmd);
            }

            $type = gettype($param);
            switch ( $type)  {
                case 'integer':
                    fwrite($this->socket, $this->encode_length(strlen('.tag=' . $param)) . '.tag=' . $param . chr(0));
                    $this->debug('<<< [' . strlen('.tag=' . $param) . '] .tag=' . $param);
                    break;

                case 'boolean':
                    fwrite($this->socket, ($param ? chr(0) : ''));
                    break;
            }
        }
        return ( $command ) ? true : false;
    }

    /**
     * Use it for sending commands to RouterOS device
     * 
     * @param   string  $string RouterOS`s command string
     * @param   array   $data   Command`s parameters
     * @return  array
     */
    public function command($string, $data = array()) {
        $count = count($data);
        $this->write($string, !$data);
        $i = 0;
        foreach ( $data as $key => $value ) {
            switch ( $key[0] ) {
                case '?':
                    $el = ($key[1] == '#') ? $key . $value : $key . '=' . $value;
                    break;
                case '~':
                    $el = $key . '~' . $value;
                    break;
                default:
                    $el = '=' . $key . '=' . $value;
                    break;
            }

            $last = ($i++ == $count - 1);
            $this->write($el, $last);
        }
        return $this->read();
    }

    /**
     * Closes up established connection with RouterOS device
     */
    public function disconnect() {
        $this->debug('Closing up established connection with RouterOS device..');
        $this->connected = ( !fclose($this->socket) ) ? true : false;
        return !$this->connected;
    }

    /**
     * Tries to get RouterOS version via SNMP or from login WEB page
     *
     * @param $Hostname
     * @param int $WEBPort
     * @param string $SNMPCommunity
     * @return float
     */
    public function determineVersion($Hostname, $WEBPort = 80, $SNMPCommunity = 'public') {
        $Version = '';

        // trying to get version via SNMP
        $SNMP = new SNMPHelper();
        $SNMP->setMode('native');
        $OID = '.1.3.6.1.4.1.14988.1.1.4.4.0';
        $tmpSNMP = $SNMP->walk($Hostname, $SNMPCommunity, $OID, false);
        $Version = ( empty($tmpSNMP) || $tmpSNMP === "$OID = " ) ? '' : str_replace('"', '', trim( substr($tmpSNMP, stripos($tmpSNMP, ':') + 1) ));

        // if first option failed - trying to parse login WEB page
        if ( empty($Version) ) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $Hostname);
            curl_setopt($curl, CURLOPT_PORT, $WEBPort);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $Result = curl_exec($curl);
            //PHP 8.0+ has no need to close curl resource anymore
            if (PHP_VERSION_ID < 80000) {
                curl_close($curl); // Deprecated in PHP 8.5
            }

            if ( !empty($Result) ) {
                preg_match('/RouterOS v(.*?)</', $Result, $Match);

                if ( isset($Match[1]) && !empty($Match[1]) ) { $Version = $Match[1]; }
            }
        }

        return floatval($Version);
    }
}