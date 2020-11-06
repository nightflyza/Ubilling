<?php

/**
 * Network equipment FDB tables archive implementation
 */
class FDBArchive {

    /**
     * Contains system alter config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains all available switches devices as id=>data
     *
     * @var array
     */
    protected $allSwitches = array();

    /**
     * Contains existing switches MAC addresses as mac=>id
     *
     * @var array
     */
    protected $allSwitchesMac = array();

    /**
     * Contains available users data from cache
     *
     * @var array
     */
    protected $allUserData = array();

    /**
     * Contains available users mac address as mac=>login
     *
     * @var array
     */
    protected $allUsersMac = array();

    /**
     * Contains available ONU devices mac address as mac=>id
     *
     * @var array
     */
    protected $allOnuMac = array();

    /**
     * Contains available ONU devices assigned users id=>login
     *
     * @var array
     */
    protected $allOnuUsers = array();

    /**
     * Protected database model placeholder
     *
     * @var object
     */
    protected $archive = '';

    /**
     * Object wide json helper placeholder
     *
     * @var object
     */
    protected $json = '';

    /**
     * Days counter before automatic archive cleanup/rotation
     *
     * @var int
     */
    protected $daysRotate = 0;

    /**
     * Contains default FDB caches storage path
     */
    const PATH_CACHE = 'exports/';

    /**
     * Contains default switches FDB cache record postfix
     */
    const EXT_SWITCHES = '_fdb';

    /**
     * Contains default PON OLT FDB cache record postfix
     */
    const EXT_OLTS = '_OLTFDB';

    /**
     * Contains default archive database table name
     */
    const TABLE_ARCHIVE = 'fdbarchive';

    /**
     * Contains default module controller URL
     */
    const URL_ME = '?module=fdbarchive';

    /**
     * Contains default fdb cache module URL
     */
    const URL_CACHE = '?module=switchpoller';

    /**
     * Another required URLs for internal routing
     */
    const URL_SWITCHPROFILE = '?module=switches&edit=';
    const URL_USERPROFILE = '?module=userprofile&username=';
    const URL_ONUPROFILE = '?module=ponizer&editonu=';

    public function __construct() {
        $this->loadConfigs();
        $this->initJson();
        $this->initArchive();
        $this->loadSwitches();
    }

    /**
     * Preloads system configs into protected props for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadConfigs() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        if (isset($this->altCfg['FDBARCHIVE_MAX_AGE'])) {
            if (!empty($this->altCfg['FDBARCHIVE_MAX_AGE'])) {
                if (is_numeric($this->altCfg['FDBARCHIVE_MAX_AGE'])) {
                    $this->daysRotate = $this->altCfg['FDBARCHIVE_MAX_AGE'];
                }
            }
        }
    }

    /**
     * Inits archive model as protected property for further usage
     * 
     * @return void
     */
    protected function initArchive() {
        $this->archive = new NyanORM(self::TABLE_ARCHIVE);
    }

    /**
     * Inits archive model as protected property for further usage
     * 
     * @return void
     */
    protected function initJson() {
        $this->json = new wf_JqDtHelper();
    }

    /**
     * Loads switches into protected property
     * 
     * @return void
     */
    protected function loadSwitches() {
        $switches = new nya_switches();
        $this->allSwitches = $switches->getAll('id');
        if (!empty($this->allSwitches)) {
            foreach ($this->allSwitches as $io => $each) {
                if (!empty($each['swid'])) {
                    $this->allSwitchesMac[$each['swid']] = $each['id'];
                }
            }
        }
    }

    /**
     * Loads user data into protected properties for further usage
     * 
     * @return void
     */
    protected function loadUserData() {
        $this->allUserData = zb_UserGetAllDataCache();
        if (!empty($this->allUserData)) {
            $this->allUsersMac = zb_UserGetAllMACs();
            $this->allUsersMac = array_flip($this->allUsersMac);
        }
    }

