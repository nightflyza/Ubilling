<?php

/**
 * Basic Ubilling UserStats configs abstraction class
 */
class UserStatsConfig {
    /**
     * Stores UserStats main config "userstats.ini"
     *
     * @var array
     */
    protected $ustasCfg = array();

    /**
     * Stores OPENPAYZ config "opayz.ini"
     *
     * @var array
     */
    protected $opayzCfg = array();

    const US_CONFIG_PATH = 'config/userstats.ini';
    const OPAYZ_CONFIG_PATH = 'config/opayz.ini';

    public function __construct() {
        $this->loadUstas();
        $this->loadOpayzCfg();
    }

    /**
     * Loads UserStats main config "userstats.ini" to private ustasCfg prop
     *
     * @return void
     */
    protected function loadUstas() {
        $this->ustasCfg = parse_ini_file(self::US_CONFIG_PATH);
    }

    /**
     * Getter for private ustatsCfg prop
     *
     * @return array
     */
    public function getUstas() {
        return ($this->ustasCfg);
    }

    /**
     * Parameter getter for ustasCfg
     * Returns $parameter from "userstats.ini" or FALSE if parameter not defined
     * May return $retValIfParamEmptyOrNotExists value, instead of searched $parameter,
     * if searched $parameter is FALSE (but NOT NULL) or not defined
     *
     * @param mixed $param
     * @param mixed $retValIfParamEmptyOrNotExists
     *
     * @return mixed
     */
    public function getUstasParam($param = false, $retValIfParamEmptyOrNotExists = null) {
        $alterParam = ($param and isset($this->ustasCfg[$param])) ? $this->ustasCfg[$param] : false;

        if ($alterParam === false and !is_null($retValIfParamEmptyOrNotExists)) {
            $alterParam = $retValIfParamEmptyOrNotExists;
        }

        return ($alterParam);
    }

    /**
     * Loads UserStats OPENPAYZ config "opayz.ini" to private opayzCfg prop
     *
     * @return void
     */
    protected function loadOpayzCfg() {
        $this->opayzCfg = parse_ini_file(self::OPAYZ_CONFIG_PATH);
    }

    /**
     * Getter for private opayzCfg prop
     *
     * @return array
     */
    public function getOpayzCfg() {
        return ($this->opayzCfg);
    }
}