<?php

/**
 * Universal PBX calls recodrings viewer class
 */
class PBXMonitor {

    /**
     * Contains all call records loaded from database
     *
     * @var array
     */
    protected $allRecords = array();

    /**
     * Contains count of call records available
     *
     * @var int
     */
    protected $totalRecordsCount = 0;

    /**
     * Contains filtered records count
     *
     * @var int
     */
    protected $filteredRecordsCount = 0;

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
     * PBX calls cache database abstraction layer
     *
     * @var object
     */
    protected $pbxCallsDb = '';

    /**
     * Default on-page calls number
     *
     * @var int
     */
    protected $onPage = 50;

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
     * Name of database table with calls cache
     */
    const TABLE_CALLS = 'pbxcalls';

    /**
     * Cache refill process PID
     */
    const REFILL_PID = 'PBXCALLS';

    /**
     * Creates new PBX monitor instance
     * 
     * @return void
     */
    public function __construct() {
        $this->loadConfig();
        $this->detectFfmpeg();
        $this->initPbxCallsDb();
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
        if ((!isset($this->altCfg['WDYC_ONLY_MOBILE'])) or (!@$this->altCfg['WDYC_ONLY_MOBILE'])) {
            $this->onlyMobileFlag = false;
        }

        $this->voicePath = $this->altCfg['PBXMON_RECORDS_PATH'];
        $this->archivePath = $this->altCfg['PBXMON_ARCHIVE_PATH'];
        $this->baseConverterPath = $this->altCfg['PBXMON_FFMPG_PATH'];
    }

