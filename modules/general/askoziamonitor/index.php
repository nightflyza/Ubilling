<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['ASKOZIA_ENABLED']) {
    if (cfr('ASKOZIAMON')) {

        class AskoziaMonitor {

            /**
             * Contains system alter config as key=>value
             *
             * @var array
             */
            protected $altCfg = array();

            /**
             * Contains default recorded calls path
             *
             * @var string
             */
            protected $voicePath = '/mnt/askozia/';

            /**
             * Contains default recorded files file extensions
             *
             * @var string
             */
            protected $callsFormat = '*.gsm';

            /**
             * Flag for telepathy detection of users
             *
             * @var bool
             */
            protected $onlyMobileFlag = true;

            /**
             * Contains user assigned tags as login=>usertags
             *
             * @var array
             */
            protected $userTags = array();

            /**
             * Default icons path
             */
            const ICON_PATH = 'skins/calls/';

            /**
             * Default module path
             */
            const URL_ME = '?module=askoziamonitor';

            /**
             * Creates new askozia monitor instance
             * 
             * @return void
             */
            public function __construct() {
                $this->loadConfig();
            }

            /**
             * Loads all required configs and sets some options
             * 
             * @return void
             */
            protected function loadConfig() {
                global $ubillingConfig;
                $this->altCfg = $ubillingConfig->getAlter();
                if ((!isset($this->altCfg['WDYC_ONLY_MOBILE'])) OR ( !@$this->altCfg['WDYC_ONLY_MOBILE'])) {
                    $this->onlyMobileFlag = false;
                }
            }

            /**
             * Loads existing tagtypes and usertags into protected props for further usage
             * 
             * @return void
             */
            protected function loadUserTags() {
                $this->userTags = zb_UserGetAllTags();
            }

            /**
             * Catches file download
             * 
             * @return void
             */
            public function catchFileDownload() {
                if (wf_CheckGet(array('dlaskcall'))) {
                    zb_DownloadFile($this->voicePath . $_GET['dlaskcall'], 'default');
                }
            }

            /**
             * Returns available calls files array 
             * 
             * @return array
             */
            protected function getCallsDir() {
                $result = array();
                $result = rcms_scandir($this->voicePath, $this->callsFormat, 'file');
                return ($result);
            }

            /**
             * Returns calls list container
             * 
             * @return string
             */
            public function renderCallsList() {
                $opts = '"order": [[ 0, "desc" ]]';
                $columns = array(__('Date'), __('Number'), __('User'), __('Tags'), __('File'));
                if (wf_CheckGet(array('username'))) {
                    $loginFilter = '&loginfilter=' . $_GET['username'];
                } else {
                    $loginFilter = '';
                }
                $result = wf_JqDtLoader($columns, self::URL_ME . '&ajax=true' . $loginFilter, false, __('Calls records'), 100, $opts);
                return ($result);
            }

            /**
             * Renders user tags if available
             * 
             * @param string $userLogin
             * 
             * @return string
             */
            protected function renderUserTags($userLogin) {
                $result = '';
                if (!empty($userLogin)) {
                    if (isset($this->userTags[$userLogin])) {
                        if (!empty($this->userTags[$userLogin])) {
                            $result.=implode(', ', $this->userTags[$userLogin]);
                        }
                    }
                }
                return ($result);
            }

            /**
             * Renders json recorded calls list
             * 
             * @param string $filterLogin
             * 
             * @return void
             */
            public function jsonCallsList($filterLogin = '') {
                $allAddress = zb_AddressGetFulladdresslistCached();
                $allRealnames = zb_UserGetAllRealnames();
                $this->loadUserTags();
                $json = new wf_JqDtHelper();
                $allVoiceFiles = $this->getCallsDir();
                $telepathy = new Telepathy(false, true);
                $telepathy->usePhones();
                if (!empty($allVoiceFiles)) {
                    /**
                     * Fuck a fucking placement, I don't need you motherfuckers
                     * I'ma get it on my own before I get on your production
                     * 'Cause you fucking pieces of shit don't show no motherfucking love to me
                     * I see right through your guise, you try and hide but you can't run from me
                     */
                    foreach ($allVoiceFiles as $io => $each) {
                        $fileName = $each;
                        $explodedFile = explode('_', $fileName);
                        $cleanDate = explode('.', $explodedFile[2]);
                        $cleanDate = $cleanDate[0];
                        $callingNumber = $explodedFile[1];
                        $callDirection = ($explodedFile[0] == 'in') ? self::ICON_PATH . 'incoming.png' : self::ICON_PATH . 'outgoing.png';
                        //unfinished calls
                        if ((!ispos($cleanDate, 'in')) AND ( !ispos($cleanDate, 'out'))) {
                            //here onlyMobile flag used for mobile normalizing too
                            $userLogin = $telepathy->getByPhoneFast($callingNumber, $this->onlyMobileFlag, $this->onlyMobileFlag);
                            $userLink = (!empty($userLogin)) ? wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . @$allAddress[$userLogin]) . ' ' . @$allRealnames[$userLogin] : '';
                            $newDateString = date_format(date_create_from_format('Y-m-d-H-i-s', $cleanDate), 'Y-m-d H:i:s');
                            $cleanDate = $newDateString;
                            $fileUrl = self::URL_ME . '&dlaskcall=' . $fileName;
                            if ((empty($filterLogin)) OR ( $filterLogin == $userLogin)) {
                                $data[] = wf_img($callDirection) . ' ' . $cleanDate;
                                $data[] = $callingNumber;
                                $data[] = $userLink;
                                $data[] = $this->renderUserTags($userLogin);
                                $data[] = $this->getSoundcontrols($fileUrl);
                                $json->addRow($data);
                            }
                            unset($data);
                        }
                    }
                }
                $telepathy->savePhoneTelepathyCache();
                $json->getJson();
            }

            /**
             * Inits gsm/wav player for further usage
             * 
             * @return string
             */
            public function initPlayer() {
                $result = '';
                $result.=wf_tag('script', false, '', 'src="modules/jsc/wavplay/embed/domready.js"') . wf_tag('script', true);
                $result.=wf_tag('script', false, '', 'src="modules/jsc/wavplay/embed/swfobject.js"') . wf_tag('script', true);
                $result.=wf_tag('script', false, '', 'src="modules/jsc/wavplay/embed/tinywav.js"') . wf_tag('script', true);
                return ($result);
            }

            /**
             * Returns controls for some recorded call file
             * 
             * @param string $fileUrl
             * 
             * @return string
             */
            protected function getSoundcontrols($fileUrl) {
                $result = '';
                if (!empty($fileUrl)) {
                    $result.=wf_tag('a', false, '', 'onclick="try{window.TinyWav.Play(\'' . $fileUrl . '\')}catch(E){alert(E)}"') . wf_img('skins/play.png', __('Play')) . wf_tag('a', true) . ' ';
                    $result.=wf_tag('a', false, '', 'onclick="try{window.TinyWav.Pause()}catch(E){alert(E)}"') . wf_img('skins/pause.png', __('Pause')) . wf_tag('a', true) . ' ';
                    $result.=wf_tag('a', false, '', 'onclick="try{window.TinyWav.Resume()}catch(E){alert(E)}"') . wf_img('skins/continue.png', __('Continue')) . wf_tag('a', true) . ' ';
                    $result.=wf_Link($fileUrl, wf_img('skins/icon_download.png', __('Download')));
                }

                return ($result);
            }

        }

        set_time_limit(0);
        $askMon = new AskoziaMonitor();


        //catching voice record download
        $askMon->catchFileDownload();

        //rendering ajax datatables data
        if (wf_CheckGet(array('ajax'))) {
            $loginFilter = (wf_CheckGet(array('loginfilter'))) ? $_GET['loginfilter'] : '';
            $askMon->jsonCallsList($loginFilter);
        }

        //manual cache cleanup
        if (wf_CheckGet(array('cleantelepathycache'))) {
            $telepathy = new Telepathy(false, true, false, true);
            $telepathy->usePhones();
            $telepathy->flushPhoneTelepathyCache();
            rcms_redirect($askMon::URL_ME);
        }

        $windowControls = wf_Link($askMon::URL_ME . '&cleantelepathycache=true', wf_img('skins/icon_cleanup.png', __('Cache cleanup')), false);
        show_window(__('Askozia calls records') . ' ' . $windowControls, $askMon->initPlayer() . $askMon->renderCallsList());
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('AskoziaPBX integration now disabled'));
}
?>