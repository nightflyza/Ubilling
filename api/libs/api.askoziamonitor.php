<?php

/**
 * AskoziaPBX calls recodrings viewer class
 */
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
     * Contains voice recors archive path
     *
     * @var string
     */
    protected $archivePath = '/mnt/calls_archive/';

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
     * FFmpeg installed?
     *
     * @var bool
     */
    protected $ffmpegFlag = false;

    /**
     * installed ffmpeg path
     *
     * @var string
     */
    protected $ffmpegPath = '';

    /**
     * Default icons path
     */
    const ICON_PATH = 'skins/calls/';

    /**
     * Default module path
     */
    const URL_ME = '?module=askoziamonitor';

    /**
     * URL of user profile route
     */
    const URL_PROFILE = '?module=userprofile&username=';

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
     * Detects is ffmpeg installed on local system and sets ffmpegFlag and path properties.
     * 
     * @return void
     */
    protected function detectFfmpeg() {
        $command = 'which ffmpeg';
        $rawLocation = shell_exec($command);
        if (!empty($rawLocation)) {
            $this->ffmpegFlag = true;
            $this->ffmpegPath = trim($rawLocation);
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
            //voice records
            if (file_exists($this->voicePath . $_GET['dlaskcall'])) {
                zb_DownloadFile($this->voicePath . $_GET['dlaskcall'], 'default');
            } else {
                //archive download
                if (file_exists($this->archivePath . $_GET['dlaskcall'])) {
                    zb_DownloadFile($this->archivePath . $_GET['dlaskcall'], 'default');
                } else {
                    show_error(__('File not exist') . ': ' . $_GET['dlaskcall']);
                }
            }
        }
    }

    /**
     * Returns available calls files array 
     * 
     * @return array
     */
    protected function getCallsDir() {
        $result = array();
        if (file_exists($this->voicePath)) {
            $result = rcms_scandir($this->voicePath, $this->callsFormat, 'file');
        }
        return ($result);
    }

    /**
     * Returns available archived calls files array 
     * 
     * @return array
     */
    protected function getArchiveDir() {
        $result = array();
        if (file_exists($this->archivePath)) {
            $result = rcms_scandir($this->archivePath, $this->callsFormat, 'file');
        }
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
                    $result .= implode(', ', $this->userTags[$userLogin]);
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
        $allArchiveFiles = $this->getArchiveDir();
        $telepathy = new Telepathy(false, true);
        $telepathy->usePhones();
        $askCalls = new nya_askcalls();
        $previousCalls = $askCalls->getAll('filename');
        $curYear = curyear() . '-';
        //current year filter for all calls
        if (empty($filterLogin)) {
            $renderAll = false;
        } else {
            $renderAll = true;
        }

        //normal voice records rendering
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
                    if (!isset($previousCalls[$fileName])) {
                        //here onlyMobile flag used for mobile normalizing too
                        $userLogin = $telepathy->getByPhoneFast($callingNumber, $this->onlyMobileFlag, $this->onlyMobileFlag);
                        $askCalls->data('filename', ubRouting::filters($fileName, 'mres'));
                        $askCalls->data('login', ubRouting::filters($userLogin, 'mres'));
                        $askCalls->create();
                    } else {
                        $userLogin = $previousCalls[$fileName]['login'];
                    }

                    $userLink = (!empty($userLogin)) ? wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . @$allAddress[$userLogin]) . ' ' . @$allRealnames[$userLogin] : '';
                    $newDateString = date_format(date_create_from_format('Y-m-d-H-i-s', $cleanDate), 'Y-m-d H:i:s');
                    $cleanDate = $newDateString;
                    $fileUrl = self::URL_ME . '&dlaskcall=' . $fileName;
                    if ((empty($filterLogin)) OR ( $filterLogin == $userLogin)) {
                        if ($renderAll) {
                            $data[] = wf_img($callDirection) . ' ' . $cleanDate;
                            $data[] = $callingNumber;
                            $data[] = $userLink;
                            $data[] = $this->renderUserTags($userLogin);
                            $data[] = $this->getSoundcontrols($fileUrl);
                            $json->addRow($data);
                        } else {
                            if (ispos($cleanDate, $curYear)) {
                                $data[] = wf_img($callDirection) . ' ' . $cleanDate;
                                $data[] = $callingNumber;
                                $data[] = $userLink;
                                $data[] = $this->renderUserTags($userLogin);
                                $data[] = $this->getSoundcontrols($fileUrl);
                                $json->addRow($data);
                            }
                        }
                    }
                    unset($data);
                }
            }
        }

        //archived records rendering
        if (!empty($allArchiveFiles)) {
            $archiveLabel = wf_img('skins/calls/archived.png', __('Archive'));
            foreach ($allArchiveFiles as $io => $each) {
                $fileName = $each;
                $explodedFile = explode('_', $fileName);
                $cleanDate = explode('.', $explodedFile[2]);
                $cleanDate = $cleanDate[0];
                $callingNumber = $explodedFile[1];
                $callDirection = ($explodedFile[0] == 'in') ? self::ICON_PATH . 'incoming.png' : self::ICON_PATH . 'outgoing.png';
                //unfinished calls
                if ((!ispos($cleanDate, 'in')) AND ( !ispos($cleanDate, 'out'))) {
                    if (!isset($previousCalls[$fileName])) {
                        //here onlyMobile flag used for mobile normalizing too
                        $userLogin = $telepathy->getByPhoneFast($callingNumber, $this->onlyMobileFlag, $this->onlyMobileFlag);
                        $askCalls->data('filename', ubRouting::filters($fileName, 'mres'));
                        $askCalls->data('login', ubRouting::filters($userLogin, 'mres'));
                        $askCalls->create();
                    } else {
                        $userLogin = $previousCalls[$fileName]['login'];
                    }

                    $userLink = (!empty($userLogin)) ? wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . @$allAddress[$userLogin]) . ' ' . @$allRealnames[$userLogin] : '';
                    $newDateString = date_format(date_create_from_format('Y-m-d-H-i-s', $cleanDate), 'Y-m-d H:i:s');
                    $cleanDate = $newDateString;
                    $fileUrl = self::URL_ME . '&dlaskcall=' . $fileName;
                    if ((empty($filterLogin)) OR ( $filterLogin == $userLogin)) {
                        if ($renderAll) {
                            $data[] = wf_img($callDirection) . ' ' . $cleanDate;
                            $data[] = $callingNumber;
                            $data[] = $userLink;
                            $data[] = $this->renderUserTags($userLogin);
                            $data[] = $this->getSoundcontrols($fileUrl) . ' ' . $archiveLabel;
                            $json->addRow($data);
                        } else {
                            if (ispos($cleanDate, $curYear)) {
                                $data[] = wf_img($callDirection) . ' ' . $cleanDate;
                                $data[] = $callingNumber;
                                $data[] = $userLink;
                                $data[] = $this->renderUserTags($userLogin);
                                $data[] = $this->getSoundcontrols($fileUrl) . ' ' . $archiveLabel;
                                $json->addRow($data);
                            }
                        }
                    }
                    unset($data);
                }
            }
        }
        $telepathy->savePhoneTelepathyCache();
        $json->getJson();
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
            $sampleUrl = 'modules/jsc/sounds/meabeab.mp3';

            $iconPlay = wf_img('skins/play.png', __('Play'));
            $iconPause = wf_img('skins/pause.png', __('Pause'));
            $playerId = 'player_' . wf_InputId();
            $result .= wf_tag('audio', false, '', 'id="' . $playerId . '" src="' . $sampleUrl . '" preload=none') . wf_tag('audio', true);
            $result .= wf_Link('#', $iconPlay, false, '', 'onclick="document.getElementById(\'' . $playerId . '\').play()"');
            $result .= wf_Link('#', $iconPause, false, '', 'onclick="document.getElementById(\'' . $playerId . '\').pause()"');
            $result .= wf_Link($fileUrl, wf_img('skins/icon_download.png', __('Download')));
        }

        return ($result);
    }

}
