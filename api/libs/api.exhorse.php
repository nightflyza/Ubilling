<?php

class ExistentialHorse {

    protected $altCfg = array();
    protected $allInetUsers = array();
    protected $storeTmp = array();
    protected $complexFlag = false;
    protected $complexMasks = array();

    public function __construct() {
        $this->loadConfig();
        $this->loadUserData();
        $this->initTmp();
    }

    /**
     * Preloads alter config, for further usage as key=>value
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        //loading complex tariffs config
        if ($this->altCfg['COMPLEX_ENABLED']) {
            $this->complexFlag = true;

            if (!empty($this->altCfg['COMPLEX_MASKS'])) {
                $masksRaw = explode(",", $this->altCfg['COMPLEX_MASKS']);
                if (!empty($masksRaw)) {
                    foreach ($masksRaw as $eachmask) {
                        $this->complexMasks[] = trim($eachmask);
                    }
                }
            } else {
                throw new Exception(self::CPL_EMPTY_EX);
            }
        }
    }

    /**
     * Inits empty temporary array with default struct.
     * 
     * @return void
     */
    protected function initTmp() {
        $this->storeTmp['u_totalusers'] = 0;
        $this->storeTmp['u_activeusers'] = 0;
        $this->storeTmp['u_inactiveusers'] = 0;
        $this->storeTmp['u_frozenusers'] = 0;
        $this->storeTmp['u_complextotal'] = 0;
        $this->storeTmp['u_complexactive'] = 0;
        $this->storeTmp['u_complexinactive'] = 0;
        $this->storeTmp['u_signups'] = 0;
        $this->storeTmp['u_citysignups'] = '';
    }

    /**
     * Loads all of available inet users into protected property
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allInetUsers = zb_UserGetAllStargazerDataAssoc();
    }

    /**
     * Detects user activity state
     * 
     * @param array $userData
     * 
     * @return int 1 - active, 0 - inactive, -1 - frozen
     */
    protected function isActive($userData) {
        $result = '';
        if (($userData['Cash'] >= $userData['Credit']) AND ( $userData['AlwaysOnline'] == 1) AND ( $userData['Passive'] == 0)) {
            $result = 1;
        }
        if (($userData['Cash'] <= $userData['Credit']) AND ( $userData['AlwaysOnline'] == 1) AND ( $userData['Passive'] == 0)) {
            $result = 0;
        }
        if ($userData['Passive'] == 1) {
            $result = -1;
        }
        return ($result);
    }

    /**
     * Detect is user complex or not?
     * 
     * @param array $userArray
     * 
     * @return bool
     */
    protected function isComplex($userArray) {
        $result = false;
        if ($this->complexFlag) {
            if (!empty($this->complexMasks)) {
                foreach ($this->complexMasks as $io => $eachMask) {
                    if (ispos($userArray['Tariff'], $eachMask)) {
                        $result = true;
                        break;
                    }
                }
            }
        }
        return ($result);
    }

    public function preprocessUserData() {
        if (!empty($this->allInetUsers)) {
            foreach ($this->allInetUsers as $io => $eachUser) {
                //active users
                if ($this->isActive($eachUser) == 1) {
                    $this->storeTmp['u_activeusers'] ++;
                }
                //inactive users
                if ($this->isActive($eachUser) == 0) {
                    $this->storeTmp['u_inactiveusers'] ++;
                }
                //just frozen bodies
                if ($this->isActive($eachUser) == -1) {
                    $this->storeTmp['u_frozenusers'] ++;
                }

                //complex users detection
                if ($this->isComplex($eachUser)) {
                    $this->storeTmp['u_complextotal'] ++;
                }

                //total users count
                $this->storeTmp['u_totalusers'] ++;
            }
        }

        debarr($this->storeTmp);
    }

}
