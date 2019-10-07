<?php

class UbillingRemoteDHCP {

    /**
     * Contains default object config as key=>value
     *
     * @var array
     */
    protected $config = array();

    /**
     * Contains remote billing URL
     *
     * @var string
     */
    protected $billingUrl = '';

    /**
     * Contains Ubilling serial for API access
     *
     * @var string
     */
    protected $apiKey = '';

    /**
     * Some predefined URLs and paths
     */
    const URL_API = '/?module=remoteapi&action=remotedhcp&key=';
    const EXPORT_PATH = '/multinet/';

    /**
     * Creates new remote dhcp instance
     */
    public function __construct() {
        $this->loadConfig();
        $this->generateConfigs();
    }

    /**
     * Loads object configuration and sets all required properties
     * 
     * @return void
     */
    protected function loadConfig() {
        $configPath = 'config.ini';
        $this->config = parse_ini_file($configPath);
        $this->billingUrl = $this->config['UBILLING_URL'];
        $this->apiKey = $this->config['UBILLING_SERIAL'];
    }

    /**
     * Returns remote DHCP configs data as array filename=>data
     * 
     * @return array
     */
    protected function getRawData() {
        $result = array();
        if (!empty($this->apiKey) AND ! empty($this->billingUrl)) {
            $requestUrl = $this->billingUrl . self::URL_API . $this->apiKey;
            @$requestResult = file_get_contents($requestUrl);
            if (!empty($requestResult)) {
                @$result = json_decode($requestResult, true);
            }
        }
        return($result);
    }

    /**
     * Regenerates all config files with remotely fetched content
     * 
     * @return void
     */
    protected function generateConfigs() {
        $rawData = $this->getRawData();
        if (is_array($rawData)) {
            if (!empty($rawData)) {
                foreach ($rawData as $eachConfigName => $eachConfigData) {
                    $fileName = dirname(__FILE__) . self::EXPORT_PATH . $eachConfigName;
                    file_put_contents($fileName, $eachConfigData['content']);
                }
                //yeah, we updated some configs and now we need server restart
                $this->restartDhcpd();
            } else {
                print('ERROR:EMPTY_CONFIGS_RECEIVED');
            }
        } else {
            print('ERROR:REMOTE_UBILLING_CONNECTION_FAILED');
        }
    }

    /**
     * Performs ISC-DHCP server restart
     * 
     * @return void
     */
    protected function restartDhcpd() {
        $rcPath = $this->config['RC_DHCPD'];
        $restartCommad = $rcPath . ' restart';
        shell_exec($restartCommad);
    }

}

$remoteDhcp = new UbillingRemoteDHCP();
