<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if (@$us_config['VISOR_ENABLED']) {

    class ZBSVisorInterface {

        protected $myLogin = '';

        public function __construct($login) {
            $this->setLogin($login);
        }

        public function setLogin($login) {
            $this->myLogin = mysql_real_escape_string($login);
        }

    }

    $visor = new ZBSVisorInterface($user_login);
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}