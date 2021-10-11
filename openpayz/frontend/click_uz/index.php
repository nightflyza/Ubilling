<?php

/**
 * Click.UZ API frontend for OpenPayz
 *
 * https://docs.click.uz/click-api/
 */

// подключаем API OpenPayz
include ("../../libs/api.openpayz.php");

class ClickUZ {
    /**
     * Predefined stuff
     */
    const PATH_CONFIG       = 'config/clickuz.ini';
    const PATH_AGENTCODES   = 'config/agentcodes_mapping.ini';
    const PATH_TRANSACTS    = 'tmp/';

    /**
     * Paysys specific predefines
     */
    const HASH_PREFIX = 'CLICK_UZ_';
    const PAYSYS = 'CLICK_UZ';

    /**
     * Actions codes here
     */
    const ACT_INFO = 0;
    const ACT_PREPARE = 1;
    const ACT_CONFIRM = 2;

    /**
     * Agent codes using flag
     *
     * @var bool
     */
    protected $agentcodesON = false;

    /**
     * Non strict agent codes using flag
     *
     * @var bool
     */
    protected $agentcodesNonStrict = false;

    /**
     * Contains values from agentcodes_mapping.ini
     *
     * @var array
     */
    protected $agentcodesMapping = array();

    /**
     * Merchant secret key from ClickUZ
     *
     * @var string
     */
    protected $secretKey = '';

    /**
     * Placeholder for UB API URL
     *
     * @var string
     */
    protected $ubapiURL = '';

    /**
     * Placeholder for UB API key
     *
     * @var string
     */
    protected $ubapiKey = '';

    /**
     * Instance configuration as key=>value
     *
     * @var array
     */
    protected $config = array();

    /**
     * Preloads all required configuration, sets needed object properties
     *
     * @return void
     */
    public function __construct() {
        $this->loadConfig();
        $this->setOptions();
        $this->loadACMapping();
    }

    /**
     * Loads frontend configuration in protected prop
     *
     * @return void
     */
    protected function loadConfig() {
        if (file_exists(self::PATH_CONFIG)) {
            $this->config = parse_ini_file(self::PATH_CONFIG);
        } else {
            die('Fatal error: config file ' . self::PATH_CONFIG . ' not found!');
        }
    }

    /**
     * Loads frontend agentcodes_mapping.ini in protected prop
     *
     * @return void
     */
    protected function loadACMapping() {
        if ($this->agentcodesON) {
            if (file_exists(self::PATH_AGENTCODES)) {
                $this->agentcodesMapping = parse_ini_file(self::PATH_AGENTCODES);
            } else {
                die('Fatal error: agentcodes_mapping.ini file ' . self::PATH_AGENTCODES . ' not found!');
            }
        }
    }

    /**
     * Sets object properties based on frontend config
     *
     * @return void
     */
    protected function setOptions() {
        if (!empty($this->config)) {
            $this->agentcodesON         = $this->config['USE_AGENTCODES'];
            $this->agentcodesNonStrict  = $this->config['NON_STRICT_AGENTCODES'];
            $this->secretKey            = $this->config['SECRET_KEY'];
            $this->ubapiURL             = $this->config['UBAPI_URL'];
            $this->ubapiKey             = $this->config['UBAPI_KEY'];
        } else {
            die('Fatal: config is empty!');
        }
    }

    protected function createSign($actCode) {

    }

    protected function validateSign() {

    }

    /**
     * Saves transaction id to validate some possible duplicates
     *
     * @return void
     */
    protected function saveTransaction() {
//todo: change to fit specs
        if (!empty($this->receivedData)) {
            if (isset($this->receivedData['PAY_ID'])) {
                file_put_contents(self::PATH_TRANSACTS . $this->receivedData['PAY_ID'], serialize($this->receivedData));
            }
        }
    }


}