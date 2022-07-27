<?php

/**
 * You're Goddamn Right
 */
class SayMyName {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Login generation mode mapped from LOGIN_GENERATION option
     *
     * @var string
     */
    protected $generationMode = '';

    /**
     * User city alias
     *
     * @var string
     */
    protected $cityAlias = '';

    /**
     * User street alias
     *
     * @var string
     */
    protected $streetAlias = '';

    /**
     * User build number
     *
     * @var string
     */
    protected $buildNum = '';

    /**
     * User apartament number
     *
     * @var string
     */
    protected $apt = '';

    /**
     * User IP
     *
     * @var string
     */
    protected $ipProposal = '';

    /**
     * User associated agent Id
     *
     * @var int
     */
    protected $agentId = 0;

    /**
     * Contains increment-like logins start offset. 
     * Mapped from LOGIN_GENERATION_INCOFFSET option.
     *
     * @var int
     */
    protected $incrementsOffset = 1;

    /**
     * Contains default increments maximum value. 
     * Mapped from LOGIN_GENERATION_INCMAX option.
     *
     * @var int
     */
    protected $incrementsMaxLimit = 100000;

    /**
     * Contains default apartments number delimiter for address based logins. 
     * Mapped from LOGIN_GENERATION_AD option.
     *
     * @var string
     */
    protected $apartmentDelimiter = 'ap';

    /**
     * Contains shared prefix for further usage in some generators.
     * Mapped from LOGIN_GENERATION_SHPRFX option.
     *
     * @var string
     */
    protected $sharedPrefix = 'UB';

    /**
     * Contains all busy users logins
     *
     * @var array
     */
    protected $busyLogins = array();

    /**
     * New user login proposal
     *
     * @var string
     */
    protected $loginProposal = '';

    /**
     * Some predefined constants, options, routes etc..
     */
    const OPTION_MODE = 'LOGIN_GENERATION';
    const OPTION_INCOFFSET = 'LOGIN_GENERATION_INCOFFSET';
    const OPTION_INCMAX = 'LOGIN_GENERATION_INCMAX';
    const OPTION_APT_DELIMITER = 'LOGIN_GENERATION_AD';
    const OPTION_SHARED_PREFIX = 'LOGIN_GENERATION_SHPRFX';
    const GENERATORS_PATH = 'api/vendor/login_generators/';

    /**
     * Creates new login generator instance
     * 
     * @param string $cityAlias
     * @param string $streetAlias
     * @param string $buildNum
     * @param string $apt
     * @param string $ipProposal
     * @param int $agentid
     * 
     */
    public function __construct($cityAlias, $streetAlias, $buildNum, $apt, $ipProposal, $agentId = 0) {
        $this->loadConfig();
        if (isset($this->altCfg[self::OPTION_MODE])) {
            //setting generation mode
            $this->generationMode = $this->altCfg[self::OPTION_MODE];
            //optional custom increments offset
            if (isset($this->altCfg[self::OPTION_INCOFFSET])) {
                $this->incrementsOffset = $this->altCfg[self::OPTION_INCOFFSET];
            }
            //and custom optional maximum increments values
            if (isset($this->altCfg[self::OPTION_INCMAX])) {
                $this->incrementsMaxLimit = $this->altCfg[self::OPTION_INCMAX];
            }
            //custom optional apartments delimiter
            if (isset($this->altCfg[self::OPTION_APT_DELIMITER])) {
                $this->apartmentDelimiter = $this->altCfg[self::OPTION_APT_DELIMITER];
            }
            //custom shared prefix for some generators
            if (isset($this->altCfg[self::OPTION_SHARED_PREFIX])) {
                $this->sharedPrefix = $this->altCfg[self::OPTION_SHARED_PREFIX];
            }

            //setting some of new user parameters
            $this->cityAlias = zb_TranslitString($cityAlias);
            $this->streetAlias = zb_TranslitString($streetAlias);
            $this->buildNum = zb_TranslitString($buildNum);
            $this->apt = zb_TranslitString($apt);
            $this->ipProposal = $ipProposal;
            $this->agentId = $agentId;
            //loading all busy logins
            $this->loadBusyLogins();

            //validation of increment custom offsets
            if ($this->incrementsOffset >= $this->incrementsMaxLimit) {
                die(self::OPTION_INCOFFSET . ' >= ' . self::OPTION_INCMAX);
            }
        } else {
            die(__('You missed an important option') . ' ' . self::OPTION_MODE . '!');
        }
    }

    /**
     * Loads some required configs data
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Loads currently existing users logind from database
     * 
     * @return void
     */
    protected function loadBusyLogins() {
        $this->busyLogins = zb_AllBusyLogins();
    }

    /**
     * Filters user login for only allowed symbols
     * 
     * @param string $login
     * 
     * @return string
     */
    protected function filterLogin($login) {
        $login = str_replace(' ', '_', $login); //no spaces, lol
        $result = preg_replace("#[^a-z0-9A-Z_]#Uis", '', $login); //alphanumeric
        $result = zb_TranslitString($result); //force translit
        return($result);
    }

    /**
     * Returns new user login proposal
     * 
     * @return string
     */
    public function getLogin() {
        $result = '';
        $generatorFullPath = self::GENERATORS_PATH . $this->generationMode;
        if (file_exists($generatorFullPath)) {
            $generatorCode = file_get_contents($generatorFullPath);
            if (!empty($generatorCode)) {
                eval($generatorCode);
                //any login suggestions?
                if (empty($this->loginProposal)) {
                    log_register('LOGIN_GENERATION `' . $this->generationMode . '` FAIL EMPTY_PROPOSAL');
                    throw new Exception('Generator code in ' . ': ' . $generatorFullPath . ' doesnt set loginProposal property or returns empty proposal');
                }
            } else {
                log_register('LOGIN_GENERATION `' . $this->generationMode . '` FAIL EMPTY_CODE');
                throw new Exception('Generator code is empty' . ': ' . $generatorFullPath);
            }
        } else {
            log_register('LOGIN_GENERATION `' . $this->generationMode . '` FAIL NOT_EXISTS');
            throw new Exception('Login generation definition not exists' . ': ' . $generatorFullPath);
        }
        $result = $this->filterLogin($this->loginProposal);
        if (isset($this->busyLogins[$result])) {
            log_register('LOGIN_GENERATION `' . $this->generationMode . '` FAIL DUPLICATE `' . $result . '`');
            throw new Exception('Generator ' . $generatorFullPath . ' returned existing login as proposal');
        }
        return($result);
    }

}
