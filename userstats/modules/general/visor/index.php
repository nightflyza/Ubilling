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
         * Contains received channel container type
         *
         * @var string
         */
        protected $chanPreviewContainer = 'mjpeg';

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
            if ($this->userstatsCfg['OPENPAYZ_ENABLED']) {
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
            }

            return ($result);
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
         * Returns channel preview container/player based on stream type
         * 
         * @param string $streamUrl
         * @param string $width
         * @param bool $autoPlay
         * 
         * @return string
         */
        protected function renderChannelPlayer($streamUrl, $width, $autoPlay = false) {
            $result = '';
            // detect type based on URL
            $this->chanPreviewContainer = 'mjpeg';
            if (strpos($streamUrl, '/hls/') !== false) {
                $this->chanPreviewContainer = 'hls';
            }

            if (strpos($streamUrl, 'pseudostream') !== false) {
                $this->chanPreviewContainer = 'hls';
            }

            if ($this->chanPreviewContainer == 'mjpeg') {
                $result .= la_img_sized($streamUrl, '', $width);
            }

            if ($this->chanPreviewContainer == 'hls') {
                $autoPlayMode = ($autoPlay) ? 'true' : 'false';
                $uniqId = 'hlsplayer' . la_InputId();
                $result .= la_tag('script', false, '', 'src="modules/jsc/playerjs/playerjs.js"') . la_tag('script', true);
                $result .= la_tag('div', false, '', 'id="' . $uniqId . '" style="width:' . $width . ';"') . la_tag('div', true);
                $result .= la_tag('script', false);
                $result .= 'var player = new Playerjs({id:"' . $uniqId . '", file:"' . $streamUrl . '", autoplay:' . $autoPlayMode . '});';
                $result .= la_tag('script', true);
            }
            return ($result);
        }

        /**
         * Gets channels preview as JSON from remote API call
         * 
         * @param string $channelGuid
         * @param bool $maxQuality
         * 
         * @return string
         */
        public function getMyChannelsPreview($channelGuid = '', $maxQuality = false) {
            $result = '';
            $channelGuid = vf($channelGuid);
            $channelFilter = (!empty($channelGuid)) ? $channelGuid : '';

            if ($channelFilter) {
                $result .= la_Link('?module=visor&previewchannels=true', __('Back'), true, 'anunreadbutton');
            }

            if (@$this->userstatsCfg['API_URL'] and @$this->userstatsCfg['API_KEY']) {
                if (!empty($this->myUserData)) {
                    if (isset($this->myUserData['id'])) {
                        $myVisorId = $this->myUserData['id'];
                        $requestUrl = '&action=visorchans&userid=' . $myVisorId . '&param=preview';
                        if ($maxQuality) {
                            $requestUrl .= '&fullsize=true';
                        }
                        $channels = zbs_remoteApiRequest($requestUrl);
                        if (!empty($channels)) {
                            @$channels = json_decode($channels);
                            if (!empty($channels)) {
                                foreach ($channels as $eachChanGuid => $eachUrl) {
                                    $filteredChan = true;
                                    $previewWidth = '300px';

                                    if ($channelFilter) {
                                        $previewWidth = '100%';
                                        if ($eachChanGuid == $channelFilter) {
                                            $filteredChan = true;
                                        } else {
                                            $filteredChan = false;
                                        }
                                    }


                                    if (!empty($eachUrl)) {
                                        if ($filteredChan) {
                                            $result .= la_tag('div', false, '', 'style="float:left; width:' . $previewWidth . '; margin:5px;"');
                                            $result .= $this->renderChannelPlayer($eachUrl, '90%', true);
                                            $result .= la_tag('br');
                                            $result .= la_tag('br');
                                            if (!$channelFilter) {
                                                $fullQualUrl = '?module=visor&previewchannels=true&fullpreview=' . $eachChanGuid;
                                                $result .= la_Link($fullQualUrl, __('View'), false, 'anreadbutton');
                                            }
                                            $result .= la_tag('div', true);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                die(__('ERROR: API_KEY/API_URL not set or empty!'));
            }

            return ($result);
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
            return ($result);
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
                        $currency = @$this->userstatsCfg['currency'];
                        $totalCamerasPrice = 0;
                        $allPayIds = $this->getAllPaymentIds();
                        $allTariffsFee = zbs_TariffGetAllPrices();

                        if ($this->myUserData['chargecams']) {
                            $primaryAddress = (isset($this->allAddress[$this->myLogin])) ? $this->allAddress[$this->myLogin] : $this->myLogin;
                            $result .= __('Money for cameras will be charged from your primary account') . ' ' . la_Link('index.php', $primaryAddress) . ' ';
                            $result .= __('if no funds for further cameras functioning') . '. ';
                            $result .= __('Your primary account balance now is') . ' ' . $this->allUsers[$this->myLogin]['Cash'] . ' ';
                            if ($this->userstatsCfg['OPENPAYZ_ENABLED']) {
                                $result .= $currency . '. ' . __('You can recharge it with following Payment ID') . ': ' . $allPayIds[$this->myLogin];
                            }
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
                                $cells .= la_TableCell($cameraFee . ' ' . $currency);
                                $rows .= la_TableRow($cells, 'row3');
                                $totalCamerasPrice += $cameraFee;
                            }
                        }

                        $totalPriceLabel = __('Total') . ' ' . la_tag('nobr') . $totalCamerasPrice . la_tag('nobr', true) . ' ' . $currency;
                        $cells = la_TableCell('');
                        $cells .= la_TableCell('');
                        $cells .= la_TableCell('');
                        $cells .= la_TableCell($totalPriceLabel);
                        $rows .= la_TableRow($cells, 'row1');
                        $result .= la_TableBody($rows, '100%', 0, 'resp-table');

                        $myChansCount = $this->getChansCount();
                        //user have some channels assigned
                        if ($myChansCount > 0) {
                            $result .= la_tag('br');
                            if (!la_CheckGet(array('previewchannels'))) {
                                $result .= la_Link('?module=visor&previewchannels=true', __('View'), false, 'anreadbutton');
                            } else {
                                if (!la_CheckGet(array('fullpreview'))) {
                                    $backUrl = '?module=visor';
                                } else {
                                    $backUrl = '?module=visor&previewchannels=true';
                                }
                                $result .= la_Link($backUrl, __('Back'), false, 'anunreadbutton') . ' ';
                                $result .= la_Link('?module=visor&software=true', __('Settings'), false, 'anreadbutton');
                            }
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
            return ($result);
        }

        /**
         * Renders some DVR auth data if user have some channels assigned.
         * 
         * @return void
         */
        public function renderDvrAuthData() {
            $result = '';
            if (!empty($this->myUserData)) {
                $myVisorId = $this->myUserData['id'];
                $requestUrl = '&action=visorchans&userid=' . $myVisorId . '&param=authdata';
                $rawData = zbs_remoteApiRequest($requestUrl);
                $dvrFullFlag = (@$this->userstatsCfg['VISOR_DVR_FULL']) ? true : false;
                if (!empty($rawData)) {
                    $authData = json_decode($rawData, true);
                    if (!empty($authData)) {
                        $cells = '';
                        $cells .= la_TableCell(__('Host'));
                        if ($dvrFullFlag) {
                            $cells .= la_TableCell(__('IP'));
                            $cells .= la_TableCell(__('Port'));
                        }
                        $cells .= la_TableCell(__('Login'));
                        $cells .= la_TableCell(__('Password'));
                        $cells .= la_TableCell(__('Actions'));
                        $rows = la_TableRow($cells, 'row1');

                        foreach ($authData as $io => $each) {
                            $cells = '';
                            $cells= la_TableCell($each['dvrname']);
                            if ($dvrFullFlag) {
                                $cells .= la_TableCell($each['ip']);
                                $cells .= la_TableCell($each['port']);
                            } 
                            
                            $cells .= la_TableCell($each['login']);
                            $cells .= la_TableCell($each['password']);
                            
                            $actLink = (!empty($each['weburl'])) ? la_Link($each['weburl'], __('Go to'), false, 'anreadbutton', 'target="_BLANK"') : '';
                            $cells .= la_TableCell($actLink);
                            $rows .= la_TableRow($cells, 'row3');
                        }

                        $result .= la_TableBody($rows, '100%', 0, 'resp-table');
                    }
                }
            }
            return ($result);
        }

        /**
         * Renders available software list
         * 
         * @return string
         */
        public function renderSoftwareList() {
            $result = '';
            if (@$this->userstatsCfg['VISOR_SOFTWARE']) {
                $rawSoft = explode(',', $this->userstatsCfg['VISOR_SOFTWARE']);
                if (!empty($rawSoft)) {
                    $result .= la_tag('br');
                    foreach ($rawSoft as $ia => $eachLink) {
                        $eachLink = explode('|', $eachLink);
                        $result .= la_Link($eachLink[1], la_img($eachLink[0], $eachLink[2]), false, '', 'target="_BLANK"') . ' ';
                    }
                }
            }
            return ($result);
        }
    }

    $visor = new ZBSVisorInterface($user_login);
    //Surveillance user profile
    if (!la_CheckGet(array('fullpreview')) and !la_CheckGet(array('software'))) {
        show_window(__('Surveillance'), $visor->renderProfile());
    }

    //channels preview
    if (la_CheckGet(array('previewchannels'))) {
        if (!la_CheckGet(array('fullpreview'))) {
            show_window(__('View'), $visor->getMyChannelsPreview()); //low qual
        } else {
            show_window(__('View'), $visor->getMyChannelsPreview($_GET['fullpreview'], true)); //only one full qual
        }
    }

    if (la_CheckGet(array('software'))) {
        $authData = $visor->renderDvrAuthData();
        if (!empty($authData)) {
            show_window('', la_Link('?module=visor&previewchannels=true', __('Back'), true, 'anunreadbutton'));
            if (@$us_config['VISOR_SOFTWARE']) {
                show_window(__('Downloads'), $visor->renderSoftwareList());
            }
            show_window(__('Settings'), $authData);
        }
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}
