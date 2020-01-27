<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

if (@$us_config['VISOR_ENABLED']) {

    class ZBSVisorInterface {

        /**
         * Contains current instance user login
         *
         * @var string
         */
        protected $myLogin = '';

        /**
         * Contains associated visor user data
         *
         * @var array
         */
        protected $myUserData = array();

        /**
         * Contains associated cameras as id=>camera data
         *
         * @var array
         */
        protected $myCameras = array();

        /**
         * Contains preloaded userstats.ini as key=>value
         *
         * @var array
         */
        protected $userstatsCfg = array();

        /**
         * Contains all system users data as login=>userData
         *
         * @var array
         */
        protected $allUsers = array();

        /**
         * Contains all available address data as login=>address string
         *
         * @var array
         */
        protected $allAddress = array();

        /**
         * Some default tables names
         */
        const TABLE_USERS = 'visor_users';
        const TABLE_CAMS = 'visor_cams';
        const TABLE_DVRS = 'visor_dvrs';
        const TABLE_CHANS = 'visor_chans';

        public function __construct($login) {
            $this->loadConfigs();
            $this->setLogin($login);
            $this->loadAllUserData();
            $this->loadAllAddressData();
            $this->loadMyUserData();
            $this->loadMyCameras();
        }

        /**
         * Preloads userstats.ini into protected property for further usage.
         * 
         * @global array $us_config
         * 
         * @return void
         */
        protected function loadConfigs() {
            global $us_config;
            $this->userstatsCfg = $us_config;
        }

        /**
         * Protected login property setter
         * 
         * @param string $login
         * 
         * @return void
         */
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
         * Loads current instance assigned cameras
         * 
         * @return void
         */
        protected function loadMyCameras() {
            if (!empty($this->myUserData)) {
                if (isset($this->myUserData['id'])) {
                    $myVisorId = $this->myUserData['id'];
                    $query = "SELECT * from `" . self::TABLE_CAMS . "` WHERE `visorid`='" . $myVisorId . "';";
                    $all = simple_queryall($query);
                    if (!empty($all)) {
                        foreach ($all as $io => $each) {
                            $this->myCameras[$each['id']] = $each;
                        }
                    }
                }
            }
        }

        /**
         * Loads all of available users data into protected prop for further usage.
         * 
         * @return void
         */
        protected function loadAllUserData() {
            $this->allUsers = zbs_UserGetAllStargazerData();
        }

        /**
         * Returns all available payment IDs
         * 
         * @return array
         */
        protected function getAllPaymentIds() {
            $result = array();
            if ($this->userstatsCfg['OPENPAYZ_REALID']) {
                $query = "SELECT * from `op_customers`";
                $all = simple_queryall($query);
                if (!empty($all)) {
                    foreach ($all as $io => $each) {
                        $result[$each['realid']] = $each['virtualid'];
                    }
                }
            } else {
                if (!empty($this->allUsers)) {
                    foreach ($this->allUsers as $eachLogin => $eachUserData) {
                        $result[$eachLogin] = ip2int($eachUserData['IP']);
                    }
                }
            }

            return($result);
        }

        /**
         * Preloads available address data into protected prop
         * 
         * @return void
         */
        protected function loadAllAddressData() {
            $this->allAddress = zbs_AddressGetFulladdresslist();
        }

        /**
         * Gets channels preview as JSON from remote API call
         * 
         * @return string
         */
        protected function getMyChannelsPreview() {
            $result = '';
            if (@$this->userstatsCfg['API_URL'] AND @ $this->userstatsCfg['API_KEY']) {
                if (!empty($this->myUserData)) {
                    if (isset($this->myUserData['id'])) {
                        $myVisorId = $this->myUserData['id'];
                        $apiBase = $this->userstatsCfg['API_URL'] . '/?module=remoteapi&key=' . $this->userstatsCfg['API_KEY'];
                        $requestUrl = $apiBase . '&action=visorchans&userid=' . $myVisorId . '&param=preview';
                        @$channels = file_get_contents($requestUrl);
                        if (!empty($channels)) {
                            @$channels = json_decode($channels);
                            if (!empty($channels)) {
                                foreach ($channels as $index => $eachUrl) {
                                    if (!empty($eachUrl)) {
                                        $result .= la_tag('div', false, '', 'style="float:left; width:30%; margin:5px;"');
                                        $result .= la_img($eachUrl);
                                        $result .= la_tag('br');
                                        $result .= la_tag('br');
                                        $result .= la_tag(a, false, 'anreadbutton', 'href="' . $eachUrl . '" target="_BLANK"') . __('View') . la_tag('a', true);
                                        $result .= la_tag('div', true);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return($result);
        }

        /**
         * Returns count of channels assigned for current instance user
         * 
         * @return string
         */
        protected function getChansCount() {
            $result = 0;
            if (!empty($this->myUserData)) {
                if (isset($this->myUserData['id'])) {
                    $query = "SELECT COUNT(`id`) FROM `" . self::TABLE_CHANS . "` WHERE `visorid`='" . $this->myUserData['id'] . "'";
                    $count = simple_query($query);
                    $result = $count['COUNT(`id`)'];
                }
            }
            return($result);
        }

        /**
         * Renders basic user profile data
         * 
         * @return string
         */
        public function renderProfile() {
            $result = '';
            if (!empty($this->myLogin)) {
                if (!empty($this->myUserData)) {
                    if (!empty($this->myCameras)) {
                        $allPayIds = $this->getAllPaymentIds();
                        $allTariffsFee = zbs_TariffGetAllPrices();

                        if ($this->myUserData['chargecams']) {
                            $primaryAddress = (isset($this->allAddress[$this->myLogin])) ? $this->allAddress[$this->myLogin] : $this->myLogin;
                            $result .= __('Money for cameras will be charged from your primary account') . ' ' . la_Link('index.php', $primaryAddress) . ' ';
                            $result .= __('if no funds for further cameras functioning') . '. ';
                            $result .= __('Your primary account balance now is') . ' ' . $this->allUsers[$this->myLogin]['Cash'] . ' ';
                            $result .= $this->userstatsCfg['currency'] . '. ' . __('You can recharge it with following Payment ID') . ': ' . $allPayIds[$this->myLogin];
                            $result .= la_delimiter();
                        }
                        $result .= la_tag('h3') . __('Your cameras') . la_tag('h3', true);
                        $cells = la_TableCell(__('Address'));
                        $cells .= la_TableCell(__('Payment ID'));
                        $cells .= la_TableCell(__('Balance'));
                        $cells .= la_TableCell(__('Fee'));
                        $rows = la_TableRow($cells, 'row1');
                        foreach ($this->myCameras as $io => $eachCam) {
                            $cameraLogin = $eachCam['login'];
                            $cameraTariff = $this->allUsers[$cameraLogin]['Tariff'];
                            $cameraFee = (isset($allTariffsFee[$cameraTariff])) ? $allTariffsFee[$cameraTariff] : 0;

                            if (isset($this->allUsers[$cameraLogin])) {
                                $cells = la_TableCell(@$this->allAddress[$cameraLogin]);
                                $cells .= la_TableCell(@$allPayIds[$cameraLogin]);
                                $cells .= la_TableCell(@$this->allUsers[$cameraLogin]['Cash']);
                                $cells .= la_TableCell($cameraFee);
                                $rows .= la_TableRow($cells, 'row3');
                            }
                        }

                        $result .= la_TableBody($rows, '100%', 0, '');
                        //TODO: make preview as separate route
                        $myChansCount = $this->getChansCount();
                        //user have some channels assigned
                        if ($myChansCount > 0) {
                            $result .= $this->getMyChannelsPreview();
                        }
                    } else {
                        $result .= __('You have no cameras assigned for this user profile');
                    }
                } else {
                    $result .= __('Surveillance service is not enabled for you account');
                }
            } else {
                show_window(__('Sorry'), __('Something went wrong'));
            }
            return($result);
        }

    }

    $visor = new ZBSVisorInterface($user_login);
    show_window(__('Surveillance'), $visor->renderProfile());
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}