    /**
     * Loads PON ONU devices data into protected properties for further usage
     * 
     * @return void
     */
    protected function loadOnuData() {
        $onu = new nya_pononu();
        $allOnu = $onu->getAll();
        if (!empty($allOnu)) {
            foreach ($allOnu as $io => $each) {
                if (!empty($each['mac'])) {
                    $this->allOnuMac[$each['mac']] = $each['id'];
                    if (!empty($each['login'])) {
                        $this->allOnuUsers[$each['id']] = $each['login'];
                    }
                }
            }
        }
    }

    /**
     * Extracts IP address from switch cache record name
     * 
     * @param string $cacheRecord
     * 
     * @return string
     */
    protected function extractSwitchIP($cacheRecord) {
        $result = '';
        if (!empty($cacheRecord)) {
            $result .= zb_ExtractIpAddress($cacheRecord);
        }
        return($result);
    }

    /**
     * Tryin to detect switch ID by its IP address
     * 
     * @param string $ip
     * 
     * @return int/void
     */
    protected function getSwitchId($ip) {
        $result = '';
        if (!empty($this->allSwitches)) {
            foreach ($this->allSwitches as $io => $each) {
                if ($each['ip'] == $ip) {
                    $result = $each['id'];
                    return($result);
                }
            }
        }
        return($result);
    }

    /**
     * Extracts OLT id from cache record name
     * 
     * @param string $cacheRecord
     * 
     * @return int
     */
    protected function extractOltId($cacheRecord) {
        $result = '';
        if (!empty($cacheRecord)) {
            $result = ubRouting::filters($cacheRecord, 'int');
        }
        return($result);
    }

    /**
     * Trying to get OLT IP for existing device by its ID
     * 
     * @param int $id
     * 
     * @return string
     */
    protected function getOltIp($id) {
        $result = '';
        if (isset($this->allSwitches[$id])) {
            $result .= $this->allSwitches[$id]['ip'];
        }
        return($result);
    }

    /**
     * Performs cache scanning and saving into archive of current PON devices FDB cache
     * 
     * @return void
     */
    protected function saveOltCache() {
        $newDate = curdatetime();
        if (@$this->altCfg['PON_ENABLED']) {
            $allCachedData = rcms_scandir(self::PATH_CACHE, '*' . self::EXT_OLTS);
            if (!empty($allCachedData)) {
                foreach ($allCachedData as $cacheIndex => $cacheFile) {
                    $rawData = file_get_contents(self::PATH_CACHE . $cacheFile);
                    if (!empty($rawData)) {
                        $oltId = $this->extractOltId($cacheFile);
                        $oltIp = $this->getOltIp($oltId);

                        //filling new archive record
                        $this->archive->data('date', $newDate);
                        $this->archive->data('devid', $oltId);
                        $this->archive->data('devip', $oltIp);
                        $this->archive->data('data', $rawData);
                        //we need some different parsing of raw data in this case
                        $this->archive->data('pon', '1');
                        $this->archive->create();
                    }
                }
            }
        }
    }

    /**
     * Performs cache scanning and saving into archive of current switches FDB cache
     * 
     * @return void
     */
    protected function saveSwitchesCache() {
        $newDate = curdatetime();
        $allCachedData = rcms_scandir(self::PATH_CACHE, '*' . self::EXT_SWITCHES);

        if (!empty($allCachedData)) {
            foreach ($allCachedData as $cacheIndex => $cacheFile) {
                $rawData = file_get_contents(self::PATH_CACHE . $cacheFile);
                if (!empty($rawData)) {
                    $switchIp = $this->extractSwitchIP($cacheFile);
                    $switchId = $this->getSwitchId($switchIp);
                    //filling new archive record
                    $this->archive->data('date', $newDate);
                    $this->archive->data('devid', $switchId);
                    $this->archive->data('devip', $switchIp);
                    $this->archive->data('data', $rawData);
                    $this->archive->data('pon', '0');
                    $this->archive->create();
                }
            }
        }
    }

    /**
     * Performs cache scanning and storing into archive
     * 
     * @return void
     */
    public function storeArchive() {
        $this->saveSwitchesCache();
        if ($this->altCfg['PON_ENABLED']) {
            $this->saveOltCache();
        }
        $this->rotateArchive();
    }

