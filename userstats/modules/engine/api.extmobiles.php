<?php

/**
 * Light version for userstats
 */
class UserstatsMobilesExt {

    /**
     * Contains all additiona mobile numbers as id=>data
     *
     * @var array
     */
    protected $allMobiles = array();

    /**
     * Contains current instance user login
     *
     * @var string
     */
    protected $login = '';

    /**
     * Creates new UserstatsMobilesExt instance
     * 
     * 
     * @return void
     */
    public function __construct($login) {
        $this->setLogin($login);
        $this->loadAllUserMobiles();
    }

    /**
     * Sets current instance user login
     * 
     * @return void
     */
    protected function setLogin($login) {
        $this->login = $login;
    }

    /**
     * Loads all additional mobiles data from database for some user
     * 
     * @return void
     */
    protected function loadAllUserMobiles() {
        $userLoginClean = mysql_real_escape_string($this->login);
        if (!empty($userLoginClean)) {
            $query = "SELECT * from `mobileext` WHERE `login`='" . $userLoginClean . "';";
            $all = simple_queryall($query);
            if (!empty($all)) {
                foreach ($all as $io => $each) {
                    $this->allMobiles[$each['id']] = $each;
                }
            }
        }
    }

    /**
     * Returns filtered array for some user phones as id=>data
     * 
     * @param string $login
     * 
     * @return array
     */
    public function getUserMobiles() {
        $result = array();
        if (!empty($this->allMobiles)) {
            foreach ($this->allMobiles as $io => $each) {
                $result[$each['id']] = $each;
            }
        }
        return ($result);
    }

    /**
     * Renders additional user mobile numbers
     * 
     * @return string
     */
    public function renderUserMobiles() {
        $result = '';
        $allExtRaw = $this->getUserMobiles();
        $allExt = array();
        if (!empty($allExtRaw)) {
            foreach ($allExtRaw as $io => $each) {
                $allExt[] = $each['mobile'];
            }
        }
        /**
         * Emptiness is power, loneliness is pain
         * Serenity is might
         * Yet we shall be honoured
         * In the starforsaken night

         * Astral strain
         * All around my silent moon
         * Life suffers defeat
         */
        if (!empty($allExt)) {
            $additionalNumbers = implode(', ', $allExt);
        } else {
            $additionalNumbers = '';
        }

        $result.= $additionalNumbers;
        return ($result);
    }

}
