<?php

/**
 * Universal PBX calls recodrings viewer class
 */
class PBXMonitor {

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
    protected $voicePath = '';

    /**
     * Contains voice recors archive path
     *
     * @var string
     */
    protected $archivePath = '';

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
     * Basic ffmpeg path to search.
     *
     * @var string
     */
    protected $baseConverterPath = '';

    /**
     * File path for converted voice files
     *
     * @var string
     */
    protected $convertedPath = 'exports/';

    /**
     * ffmpeg log path
     *
     * @var string
     */
    protected $converterLogPath = 'exports/voiceconvert.log';

    /**
     * Default icons path
     */
    const ICON_PATH = 'skins/calls/';

    /**
     * Default module path
     */
    const URL_ME = '?module=pbxmonitor';

    /**
     * URL of user profile route
     */
    const URL_PROFILE = '?module=userprofile&username=';

    /**
     * Creates new PBX monitor instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfig();
        $this->detectFfmpeg();
        //       _______
        //     /` _____ `\;,
        //    /__(^===^)__\';,
        //      /  :::  \   ,;
        //     |   :::   | ,;'
        //     '._______.'`
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

        $this->voicePath = $this->altCfg['PBXMON_RECORDS_PATH'];
        $this->archivePath = $this->altCfg['PBXMON_ARCHIVE_PATH'];
        $this->baseConverterPath = $this->altCfg['PBXMON_FFMPG_PATH'];
    }

    /**
     * Detects is ffmpeg available on local system and sets ffmpegFlag and path properties.
     * 
     * @return void
     */
    protected function detectFfmpeg() {
        if (file_exists($this->baseConverterPath)) {
            $this->ffmpegFlag = true;
            $this->ffmpegPath = $this->baseConverterPath;
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
     * Catches file download or convert request
     * 
     * @return void
     */
    public function catchFileDownload() {
        if (ubRouting::checkGet('dlpbxcall')) {
            $origFileName = ubRouting::get('dlpbxcall');
            $downloadableName = '';
            //voice records
            if (file_exists($this->voicePath . $origFileName)) {
                $downloadableName = $this->voicePath . $origFileName;
            } else {
                //archive download
                if (file_exists($this->archivePath . $origFileName)) {
                    $downloadableName = $this->archivePath . $origFileName;
                }
            }

            //voice files converter installed?
            if ($this->ffmpegFlag) {
                if (ubRouting::checkGet('playable')) {
                    //need to run converter
                    if (!empty($downloadableName)) {
                        //original file is already located
                        $newFileExtension = (ubRouting::checkGet('mp3')) ? '.mp3' : '.ogg';
                        $newFilePath = $this->convertedPath . $origFileName . $newFileExtension;
                        $command = $this->ffmpegPath . ' -y -i ' . $downloadableName . ' ' . $newFilePath . ' 2>> ' . $this->converterLogPath;
                        shell_exec($command);
                        $downloadableName = $newFilePath;
                    }
                }
            } else {
                show_error(__('ffmpeg is not installed. Web player and converter not available.'));
            }

            //file download processing
            if (!empty($downloadableName)) {
                zb_DownloadFile($downloadableName, 'default');
            } else {
                show_error(__('File not exist') . ': ' . $origFileName);
            }
        }
    }

    /**
     * Returns list of all files in directory. Using this instead of rcms_scandir with filters
     * to prevent of much of preg_match callbacks and avoid performance issues.
     * 
     * @param string $directory
     * 
     * @return array
     */
    protected function scanDirectory($directory) {
        $result = array();
        if (!empty($directory)) {
            if (file_exists($directory)) {
                $raw = scandir($directory);
                $result = array_diff($raw, array('.', '..'));
            }
        }
        return($result);
    }

    /**
     * Returns available calls files array 
     * 
     * @return array
     */
    protected function getCallsDir() {
        return ($this->scanDirectory($this->voicePath));
    }

    /**
     * Returns available archived calls files array 
     * 
     * @return array
     */
    protected function getArchiveDir() {
        return ($this->scanDirectory($this->archivePath));
    }

    /**
     * Returns calls list container
     * 
     * @return string
     */
    public function renderCallsList() {
        $opts = '"order": [[ 0, "desc" ]]';
        $columns = array(__('Date'), __('Number'), __('User'), __('Tags'), __('File'));
        if (ubRouting::checkGet('username')) {
            $loginFilter = '&loginfilter=' . ubRouting::get('username');
        } else {
            $loginFilter = '';
        }
        if (ubRouting::checkGet('renderall')) {
            $filterNumber = '&renderall=true';
        } else {
            $filterNumber = '';
        }

        $result = wf_JqDtLoader($columns, self::URL_ME . '&ajax=true' . $loginFilter . $filterNumber, false, __('Calls records'), 100, $opts);
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
     * @param bool $renderAll
     * 
     * @return void
     */
    public function jsonCallsList($filterLogin = '', $renderAll = false) {
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
        if (empty($filterLogin) AND ! $renderAll) {
            $renderAll = false;
        } else {
            $renderAll = true;
        }

        $allCallsLabel = ($renderAll) ? wf_img('skins/allcalls.png', __('All time')) . ' ' : '';

        //normal voice records rendering
        if (!empty($allVoiceFiles)) {
            foreach ($allVoiceFiles as $io => $each) {
                $fileName = $each;
                if (filesize($this->voicePath . $fileName) > 0) {
                    $rowFiltered = false;
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
                        $fileUrl = self::URL_ME . '&dlpbxcall=' . $fileName;

                        if ((empty($filterLogin)) OR ( $filterLogin == $userLogin)) {
                            if ($renderAll) {
                                $rowFiltered = true;
                            } else {
                                if (ispos($cleanDate, $curYear)) {
                                    $rowFiltered = true;
                                }
                            }

                            //append data to results
                            if ($rowFiltered) {
                                $data[] = wf_img($callDirection) . ' ' . $cleanDate;
                                $data[] = $callingNumber;
                                $data[] = $userLink;
                                $data[] = $this->renderUserTags($userLogin);
                                $data[] = $this->getSoundcontrols($fileUrl) . $allCallsLabel;
                                $json->addRow($data);
                                unset($data);
                            }
                        }
                    }
                }
            }
        }

        //archived records rendering
        if (!empty($allArchiveFiles)) {
            $archiveLabel = wf_img('skins/calls/archived.png', __('Archive'));
            foreach ($allArchiveFiles as $io => $each) {
                $fileName = $each;
                if (filesize($this->archivePath . $fileName) > 0) {
                    $rowFiltered = false;
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
                        $fileUrl = self::URL_ME . '&dlpbxcall=' . $fileName;
                        if ((empty($filterLogin)) OR ( $filterLogin == $userLogin)) {
                            if ($renderAll) {
                                $rowFiltered = true;
                            } else {
                                if (ispos($cleanDate, $curYear)) {
                                    $rowFiltered = true;
                                }
                            }
                        }

                        //append data to results
                        if ($rowFiltered) {
                            $data[] = wf_img($callDirection) . ' ' . $cleanDate;
                            $data[] = $callingNumber;
                            $data[] = $userLink;
                            $data[] = $this->renderUserTags($userLogin);
                            $data[] = $this->getSoundcontrols($fileUrl) . $archiveLabel . $allCallsLabel;
                            $json->addRow($data);
                            unset($data);
                        }
                    }
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
            if ($this->ffmpegFlag) {
                $playableUrl = $fileUrl . '&playable=true';
                $iconPlay = wf_img('skins/play.png', __('Play'));
                $iconPause = wf_img('skins/pause.png', __('Pause'));
                $playerId = 'player_' . wf_InputId();
                $playControlId = 'controller_' . wf_InputId();
                $result .= wf_tag('audio', false, '', 'id="' . $playerId . '" src="' . $playableUrl . '" preload=none') . wf_tag('audio', true);
                $playController = 'document.getElementById(\'' . $playerId . '\').play();';
                $result .= wf_Link('#', $iconPlay, false, '', 'id="' . $playControlId . '" onclick="' . $playController . '"') . ' ';
                $result .= wf_Link('#', $iconPause, false, '', 'onclick="document.getElementById(\'' . $playerId . '\').pause();"') . ' ';
                $result .= wf_Link($playableUrl, wf_img('skins/icon_ogg.png', __('Download') . ' ' . __('as OGG'))) . ' ';
                $result .= wf_Link($playableUrl . '&mp3=true', wf_img('skins/icon_mp3.png', __('Download') . ' ' . __('as MP3'))) . ' ';
            } else {
                $result .= wf_Link('#', wf_img('skins/factorcontrol.png', __('ffmpeg is not installed. Web player and converter not available.'))) . ' ';
            }
            //basic download control
            $result .= wf_Link($fileUrl, wf_img('skins/icon_download.png', __('Download') . ' ' . __('as is')));
        }

        return ($result);
    }

}