    /**
     * Performs automatic archived data rotation
     * 
     * @return void
     */
    protected function rotateArchive() {
        if ($this->daysRotate) {
            $this->archive->whereRaw("`date` <= NOW() - INTERVAL " . $this->daysRotate . " DAY");
            $this->archive->delete();
        }
    }

    /**
     * Renders archive container
     * 
     * @return string
     */
    public function renderArchive() {
        $result = '';
        $macFilter = '';
        $switchIdFilter = '';
        if (ubRouting::checkGet('macfilter')) {
            $macFilter .= '&macfilter=' . ubRouting::get('macfilter');
        }

        if (ubRouting::checkGet('switchidfilter')) {
            $macFilter .= '&switchidfilter=' . ubRouting::get('switchidfilter');
        }

        $columns = array('Date', __('Switch') . ' / ' . __('OLT'), 'Port', 'Location', 'MAC', __('User') . ' / ' . __('Device'));
        $opts = '"order": [[ 0, "desc" ]]';
        $result .= wf_JqDtLoader($columns, self::URL_ME . '&ajax=true' . $macFilter . $switchIdFilter, false, 'Objects', 100, $opts);
        return($result);
    }

    /**
     * Trying to detect is device switch/user or ONU by mac. Returns profile view control.
     * 
     * @param string $mac
     * 
     * @return string
     */
    protected function getEntityControl($mac) {
        $result = '';
        if (isset($this->allUsersMac[$mac])) {
            $userLogin = $this->allUsersMac[$mac];
            $result .= wf_Link(self::URL_USERPROFILE . $userLogin, web_profile_icon() . ' ' . @$this->allUserData[$userLogin]['fulladress']);
            return($result);
        }

        if (isset($this->allSwitchesMac[$mac])) {
            $switchId = $this->allSwitchesMac[$mac];
            $switchIcon = wf_img('skins/menuicons/switches.png', __('Switch')) . ' ';
            $result .= wf_Link(self::URL_SWITCHPROFILE . $switchId, $switchIcon . @$this->allSwitches[$switchId]['location']);
            return($result);
        }

        if (isset($this->allOnuMac[$mac])) {
            $onuId = $this->allOnuMac[$mac];
            $onuIcon = wf_img('skins/switch_models.png', __('ONU')) . ' ';
            $onuAssignedUser = '';
            if (isset($this->allOnuUsers[$onuId])) {
                $onuUserLogin = $this->allOnuUsers[$onuId];
                if (isset($this->allUserData[$onuUserLogin])) {
                    $onuAssignedUser .= @$this->allUserData[$onuUserLogin]['fulladress'];
                }
            }
            $result .= wf_Link(self::URL_ONUPROFILE . $onuId, $onuIcon . $onuAssignedUser);
        }
        return($result);
    }

    /**
     * Trying to detect ONU device by mac. Returns profile view control.
     * 
     * @param string $onuMac
     * 
     * @return string
     */
    protected function getOnuHandle($onuMac) {
        $result = '';
        if (!empty($onuMac)) {
            if (isset($this->allOnuMac[$onuMac])) {
                $onuId = $this->allOnuMac[$onuMac];
                $result .= wf_Link(self::URL_ONUPROFILE . $onuId, $onuId);
            }
        }
        return($result);
    }

