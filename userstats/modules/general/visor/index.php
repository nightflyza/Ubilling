<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if (@$us_config['VISOR_ENABLED']) {

    class ZBSVisorInterface {

        protected $myLogin = '';
        protected $myUserData = array();
        protected $myCameras = array();

        /**
         * Some default tables names
         */
        const TABLE_USERS = 'visor_users';
        const TABLE_CAMS = 'visor_cams';
        const TABLE_DVRS = 'visor_dvrs';

        public function __construct($login) {
            $this->setLogin($login);
            $this->loadMyUserData();
            $this->loadMyCameras();
        }

        public function setLogin($login) {
            $this->myLogin = mysql_real_escape_string($login);
        }

        /**
         * Loads current user assigned visor user data
         * 
         * @return void
         */
        protected function loadMyUserData() {
            if (!empty($this->myLogin)) {
                $query = "SELECT * from `" . self::TABLE_USERS . "` WHERE `primarylogin`='" . $this->myLogin . "';";
                $result = simple_query($query);
                if (!empty($result)) {
                    $this->myUserData = $result;
                }
            }
        }

        /**
         * 
         */
        protected function loadMyCameras() {
            if (!empty($this->myUserData)) {
                if (isset($this->myUserData['id'])) {
                    $myVisorId = $this->myUserData['id'];
                    $query = "SELECT * from `" . self::TABLE_CAMS . "` WHERE `visorid`='" . $myVisorId . "';";
                    $result = simple_queryall($query);
                    if (!empty($result)) {
                        $this->myCameras = $result;
                    }
                }
            }
        }

    }

    $visor = new ZBSVisorInterface($user_login);
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}