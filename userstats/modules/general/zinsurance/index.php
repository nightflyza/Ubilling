<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if (@$us_config['INSURANCE_ENABLED']) {

    class InsuranceFrontend {

        protected $homeDB = '';
        protected $myLogin = '';

        const HINS_TABLE = 'ins_homereq';
        const URL_ME = '?module=zinsurance';
        const ROUTE_HINSOK = 'hinssuccess';

        public function __construct() {
            $this->setLogin();
            $this->initDataLayers();
        }

        protected function setLogin() {
            global $user_login;
            $this->myLogin = $user_login;
        }

        protected function initDataLayers() {
            $this->homeDB = new NyanORM(self::HINS_TABLE);
        }

        public function renderHomeInsuranceReq() {
            $result = '';
            $inputs = la_HiddenInput('newhinsrequest', 'true');
            $inputs .= la_TextInput('newhinsaddress', __('Address'), '', true, 25);
            $inputs .= la_TextInput('newhinsrealname', __('Real Name'), '', true, 25);
            $inputs .= la_TextInput('newhinsmobile', __('Mobile'), '', true, 15, 'mobile');
            $inputs .= la_TextInput('newhinsremail', __('Email'), '', true, 15, 'email');
            $inputs .= la_Submit(__('Insure now'));
            $result .= la_Form('', 'POST', $inputs, 'glamour');
            return($result);
        }

        protected function filterInputData($data) {
            $result = ubRouting::filters($data, 'mres');
            $result = strip_tags($result);
            $result = trim($result);
            return($result);
        }

        public function catchHinsRequest() {
            $result = '';
            if (ubRouting::checkPost('newhinsrequest')) {
                if (ubRouting::checkPost(array('newhinsrequest', 'newhinsaddress', 'newhinsrealname', 'newhinsmobile', 'newhinsremail'))) {
                    $newAddress = $this->filterInputData(ubRouting::post('newhinsaddress'));
                    $newRealName = $this->filterInputData(ubRouting::post('newhinsrealname'));
                    $newMobile = $this->filterInputData(ubRouting::post('newhinsmobile'));
                    $newEmail = $this->filterInputData(ubRouting::post('newhinsremail'));

                    if (!empty($newAddress) AND ! empty($newRealName) AND ! empty($newEmail) AND ! empty($newMobile)) {
                        if (!empty($this->myLogin)) {
                            $this->homeDB->data('date', curdatetime());
                            $this->homeDB->data('login', $this->myLogin);
                            $this->homeDB->data('address', $newAddress);
                            $this->homeDB->data('realname', $newRealName);
                            $this->homeDB->data('mobile', $newMobile);
                            $this->homeDB->data('email', $newEmail);
                            $this->homeDB->data('state', '0');
                            $this->homeDB->create();
                        } else {
                            $result .= __('Error') . ': EX_NO_USER_DETECTED';
                        }
                    } else {
                        $result .= __('All fields are mandatory').'!';
                    }
                } else {
                    $result .= __('All fields are mandatory');
                }
            }
            return($result);
        }

    }

    $insurance = new InsuranceFrontend();
    if (!ubRouting::checkGet($insurance::ROUTE_HINSOK)) {
        show_window(__('Home insurance'), $insurance->renderHomeInsuranceReq());
    } else {
        show_window(__('Success'), __('Thank you. Your request is awaiting processing. Expect your email policy soon.'));
    }

    if (ubRouting::checkPost('newhinsrequest')) {
        $hinsResult = $insurance->catchHinsRequest();
        if (empty($hinsResult)) {
            ubRouting::nav($insurance::URL_ME . '&' . $insurance::ROUTE_HINSOK . '=true');
        } else {
            show_window(__('Error'), $hinsResult);
        }
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}