    /**
     * Parses archive raw data and stores data into instance json helper
     * 
     * @param array  $archiveRecord
     * @param string $macFilter
     * @param int    $switchIdFilter
     * 
     * @return void
     */
    protected function parseData($archiveRecord, $macFilter = '', $switchIdFilter = '') {
        if (!empty($archiveRecord)) {
            $recordDate = $archiveRecord['date'];
            $recordId = $archiveRecord['devid'];
            $recordIp = $archiveRecord['devip'];
            $switchIcon = wf_img('skins/menuicons/switches.png') . ' ';

            //normal switch data
            if ($archiveRecord['pon'] != 1) {
                $fdbData = @unserialize($archiveRecord['data']);
                if (!empty($fdbData)) {
                    foreach ($fdbData as $eachMac => $eachPort) {
                        $filtered = true;
                        //basic user MAC filtering
                        if ($macFilter) {
                            if ($eachMac != $macFilter) {
                                $filtered = false;
                            }
                        }
                        //filter records only from some switch ID
                        if ($switchIdFilter) {
                            if ($recordId != $switchIdFilter) {
                                $filtered = false;
                            }
                        }

                        if ($filtered) {
                            $switchLink = $switchIcon . ' ' . __('Not exists');
                            if (!empty($recordId)) {
                                $switchLink = wf_Link(self::URL_SWITCHPROFILE . $recordId, $switchIcon . @$this->allSwitches[$recordId]['location']);
                            }
                            $data[] = $recordDate;
                            $data[] = $recordIp;
                            $data[] = $eachPort;
                            $data[] = $switchLink;
                            $data[] = $eachMac;
                            $data[] = $this->getEntityControl($eachMac);

                            $this->json->addRow($data);
                            unset($data);
                        }
                    }
                }
            } else {
                $fdbData = @unserialize($archiveRecord['data']);
                if (!empty($fdbData)) {
                    foreach ($fdbData as $eachMacPon => $eachOnuData) {
                        $switchLink = $switchIcon . ' ' . __('Not exists');
                        if (!empty($recordId)) {
                            $switchLink = wf_Link(self::URL_SWITCHPROFILE . $recordId, $switchIcon . @$this->allSwitches[$recordId]['location']);
                        }

                        if (!empty($eachOnuData)) {
                            foreach ($eachOnuData as $eachOnuId => $onuFdb) {
                                $filtered = true;
                                //basic MAC filtering behind ONU
                                if ($macFilter) {
                                    if ($onuFdb['mac'] != $macFilter) {
                                        $filtered = false;
                                    }
                                }

                                //filter records only from some OLT ID
                                if ($switchIdFilter) {
                                    if ($recordId != $switchIdFilter) {
                                        $filtered = false;
                                    }
                                }

                                if ($filtered) {
                                    $data[] = $recordDate;
                                    $data[] = $recordIp;
                                    $data[] = $this->getOnuHandle($eachMacPon);
                                    $data[] = $switchLink;
                                    $data[] = $onuFdb['mac'];
                                    $data[] = $this->getEntityControl($eachMacPon);
                                    $this->json->addRow($data);
                                    unset($data);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Renders JSON data for background ajax requests
     * 
     * @return void
     */
    public function ajArchiveData() {
        if (!ubRouting::checkGet('macfilter') AND ! ubRouting::checkGet('switchidfilter')) {
            //ugly hack to prevent memory overusage.
            $this->archive->whereRaw("`date` >= DATE_SUB(NOW(),INTERVAL 4 HOUR)");
        }
        $allArchiveRecords = $this->archive->getAll();
        $macFilter = '';
        $switchIdFilter = '';
        if (ubRouting::checkGet('macfilter')) {
            $macFilter = ubRouting::get('macfilter');
        }
        if (ubRouting::checkGet('switchidfilter')) {
            $switchIdFilter = ubRouting::get('switchidfilter', 'int');
        }
        if (!empty($allArchiveRecords)) {
            $this->loadUserData();
            if ($this->altCfg['PON_ENABLED']) {
                $this->loadOnuData();
            }
            foreach ($allArchiveRecords as $io => $each) {
                $this->parseData($each, $macFilter, $switchIdFilter);
            }
        }
        $this->json->getJson();
    }

    /**
     * Renders basic navigation controls
     * 
     * @return string
     */
    public static function renderNavigationPanel() {
        $result = wf_Link(self::URL_CACHE, wf_img_sized('skins/fdbmacsearch.png', '', '16', '16') . ' ' . __('Current FDB cache'), false, 'ubButton') . ' ';
        $result .= wf_Link(self::URL_ME, wf_img('skins/fdbarchive.png', '', '16', '16') . ' ' . __('FDB') . ' ' . __('Archive'), false, 'ubButton') . ' ';
        if (ubRouting::checkGet('macfilter') OR ubRouting::checkGet('switchidfilter')) {
            $result .= wf_Link(self::URL_ME, wf_img('skins/icon_cleanup.png') . __('Cleanup') . ' ' . __('Filters'), false, 'ubButton') . ' ';
        }
        return($result);
    }

}
