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
         * Contains current locale in two letters format
         *
         * @var string
         */
        protected $lang='en';

        /**
         * Some default tables names
         */
        const TABLE_USERS = 'visor_users';
        const TABLE_CAMS = 'visor_cams';
        const TABLE_DVRS = 'visor_dvrs';
        const TABLE_CHANS = 'visor_chans';

        public function __construct($login) {
            $this->loadConfigs();
            $this->setLang();
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
     * Sets current locale code in two letter format
     *
     * @return void
     */
    protected function setLang() {
        
        $currentLocale = $this->userstatsCfg['lang'];
        $langCode = 'en';
        switch ($currentLocale) {
            case 'ukrainian':
                $langCode = 'uk';
                break;
            case 'english':
                $langCode = 'en';
                break;
            case 'portuguese':
                $langCode = 'pt';
                break;
            case 'spanish':
                $langCode = 'es';
                break;
            case 'russian':
                $langCode = 'ru';
                break;
        }
        $this->lang = $langCode;
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
         * @param string $height
         * @param bool $autoPlay
         * @param string $fullUrl
         * 
         * @return string
         */
        protected function renderChannelPlayer($streamUrl, $width, $height, $autoPlay = false, $fullUrl = '') {
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
                if ($fullUrl) {
                    $result .= wf_Link($fullUrl, wf_img_sized($streamUrl, '', $width, $height));
                } else {
                    $result .= wf_img_sized($streamUrl, '', $width, $height);
                }
            }

            if ($this->chanPreviewContainer == 'hls') {
                $autoPlayMode = ($autoPlay) ? 'true' : 'false';
                 $lang = 'lang: "' . $this->lang . '", ';
                $uniqId = 'hlsplayer' . wf_InputId();
                $result .= wf_tag('script', false, '', 'src="modules/jsc/playerjs/w7.js"') . wf_tag('script', true);
                $result .= wf_tag('div', false, '', 'id="' . $uniqId . '" style="width:' . $width . '; height:' . $height . ';"') . wf_tag('div', true);
                $result .= wf_tag('script', false);
                $result .= 'var player = new Playerjs({id:"' . $uniqId . '", ' . $lang . ' file:"' . $streamUrl . '", autoplay:' . $autoPlayMode . '});';
                $result .= wf_tag('script', true);
                if ($fullUrl) {
                    $result .= wf_Link($fullUrl, __('View'), false, '');
                }
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
                $result .= wf_Link('?module=visor&previewchannels=true', __('Back'), true, 'anunreadbutton');
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
                                    $previewHeight = '185px';

                                    if ($channelFilter) {
                                        $previewWidth = '90%';
                                        $previewHeight = 'auto';
                                        if ($eachChanGuid == $channelFilter) {
                                            $filteredChan = true;
                                        } else {
                                            $filteredChan = false;
                                        }
                                    }


                                    if (!empty($eachUrl)) {
                                        if ($filteredChan) {
                                            $fullQualUrl = '';
                                            if (!$channelFilter) {
                                                $fullQualUrl = '?module=visor&previewchannels=true&fullpreview=' . $eachChanGuid;
                                            }
                                            $result .= wf_tag('div', false, '', 'style="float:left; width:' . $previewWidth . '; height:' . $previewHeight . '; margin:5px; overflow:hidden;"');
                                            $result .= $this->renderChannelPlayer($eachUrl, $previewWidth, $previewHeight, true, $fullQualUrl);
                                            $result .= wf_tag('div', true);
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
                            $result .= __('Money for cameras will be charged from your primary account') . ' ' . wf_Link('index.php', $primaryAddress) . ' ';
                            $result .= __('if no funds for further cameras functioning') . '. ';
                            $result .= __('Your primary account balance now is') . ' ' . $this->allUsers[$this->myLogin]['Cash'] . ' ';
                            if ($this->userstatsCfg['OPENPAYZ_ENABLED']) {
                                $result .= $currency . '. ' . __('You can recharge it with following Payment ID') . ': ' . $allPayIds[$this->myLogin];
                            }
                            $result .= wf_delimiter();
                        }
                        $result .= wf_tag('h3') . __('Your cameras') . wf_tag('h3', true);
                        $cells = wf_TableCell(__('Address'));
                        $cells .= wf_TableCell(__('Payment ID'));
                        $cells .= wf_TableCell(__('Balance'));
                        $cells .= wf_TableCell(__('Fee'));
                        $rows = wf_TableRow($cells, 'row1');
                        foreach ($this->myCameras as $io => $eachCam) {
                            $cameraLogin = $eachCam['login'];
                            $cameraTariff = $this->allUsers[$cameraLogin]['Tariff'];
                            $cameraFee = (isset($allTariffsFee[$cameraTariff])) ? $allTariffsFee[$cameraTariff] : 0;

                            if (isset($this->allUsers[$cameraLogin])) {
                                $cells = wf_TableCell(@$this->allAddress[$cameraLogin]);
                                $cells .= wf_TableCell(@$allPayIds[$cameraLogin]);
                                $cells .= wf_TableCell(@$this->allUsers[$cameraLogin]['Cash']);
                                $cells .= wf_TableCell($cameraFee . ' ' . $currency);
                                $rows .= wf_TableRow($cells, 'row3');
                                $totalCamerasPrice += $cameraFee;
                            }
                        }

                        $totalPriceLabel = __('Total') . ' ' . wf_tag('nobr') . $totalCamerasPrice . wf_tag('nobr', true) . ' ' . $currency;
                        $cells = wf_TableCell('');
                        $cells .= wf_TableCell('');
                        $cells .= wf_TableCell('');
                        $cells .= wf_TableCell($totalPriceLabel);
                        $rows .= wf_TableRow($cells, 'row1');
                        $result .= wf_TableBody($rows, '100%', 0, 'resp-table');

                        $myChansCount = $this->getChansCount();
                        //user have some channels assigned
                        if ($myChansCount > 0) {
                            $result .= wf_tag('br');
                            if (!wf_CheckGet(array('previewchannels'))) {
                                $result .= wf_Link('?module=visor&previewchannels=true', __('View'), false, 'anreadbutton');
                            } else {
                                if (!wf_CheckGet(array('fullpreview'))) {
                                    $backUrl = '?module=visor';
                                } else {
                                    $backUrl = '?module=visor&previewchannels=true';
                                }
                                $result .= wf_Link($backUrl, __('Back'), false, 'anunreadbutton') . ' ';
                                $result .= wf_Link('?module=visor&software=true', __('Settings'), false, 'anreadbutton');
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
                        $cells .= wf_TableCell(__('Host'));
                        if ($dvrFullFlag) {
                            $cells .= wf_TableCell(__('IP'));
                            $cells .= wf_TableCell(__('Port'));
                        }
                        $cells .= wf_TableCell(__('Login'));
                        $cells .= wf_TableCell(__('Password'));
                        $cells .= wf_TableCell(__('Actions'));
                        $rows = wf_TableRow($cells, 'row1');

                        foreach ($authData as $io => $each) {
                            $cells = '';
                            $cells = wf_TableCell($each['dvrname']);
                            if ($dvrFullFlag) {
                                $cells .= wf_TableCell($each['ip']);
                                $cells .= wf_TableCell($each['port']);
                            }

                            $cells .= wf_TableCell($each['login']);
                            $cells .= wf_TableCell($each['password']);

                            $actLink = (!empty($each['weburl'])) ? wf_Link($each['weburl'], __('Go to'), false, 'anreadbutton', 'target="_BLANK"') : '';
                            $cells .= wf_TableCell($actLink);
                            $rows .= wf_TableRow($cells, 'row3');
                        }

                        $result .= wf_TableBody($rows, '100%', 0, 'resp-table');
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
                    $result .= wf_tag('br');
                    foreach ($rawSoft as $ia => $eachLink) {
                        $eachLink = explode('|', $eachLink);
                        $result .= wf_Link($eachLink[1], wf_img($eachLink[0], $eachLink[2]), false, '', 'target="_BLANK"') . ' ';
                    }
                }
            }
            return ($result);
        }
    }

    $visor = new ZBSVisorInterface($user_login);
    //Surveillance user profile
    if (!wf_CheckGet(array('fullpreview')) and !wf_CheckGet(array('software'))) {
        show_window(__('Surveillance'), $visor->renderProfile());
    }

    //channels preview
    if (wf_CheckGet(array('previewchannels'))) {
        if (!wf_CheckGet(array('fullpreview'))) {
            show_window(__('View'), $visor->getMyChannelsPreview()); //low qual
        } else {
            show_window(__('View'), $visor->getMyChannelsPreview($_GET['fullpreview'], true)); //only one full qual
        }
    }

    if (wf_CheckGet(array('software'))) {
        $authData = $visor->renderDvrAuthData();
        if (!empty($authData)) {
            show_window('', wf_Link('?module=visor&previewchannels=true', __('Back'), true, 'anunreadbutton'));
            if (@$us_config['VISOR_SOFTWARE']) {
                show_window(__('Downloads'), $visor->renderSoftwareList());
            }
            show_window(__('Settings'), $authData);
        }
    }
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}