    /**
     * Inits calls cache database abstraction layer
     *
     * @return void
     */
    protected function initPbxCallsDb() {
        $this->pbxCallsDb = new NyanORM('pbxcalls');
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
                        //convert if not already converted
                        if (!file_exists($newFilePath)) {
                            $command = $this->ffmpegPath . ' -y -i ' . $downloadableName . ' ' . $newFilePath . ' 2>> ' . $this->converterLogPath;
                            shell_exec($command);
                        }

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
        return ($result);
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

        $result = wf_JqDtLoader($columns, self::URL_ME . '&ajax=true' . $loginFilter . $filterNumber, false, __('Calls records'), $this->onPage, $opts, false, '', '', true);
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
     * Refills calls cache with some new calls if found
     * 
     * @return void
     */
    public function refillCache() {
        $process = new StarDust(self::REFILL_PID);
        if ($process->notRunning()) {
            $process->start();

            $allVoiceFiles = $this->getCallsDir();
            $allArchiveFiles = $this->getArchiveDir();
            $telepathy = new Telepathy(false, true);
            $telepathy->usePhones();
            $previousCalls = $this->pbxCallsDb->getAll('filename');

            //normal voice records
            if (!empty($allVoiceFiles)) {
                foreach ($allVoiceFiles as $io => $each) {
                    $fileName = $each;
                    $explodedFile = explode('_', $fileName);
                    $cleanDate = explode('.', $explodedFile[2]);
                    $cleanDate = $cleanDate[0];

                    //unfinished calls
                    if ((!ispos($cleanDate, 'in')) and (!ispos($cleanDate, 'out'))) {
                        //new call?
                        if (!isset($previousCalls[$fileName])) {
                            $fileSize = filesize($this->voicePath . $fileName);
                            if ($fileSize > 0) {
                                $callingNumber = $explodedFile[1];
                                $callDirection = ($explodedFile[0] == 'in') ? 'in' : 'out';
                                $dateString = date_format(date_create_from_format('Y-m-d-H-i-s', $cleanDate), 'Y-m-d H:i:s');
                                $userLogin = $telepathy->getByPhoneFast($callingNumber, $this->onlyMobileFlag, $this->onlyMobileFlag);
                                $this->pbxCallsDb->data('filename', ubRouting::filters($fileName, 'mres'));
                                $this->pbxCallsDb->data('login', ubRouting::filters($userLogin, 'mres'));
                                $this->pbxCallsDb->data('size', $fileSize);
                                $this->pbxCallsDb->data('direction', $callDirection);
                                $this->pbxCallsDb->data('date', $dateString);
                                $this->pbxCallsDb->data('number', $callingNumber);
                                $this->pbxCallsDb->data('storage', 'rec');
                                $this->pbxCallsDb->create();
                            }
                        } else {
                            $callData = $previousCalls[$fileName];
                            //storage changed?
                            if ($callData['storage'] != 'rec') {
                                $callId = $callData['id'];
                                $this->pbxCallsDb->where('id', '=', $callId);
                                $this->pbxCallsDb->data('storage', 'rec');
                                $this->pbxCallsDb->save();
                            }
                        }
                    }
                }
            }

            //archived records
            if (!empty($allArchiveFiles)) {
                foreach ($allArchiveFiles as $io => $each) {
                    $fileName = $each;
                    $explodedFile = explode('_', $fileName);
                    $cleanDate = explode('.', $explodedFile[2]);
                    $cleanDate = $cleanDate[0];

                    //unfinished calls
                    if ((!ispos($cleanDate, 'in')) and (!ispos($cleanDate, 'out'))) {
                        //new call?
                        if (!isset($previousCalls[$fileName])) {
                            $fileSize = filesize($this->archivePath . $fileName);
                            if ($fileSize > 0) {
                                $callingNumber = $explodedFile[1];
                                $callDirection = ($explodedFile[0] == 'in') ? 'in' : 'out';
                                $dateString = date_format(date_create_from_format('Y-m-d-H-i-s', $cleanDate), 'Y-m-d H:i:s');
                                $userLogin = $telepathy->getByPhoneFast($callingNumber, $this->onlyMobileFlag, $this->onlyMobileFlag);
                                $this->pbxCallsDb->data('filename', ubRouting::filters($fileName, 'mres'));
                                $this->pbxCallsDb->data('login', ubRouting::filters($userLogin, 'mres'));
                                $this->pbxCallsDb->data('size', $fileSize);
                                $this->pbxCallsDb->data('direction', $callDirection);
                                $this->pbxCallsDb->data('date', $dateString);
                                $this->pbxCallsDb->data('number', $callingNumber);
                                $this->pbxCallsDb->data('storage', 'arch');
                                $this->pbxCallsDb->create();
                            }
                        } else {
                            $callData = $previousCalls[$fileName];
                            //storage changed?
                            if ($callData['storage'] != 'arch') {
                                $callId = $callData['id'];
                                $this->pbxCallsDb->where('id', '=', $callId);
                                $this->pbxCallsDb->data('storage', 'arch');
                                $this->pbxCallsDb->save();
                            }
                        }
                    }
                }
            }

            $telepathy->savePhoneTelepathyCache();
            $process->stop();
        } else {
            log_register('PBXMON REFILL SKIPPED ALREADY RUNNING');
        }
    }


    /**
     * Performs records filtering, ordering and load
     *
     * @return void
     */
    protected function recordsLoader($filterLogin = '', $renderAll = false) {
        $filterLogin = ubRouting::filters($filterLogin, 'mres');

        $this->onPage = (ubRouting::checkGet('iDisplayLength')) ? ubRouting::get('iDisplayLength') : $this->onPage;

        //login filtering
        if ($filterLogin) {
            $this->pbxCallsDb->where('login', '=', $filterLogin);
        } else {
            //date current year filtering 
            if (!$renderAll) {
                $this->pbxCallsDb->where('date', 'LIKE', curyear() . '-%');
            }
        }


        $sortField = 'date';
        $sortDir = 'desc';
        if (ubRouting::checkGet('iSortCol_0', false)) {
            $sortingColumn = ubRouting::get('iSortCol_0', 'int');
            $sortDir = ubRouting::get('sSortDir_0', 'gigasafe');
            switch ($sortingColumn) {
                case 0:
                    $sortField = 'date';
                    break;
                case 1:
                    $sortField = 'number';
                    break;
                case 2:
                    $sortField = 'login';
                    break;
            }
        }
        $this->pbxCallsDb->orderBy($sortField, $sortDir);
        $this->totalRecordsCount = $this->pbxCallsDb->getFieldsCount('id', false);



        $offset = 0;
        if (ubRouting::checkGet('iDisplayStart')) {
            $offset = ubRouting::get('iDisplayStart', 'int');
        }

        //optional live search
        $searchQuery = '';
        if (ubRouting::checkGet('sSearch')) {
            $searchQuery = ubRouting::get('sSearch', 'mres');
            if (!$filterLogin) {
                $dateQuery = ubRouting::filters($searchQuery, 'gigasafe', '-: ');
                $this->pbxCallsDb->where('number', 'LIKE', '%' . $searchQuery . '%');
                $this->pbxCallsDb->orWhere('date', 'LIKE', '%' . $dateQuery . '%');
                $this->pbxCallsDb->orWhere('login', 'LIKE', '%' . $searchQuery . '%');
            }
        }


        //optional live search happens
        if ($searchQuery) {
            $this->filteredRecordsCount = $this->pbxCallsDb->getFieldsCount('id', false) - 1;
        } else {
            $this->filteredRecordsCount = $this->totalRecordsCount;
        }
        $this->pbxCallsDb->limit($this->onPage, $offset);
        $this->allRecords = $this->pbxCallsDb->getAll();
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
        $this->recordsLoader($filterLogin, $renderAll);
        $json = new wf_JqDtHelper(true);
        $json->setTotalRowsCount($this->totalRecordsCount);
        $json->setFilteredRowsCount($this->filteredRecordsCount);

        $curYear = curyear() . '-';
        //current year filter for all calls
        if (empty($filterLogin) and ! $renderAll) {
            $renderAll = false;
        } else {
            $renderAll = true;
        }

        $allCallsLabel = ($renderAll) ? wf_img('skins/allcalls.png', __('All time')) . ' ' : '';

        //normal voice records rendering
        if (!empty($this->allRecords)) {
            foreach ($this->allRecords as $io => $each) {
                $archiveLabel = ($each['storage'] == 'arch') ?  wf_img('skins/calls/archived.png', __('Archive')) : '';
                $userLogin = $each['login'];
                $callingNumber = $each['number'];
                $callDirection = ($each['direction'] == 'in') ? self::ICON_PATH . 'incoming.png' : self::ICON_PATH . 'outgoing.png';
                $userLink = (!empty($userLogin)) ? wf_Link('?module=userprofile&username=' . $userLogin, web_profile_icon() . ' ' . @$allAddress[$userLogin]) . ' ' . @$allRealnames[$userLogin] : '';
                $fileUrl = self::URL_ME . '&dlpbxcall=' . $each['filename'];
                //append data to results
                $data[] = wf_img($callDirection) . ' ' . $each['date'];
                $data[] = $callingNumber;
                $data[] = $userLink;
                $data[] = $this->renderUserTags($userLogin);
                $data[] = $this->getSoundcontrols($fileUrl, $each['filename'], $filterLogin) . $archiveLabel  . $allCallsLabel;
                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }


    /**
     * Returns controls for some recorded call file
     * 
     * @param string $fileUrl
     * @param string $fileName
     * @param string $userName
     * @return string
     */
    protected function getSoundcontrols($fileUrl, $fileName, $userName = '') {
        $result = '';
        $bl = '';
        if (!empty($userName)) {
            $bl = '&bl=' . $userName;
        }
        if (!empty($fileUrl)) {
            if ($this->ffmpegFlag) {
                $playableUrl = $fileUrl . '&playable=true';
                $iconPlay = wf_img('skins/play.png', __('Play'));
                $result .= wf_Link(self::URL_ME . '&pbxplayer=' . $fileName . $bl, $iconPlay, false) . ' ';
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

    /**
     * Returns player for some recorded call file
     * 
     * @param string $fileUrl
     * 
     * @return string
     */
    public function renderSoundPlayer($fileName) {
        $result = '';
        $backLink = self::URL_ME;
        if (ubRouting::checkGet('bl')) {
            $backLink = self::URL_ME . '&username=' . ubRouting::get('bl');
        }
        if (!empty($fileName)) {
            $fileUrl = self::URL_ME . '&dlpbxcall=' . $fileName;
            if ($this->ffmpegFlag) {
                $playableUrl = $fileUrl . '&playable=true';
                $playerId = 'pbxcallrecfile';
                $result .= wf_tag('audio', false, '', 'id="' . $playerId . '" src="' . $playableUrl . '" preload=auto') . wf_tag('audio', true);
                $result .= wf_tag('div', false, '', 'id="waveform"') . wf_tag('div', true);
                $result .= wf_tag('script', false, '', 'src="https://unpkg.com/wavesurfer.js"') . wf_tag('script', true);
                $result .= wf_tag('script', false, '', 'type="text/javascript" src="modules/jsc/pbxmonplayer.js"') . wf_tag('script', true);
                $result .= wf_delimiter(0);
                $result .= wf_Link($playableUrl, wf_img('skins/icon_ogg.png', __('Download') . ' ' . __('as OGG')) . ' ' . __('Download') . ' ' . __('as OGG'), false, 'ubButton') . ' ';
                $result .= wf_Link($playableUrl . '&mp3=true', wf_img('skins/icon_mp3.png', __('Download') . ' ' . __('as MP3')) . ' ' . __('Download') . ' ' . __('as MP3'), false, 'ubButton') . ' ';
            } else {
                $messages = new UbillingMessageHelper();
                $result .= $messages->getStyledMessage(__('ffmpeg is not installed. Web player and converter not available.'), 'warning');
                $result .= wf_delimiter(0);
            }
            //basic download control
            $result .= wf_Link($fileUrl, wf_img('skins/icon_download.png', __('Download') . ' ' . __('as is')) . ' ' . __('Download') . ' ' . __('as is'), false, 'ubButton');
        }

        $result .= wf_delimiter();
        $result .= wf_BackLink($backLink);

        return ($result);
    }
}
