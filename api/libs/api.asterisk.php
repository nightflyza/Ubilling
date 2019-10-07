<?php

class Asterisk {

    /**
     * Contains Ubstorage data for Asterisk as key=>value
     *
     * @var array
     */
    protected $config = array();

    /**
     * Contains Ubstorage data for Number Alias Asterisk as key=>value
     *
     * @var array
     */
    protected $NumAliases = array();

    /**
     * Contains Login and mobiles from MySQL Databases as login=>data
     *
     * @var array
     */
    protected $result_LoginByNumber;

    /**
     * Contains mobiles and Login from MySQL Databases as Number=>Login
     *
     * @var array
     */
    protected $result_NumberLogin;

    /**
     *
     *
     * @var array
     */
    protected $allrealnames ;

    /**
     *
     *
     * @var array
     */
    protected $alladdress ;

    /**
     * Contains system mussages object placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Comments caching time
     *
     * @var int
     */
    protected $cacheTime = ''; //month by default

    /**
     * System alter.ini config as key=>value
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Contains default recorded calls path
     *
     * @var string
     */
    protected $recordingsPath = '';

    /**
     * Determines whether to use CEL table and contains CEL table name
     *
     * @var bool
     */
    protected $recordingsCELTab = '';

    /**
     * Contains default recorded files file extensions
     *
     * @var string
     */
    protected $recordingsFormat = '';

    // Database's vars:
    private $connected;
    private $AsteriskDB;

    const URL_ME = '?module=asterisk';
    const CACHE_PATH = 'exports/';
    public function __construct () {
        $this->loadAlter();
        $this->initMessages();
        $this->AsteriskLoadConf();
        $this->AsteriskLoadNumAliases();
        $this->AsteriskConnectDB();
        $this->initCache();
    }

    /**
     * Loads system alter config into private property for further usage
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
        $this->recordingsPath = ($ubillingConfig->getAlterParam('ASTERISK_CALLRECS_PATH')) ? $ubillingConfig->getAlterParam('ASTERISK_CALLRECS_PATH') : '';
        $this->recordingsCELTab = ($ubillingConfig->getAlterParam('ASTERISK_CALLRECS_CEL_TAB_NAME')) ? $ubillingConfig->getAlterParam('ASTERISK_CALLRECS_CEL_TAB_NAME') : '';
        $this->recordingsFormat = ($ubillingConfig->getAlterParam('ASTERISK_CALLRECS_FORMAT')) ? $ubillingConfig->getAlterParam('ASTERISK_CALLRECS_FORMAT') : '';
    }

    /**
     * Load Asterisk config
     * 
     * @return array
     */
    protected function AsteriskLoadConf() {
        $this->config = $this->AsteriskGetConf();
        $this->cacheTime = $this->config['cachetime'];

    }

    /**
     * Inits system messages helper object for further usage
     * 
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Initalizes system cache object for further usage
     * 
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Check for last cache data and if need clean
     * 
     * @return void
     */
    protected function AsterikCacheInfoClean($asteriskTable, $from, $to) {
        if (!empty($from) and !empty($to)) {
            $query = "select uniqueid from `" . $asteriskTable . "` where `calldate` BETWEEN '" . $from . " 00:00:00' AND '" . $to . " 23:59:59'  AND `lastapp`='dial' ORDER BY `calldate` DESC LIMIT 1";
            $cacheName = $from . $to;
            $cache_uniqueid_key = 'ASTERISK_UNI_' . $cacheName;
            $last_db_uniqueid = $this->AsteriskQuery($query);
            $last_cache_uniqueid = $this->cache->get($cache_uniqueid_key, $this->cacheTime);
            // Если `uniqueid` не равен записи в кеше, то очищаем весь кеш
            if (($uniqueid = @$last_db_uniqueid['0']['uniqueid']) != $last_cache_uniqueid) {
                $this->cache->delete('ASTERISK_CDR_' . $cacheName, $this->cacheTime);
                $this->cache->set($cache_uniqueid_key, $uniqueid, $this->cacheTime);
            }
        }
    }

    /**
     * Gets Asterisk config from DB, or sets default values
     * 
     * @return array
     */
    protected function AsteriskGetConf() {
        $result = array();
        //getting url
        $host = zb_StorageGet('ASTERISK_HOST');
        if (empty($host)) {
            $host = 'localhost';
            zb_StorageSet('ASTERISK_HOST', $host);
        }
        //getting login
        $login = zb_StorageGet('ASTERISK_LOGIN');
        if (empty($login)) {
            $login = 'asterisk';
            zb_StorageSet('ASTERISK_LOGIN', $login);
        }

        //getting DB name
        $db = zb_StorageGet('ASTERISK_DB');
        if (empty($db)) {
            $db = 'asteriskdb';
            zb_StorageSet('ASTERISK_DB', $db);
        }
        //getting CDR table name
        $table = zb_StorageGet('ASTERISK_TABLE');
        if (empty($table)) {
            $table = 'cdr';
            zb_StorageSet('ASTERISK_TABLE', $table);
        }

        //getting password
        $password = zb_StorageGet('ASTERISK_PASSWORD');
        if (empty($password)) {
            $password = 'password';
            zb_StorageSet('ASTERISK_PASSWORD', $password);
        }
        //getting caching time
        $cache = zb_StorageGet('ASTERISK_CACHETIME');
        if (empty($cache)) {
            $cache = '2592000';
            zb_StorageSet('ASTERISK_CACHETIME', $cache);
        }        
        //getting caching time
        $dopmobile = zb_StorageGet('ASTERISK_DOPMOBILE');
        if (empty($dopmobile)) {
            $dopmobile = '';
            zb_StorageSet('ASTERISK_DOPMOBILE', $dopmobile);
        }

        $result['host'] = $host;
        $result['db'] = $db;
        $result['table'] = $table;
        $result['login'] = $login;
        $result['password'] = $password;
        $result['cachetime'] = $cache;
        $result['dopmobile'] = $dopmobile;
        return ($result);
    }

    /**
    * Initialises connection with Asterisk database server and selects needed db
     *
     * @param MySQL Connection Id $connection
     * @return MySQLDB
     */
    protected function AsteriskConnectDB() {
        $this->AsteriskDB = new mysqli($this->config['host'], $this->config['login'], $this->config['password'], $this->config['db']);
        if (!$this->AsteriskDB->connect_error) {
            $this->connected = TRUE;
            return $this->connected;
        } else {
            $this->connected = FALSE;
            return $this->connected;
        }
    }

    /**
     * Another database query execution
     * 
     * @param string $query - query to execute
     * 
     * @return array
     */
    public function AsteriskQuery($query) {
        if ($this->connected) {
            $result = array();
            $result_query = $this->AsteriskDB->query($query, MYSQLI_USE_RESULT);
            while ($row = $result_query->fetch_assoc()) {
                $result[] = $row;
            }
            mysqli_free_result($result_query);
            //$this->AsteriskDB->close();
            return ($result);
        } else {
            $result = rcms_redirect(self::URL_ME . '&config=true');
            return ($result);
        }

    }

    /**
     * Load numbers aliases
     * 
     * @return array
     */
    protected function AsteriskLoadNumAliases() {
        $this->NumAliases = $this->AsteriskGetNumAliases();
    }

    /**
     * Get numbers aliases from database, or set default empty array
     * 
     * @return array
     */
    protected function AsteriskGetNumAliases() {
        $result = array();
        $rawAliases = zb_StorageGet('ASTERISK_NUMALIAS');
        if (empty($rawAliases)) {
            $newAliasses = serialize($result);
            $newAliasses = base64_encode($newAliasses);
            zb_StorageSet('ASTERISK_NUMALIAS', $newAliasses);
        } else {
            $readAlias = base64_decode($rawAliases);
            $readAlias = unserialize($readAlias);
            $result = $readAlias;
        }
        return ($result);
    }

    /**
     * Returns Asterisk module configuration form
     * 
     * @return string
     */
    public function AsteriskConfigForm() {
        $result = wf_BackLink(self::URL_ME, '', true);
        $result.= wf_tag('br');

        if (cfr('ASTERISKCONF')) {
            $inputs = '';
            if (! $this->connected) {
                $inputs .= $this->messages->getStyledMessage(__('Connection error for Asterisk Database'), 'error').wf_tag('br/', false);
            }
            $inputs.= wf_TextInput('newhost', __('Asterisk host'), $this->config['host'], true);
            $inputs.= wf_TextInput('newdb', __('Database name'), $this->config['db'], true);
            $inputs.= wf_TextInput('newtable', __('CDR table name'), $this->config['table'], true);
            $inputs.= wf_TextInput('newlogin', __('Database login'), $this->config['login'], true);
            $inputs.= wf_TextInput('newpassword', __('Database password'), $this->config['password'], true);
            $inputs.= wf_TextInput('newcachetime', __('Cache time'), $this->config['cachetime'], true);
            $inputs.= wf_TextInput('dopmobile', __('Additional mobile - Profile field ID'), $this->config['dopmobile'], true);
            $inputs.= wf_Submit(__('Save'));
            $result.= wf_Form("", "POST", $inputs, 'glamour');
        } else {
            $result = $this->messages->getStyledMessage(__('Access denied'), 'error');
        }
        return ($result);
    }

    /**
     * Returns number aliases aka phonebook form
     * 
     * @return string 
     */
    public function AsteriskAliasesForm() {
        $result = '';
        if (cfr('ASTERISKALIAS')) {
            $createinputs = wf_TextInput('newaliasnum', __('Phone'), '', true);
            $createinputs.=wf_TextInput('newaliasname', __('Alias'), '', true);
            $createinputs.=wf_Submit(__('Create'));
            $createform = wf_Form('', 'POST', $createinputs, 'glamour');
            $result = $createform;

            if (!empty($this->NumAliases)) {
                $delArr = array();
                foreach ($this->NumAliases as $num => $eachname) {
                    $delArr[$num] = $num . ' - ' . $eachname;
                }
                $delinputs = wf_Selector('deletealias', $delArr, __('Delete alias'), '', false);
                $delinputs.= wf_Submit(__('Delete'));
                $delform = wf_Form('', 'POST', $delinputs, 'glamour');
                $result.= $delform;
            }
        } else {
            $result = $this->messages->getStyledMessage(__('Access denied'), 'error');
        }
        return ($result);
    }

    /**
     * Delete aliase for number on Ubstorage
     * 
     * @return string
     */
    public function AsteriskDeleteAlias($deleteAliasNum) {
        $newStoreAliases = $this->NumAliases;
        $deleteAliasNum = mysql_real_escape_string($deleteAliasNum);
        if (isset($newStoreAliases[$deleteAliasNum])) {
            unset($newStoreAliases[$deleteAliasNum]);
            $newStoreAliases = serialize($newStoreAliases);
            $newStoreAliases = base64_encode($newStoreAliases);
            zb_StorageSet('ASTERISK_NUMALIAS', $newStoreAliases);
            log_register("ASTERISK ALIAS DELETE `" . $deleteAliasNum . "`");
            rcms_redirect(self::URL_ME . '&config=true');
        }
    }

    /**
     * Create aliases for number on Ubstorage
     * 
     * @return string
     */
    public function AsteriskCreateAlias($newAliasNum, $newAliasName) {
        $newStoreAliases = $this->NumAliases;
        $newAliasNum = mysql_real_escape_string($newAliasNum);
        $newAliasName = mysql_real_escape_string($newAliasName);
        $newStoreAliases[$newAliasNum] = $newAliasName;
        $newStoreAliases = serialize($newStoreAliases);
        $newStoreAliases = base64_encode($newStoreAliases);
        zb_StorageSet('ASTERISK_NUMALIAS', $newStoreAliases);
        log_register("ASTERISK ALIAS ADD `" . $newAliasNum . "` NAME `" . $newAliasName . "`");
        rcms_redirect(self::URL_ME . '&config=true');
    }

    /**
     * Update parametrs for Asterisk configs on Ubstorage
     * 
     * @return string
     */
    public function AsteriskUpdateConfig($newhost, $newdb, $newtable, $newlogin, $newpassword, $newcachetime = '2592000', $dopmobile = '') {
        zb_StorageSet('ASTERISK_HOST', $newhost);
        zb_StorageSet('ASTERISK_DB', $newdb);
        zb_StorageSet('ASTERISK_TABLE', $newtable);
        zb_StorageSet('ASTERISK_LOGIN', $newlogin);
        zb_StorageSet('ASTERISK_PASSWORD', $newpassword);
        zb_StorageSet('ASTERISK_CACHETIME', ($newcachetime < 2592000) ? $newcachetime: 2592000);
        zb_StorageSet('ASTERISK_DOPMOBILE', $dopmobile);
        log_register('ASTERISK settings changed');
        rcms_redirect(self::URL_ME . '&config=true');
    }

    /**
     * Returns CDR date selection form
     * 
     * @return string
     */
    public function panel() {
        global $user_login;
        $inputs = '';
        if (isset($user_login)) {
            $inputs .= wf_BackLink(self::URL_ME, '', false);
        }
        if (cfr('ASTERISKCONF')) {
            $inputs.=wf_Link(self::URL_ME . '&config=true', wf_img('skins/icon_extended.png') . ' ' . __('Settings'), false, 'ubButton') . ' ';
        }
        $inputs.= wf_DatePickerPreset('datefrom', curdate()) . ' ' . __('From');
        $inputs.= wf_DatePickerPreset('dateto', curdate()) . ' ' . __('To');
        if (!isset($user_login)) {
            $inputs.= wf_Trigger('countnum', 'Показать самых назойливых', false);
        }
        $inputs.= wf_Submit(__('Show'));
        $result = wf_Form("", "POST", $inputs, 'glamour');
        if (! $this->connected) {
            $result .= $this->messages->getStyledMessage(__('Connection error for Asterisk Database'), 'error').wf_tag('br/', false);
        }
        return ($result);
    }

    /**
     * Get comment for user
     *
     * @param int $idComments - comment id
     *
     * @return string
     */
    protected function AsteriskGetCommentsForUser($idComments) {
            $query = "SELECT `text` from `adcomments` WHERE `scope`='ASTERISK' AND `item`='" . $idComments . "' ORDER BY `date` ASC LIMIT 1;";
            $result = simple_query($query);
            $comments = $result["text"];
            return ($comments);
    }

    /**
     * Get status switch for user
     *
     * @param int $login - user login
     *
     * @return string
     */
    protected function AsteriskGetSWStatus($login) {
        $alldeadswitches = zb_SwitchesGetAllDead();
        $query = "SELECT `login`,`ip` FROM `switchportassign` LEFT JOIN `switches` ON switchportassign.switchid=switches.id WHERE `login`='" . $login . "';";
        $result_q = simple_query($query);
        if (empty($result_q) ) {
            $result =  'ERROR: USER NOT HAVE SWITCH';
        } else {
            $result = isset($alldeadswitches[$result_q['ip']]) ? "DIE" : "OK";
        }
        return ($result);
    }

    /**
     * Get status switch and other for user, if his bumber have database. Use only in remote API.
     * 
     * @param int $number, $param
     * 
     * @return mixed
     */
    public function AsteriskGetInfoApi($number, $param) {
        $this->AsteriskGetLoginByNumberQuery();
        $number_cut = substr($number, -10);
        $login = @$this->result_NumberLogin[$number_cut];
        if (!empty($login)) {
            if ($param == "login") {
                $result = $login;
            } elseif ($param == "swstatus") {
                $result = $this->AsteriskGetSWStatus($login);
            } elseif ($param == "realname") {
                $this->AsteriskGetUserAllRealnames();
                $realname = @$this->allrealnames[$login];
                $realname = preg_replace('/[^a-zA-Zа-яА-Я0-9ё\d ]+/iu','',$realname);
                $realname = zb_TranslitString($realname, TRUE);
                $result = $login . "-" . $realname;
            } else {
                $result = 'ERROR: MISTAKE PARAMETR';
            }
        } else {
            $result = 'ERROR: NOT OUR USER';
        }
        return ($result);
    }

    /**
     * Converts per second time values to human-readable format
     * 
     * @param int $seconds - time interval in seconds
     * 
     * @return string
     */
    protected function AsteriskFormatTime($seconds) {
        $init = $seconds;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;

        if ($init < 3600) {
            //less than 1 hour
            if ($init < 60) {
                //less than minute
                $result = $seconds . ' ' . __('sec.');
            } else {
                //more than one minute
                $result = $minutes . ' ' . __('minutes') . ' ' . $seconds . ' ' . __('seconds');
            }
        } else {
            //more than hour
            $result = $hours . ' ' . __('hour') . ' ' . $minutes . ' ' . __('minutes') . ' ' . $seconds . ' ' . __('seconds');
        }
        return ($result);
    }

    /**
     * Gets Login by caller number from DB
     * 
     * @return array('number'=>login))
     */
    protected function AsteriskGetLoginByNumberQuery() {
        if (!isset($this->result_LoginByNumber) and empty($this->result_LoginByNumber)) {
            $result = array();
            $result_a = array();
            $query_phone = "SELECT `phones`.`login`,`phone` FROM `phones`";
            $query_mobile = "SELECT `phones`.`login`,`mobile` FROM `phones`";
            $result_p = simple_queryall($query_phone);
            $result_m = simple_queryall($query_mobile);

            foreach ($result_p as $data) {
                $result[$data['login']]['phone'] = substr($data['phone'], -10);
                $result_a[substr($data['phone'], -10)] = $data['login'];
            }
            foreach ($result_m as $data) {
                $result[$data['login']]['mobile'] = substr($data['mobile'], -10);
                $result_a[substr($data['mobile'], -10)] = $data['login'];
            }
            if ($this->config['dopmobile']) {
                $query_mobile_dop = "SELECT `login`,`content` FROM `cfitems` WHERE `typeid`='" . $this->config['dopmobile'] . "'";
                $result_md = simple_queryall($query_mobile_dop);

                foreach ($result_md as $data) {
                    $result[$data['login']]['dop_mob'] = substr($data['content'], -10);
                    $result_a[substr($data['content'], -10)] = $data['login'];
                }
            }
            if ($this->altCfg['MOBILES_EXT']) {
                $query_mobileext = "SELECT `login`,`mobile` as `mobileext` FROM `mobileext`";
                $result_me = simple_queryall($query_mobileext);
                foreach ($result_me as $data) {
                    $result[$data['login']]['mobileext'][] = substr($data['mobileext'], -10);
                    $result_a[substr($data['mobileext'], -10)] = $data['login'];
                }
            }
        }
        $this->result_LoginByNumber = $result;
        $this->result_NumberLogin = $result_a;
    }

    /**
     * Returns all of users realnames records as login=>realname array
     * 
     * @return void
     */
    protected function AsteriskGetUserAllRealnames() {
        $this->allrealnames = zb_UserGetAllRealnames();
    }

    /**
     * Returns user address by some user login
     * 
     * @return void
     */
    protected function AsteriskGetFulladdress() {
        $this->alladdress = zb_AddressGetFulladdresslistCached();
    }

    /**
     * Returns human readable alias from phone book by phone number
     * 
     * @param string $number - phone number
     * 
     * @return string
     */
    protected function AsteriskGetNumAlias($number) {
        if (!empty($this->NumAliases)) {
            if (isset($this->NumAliases[$number])) {
                return($number . ' - ' . $this->NumAliases[$number]);
            } else {
                return ($number);
            }
        } else {
            return ($number);
        }
    }

    /**
     * Gets Ubilling user login by number mobile
     * 
     * @param string $number - number
     * 
     * @return string
     */
    protected function AsteriskGetLoginByNumber($number) {

        if (strlen($number) == 13 or strlen(substr($number, -10)) == 10) {
            $number_cut = substr($number, -10);
            $user_by_number = @$this->result_NumberLogin[$number_cut];
            $result = array();
            if (!empty($user_by_number)) {
                $result['link'] = wf_Link('?module=userprofile&username=' . $user_by_number, $number, false);
                $result['login'] = $user_by_number;
                $result['name'] = @$this->allrealnames[$user_by_number];
                $result['adres'] = @$this->alladdress[$user_by_number];
                return ($result);
            } else {
                $result['link'] = $number;
                $result['login'] = '';
                $result['name'] = '';
                $result['adres'] = '';
                return ($result);
            }
        } else {
            $result['link'] = $this->AsteriskGetNumAlias($number);
            $result['login'] = '';
            $result['name'] = '';
            $result['adres'] = '';
            return ($result);
        }
    }

    /**
     * Gets Asterisk CDR data from database and manage cache
     * Load AsteriskGetLoginByNumberQuery,  AsteriskGetUserAllRealnames, AsteriskGetFulladdress
     * 
     * @param string $from - start date
     * @param string $to  - end date
     * @param string $user_login  - login if not empty
     * 
     * @return void
     */
    protected function AsteriskGetCDR() {
       $result = '';
        // Load needed function
        $this->AsteriskGetLoginByNumberQuery();
        $this->AsteriskGetUserAllRealnames();
        $this->AsteriskGetFulladdress();

        $from = isset($_GET['datefrom']) ? mysql_real_escape_string($_GET['datefrom']) : '2000-01-01';
        $to = isset($_GET['dateto']) ? mysql_real_escape_string($_GET['dateto']) : curdate();
        $asteriskTable = mysql_real_escape_string($this->config['table']);
        $user_login = isset($_GET['username']) ? vf($_GET['username']) : '';

        if (!empty($this->recordingsPath) and !empty($this->recordingsCELTab)) {
            $cel = $this->recordingsCELTab;
            $query_flds = $asteriskTable . ".*, " . $cel .  ".id, " . $cel . ".appname, SUBSTRING_INDEX(" . $cel . ".appdata, ',', 1) AS app_data ";
            $query_voice_join = " INNER JOIN " . $cel . " ON " . $asteriskTable . ".calldate = " . $cel . ".eventtime " .
                                            " AND " . $asteriskTable . ".cnum = " . $cel . ".cid_num " .
                                            " AND (lower(" . $cel . ".appname) = 'monitor' OR lower(" . $cel . ".appname) = 'mixmonitor') ";
        } else {
            $query_flds = ' * ';
            $query_voice_join = '';
        }

        if (! empty($user_login)) {
            //fetch some data from Asterisk database
            $phone = @$this->result_LoginByNumber[$user_login]['phone'];
            $mobile = @$this->result_LoginByNumber[$user_login]['mobile'];
            $mobileext_arr = @$this->result_LoginByNumber[$user_login]['mobileext'];
            $dop_mobile = @$this->result_LoginByNumber[$user_login]['dop_mob'];

            // Building a query to the database
            $where_part = '';
            $query = "select " . $query_flds . " from `" . $asteriskTable . "` " . $query_voice_join . " where `calldate` BETWEEN '" . $from . " 00:00:00' AND '" . $to . " 23:59:59' AND (";
            if (!empty($phone) AND empty($where_part)) {
                $where_part.= "`src` LIKE '%" . $phone . "' OR `dst` LIKE '%" . $phone . "'";
            } elseif (!empty($phone) AND !empty($where_part)) {
                $where_part.= " OR `src` LIKE '%" . $phone . "' OR `dst` LIKE '%" . $phone . "'";
            }
            if (!empty($mobile) AND empty($where_part)) {
                $where_part.= "`src` LIKE '%" . $mobile . "' OR `dst` LIKE '%" . $mobile . "'";
            } elseif (!empty($mobile) AND !empty($where_part)) {
                $where_part.= " OR `src` LIKE '%" . $mobile . "' OR `dst` LIKE '%" . $mobile . "'";
            }
            if (!empty($mobileext_arr) AND empty($where_part)) {
                foreach ($mobileext_arr as $id=>$mobileext) {
                    if ($id == 0) {
                    $where_part.= "`src` LIKE '%" . $mobileext . "' OR `dst` LIKE '%" . $mobileext . "'";
                    } else {
                        $where_part.= " OR `src` LIKE '%" . $mobileext . "' OR `dst` LIKE '%" . $mobileext . "'";
                    }
                }
            } elseif (!empty($mobileext_arr) AND !empty($where_part)) {
                foreach ($mobileext_arr as $mobileext) {
                    $where_part.= " OR `src` LIKE '%" . $mobileext . "' OR `dst` LIKE '%" . $mobileext . "'";
                }
            }
            if (!empty($dop_mobile) AND empty($where_part)) {
                $where_part.= "`src` LIKE '%" . $dop_mobile . "' OR `dst` LIKE '%" . $dop_mobile . "'";
            } elseif (!empty($dop_mobile) AND !empty($where_part)) {
                $where_part.= " OR `src` LIKE '%" . $dop_mobile . "' OR `dst` LIKE '%" . $dop_mobile . "'";
            }
            $query.= $where_part;
            $query.= ") AND `lastapp`='dial' ORDER BY `calldate` DESC";

            if (!empty($where_part)) {
                $rawResult = $this->AsteriskQuery($query);
            }
        } elseif (wf_CheckGet(array('countnum')) and  empty($user_login)) {
            $query = "select *,count(`src`) as `countnum`  from `" . $asteriskTable . "` where `calldate` BETWEEN '" . $from . " 00:00:00' AND '" . $to . " 23:59:59' AND `lastapp`='dial' GROUP BY `src`";
            $rawResult = $this->AsteriskQuery($query);
        } else {
            // check if need clean cache 
            $this->AsterikCacheInfoClean($asteriskTable, $from, $to);
            // Start check cache and get result
            $query = "select " . $query_flds . " from `" . $asteriskTable . "` " . $query_voice_join . " where `calldate` BETWEEN '" . $from . " 00:00:00' AND '" . $to . " 23:59:59'  AND `lastapp`='dial' ORDER BY `calldate` DESC";

            $obj = $this;
            $cacheName = $from . $to;
            $rawResult = $this->cache->getCallback('ASTERISK_CDR_' . $cacheName, function()  use ($query, $obj) {
                        return ($obj->AsteriskQuery($query));
                        }, $this->cacheTime);
        }

        // Check for rawResult
        if (!empty($rawResult)) {
            //here is data parsing
            $result = $rawResult;
        }

        return $result;
    }

    /**
     * Returns container of Asterisk
     *
     * @return string
     */
    public function renderAsteriskCDR() {
        $result = '';

        if (empty($this->recordingsPath)) {
            $columns = array('#', 'ID', 'Time', 'From', 'Real Name', 'Address', 'To', 'Type', 'Status', 'Talk time');
            $playerInit = '';
        } else {
            $columns = array('#', 'ID', 'Time', 'From', 'Real Name', 'Address', 'To', 'Type', 'Status', 'Talk time', 'Call recording');
            $playerInit = $this->initPlayer();
        }

        if (wf_CheckPost(array('countnum')) and ! isset($user_login) and $_POST['countnum']) {
            $columns[]= 'Назойливость';
            $countnum = '&countnum=true';
        } else {
            $columns[]= 'Comments';
            $countnum = '';
        }

        $from = isset($_POST['datefrom']) ? $_POST['datefrom'] : '2000-01-01';
        $to = isset($_POST['dateto']) ? $_POST['dateto'] : curdate();
        $user_login = isset($_GET['username']) ? '&username=' . vf($_GET['username']) : '';

        $opts = '"order": [[ 0, "asc" ]]';
        $result = $playerInit . wf_JqDtLoader($columns, '?module=asterisk&ajax=true&datefrom=' . $from  . '&dateto=' . $to . $user_login . $countnum, false, 'Calls', 100, $opts);
        return ($result);
    }

    /**
     * Parse Asterisk RAW CDR data
     *
     * @return string
     */
    public function ajaxAvaibleCDR() {
        $cdrData = $this->AsteriskGetCDR();
        $adcomments = new ADcomments('ASTERISK'); // minus one SQL query per call
        $json = new wf_JqDtHelper();

        if (!empty($cdrData)) {
            $totalTime = 0;
            $callsCounter = 0;
            $allVoiceFiles = (empty($this->recordingsPath)) ? array() : $this->getCallsDir();

            foreach ($cdrData as $io => $each) {
                if (isset($cdrData[$io - 1]['src'])) {
                    if ($cdrData[$io]['src'] == $cdrData[$io - 1]['src'] and $cdrData[$io - 1]['disposition'] == 'NO ANSWER' and $cdrData[$io]['disposition'] != 'ANSWERED')
                        continue;
                    if ($cdrData[$io]['src'] == $cdrData[$io - 1]['src'] and $cdrData[$io - 1]['dst'] == 'hangup')
                        continue;
                    if ($cdrData[$io]['src'] == $cdrData[$io - 1]['src'] and $cdrData[$io - 1]['dst'] == 'musiconhold')
                        continue;
                }
                $callsCounter++;
                $AsteriskGetLoginByNumberAraySrc = array($this->AsteriskGetLoginByNumber($each['src']));
                foreach ($AsteriskGetLoginByNumberAraySrc as $tempDataSrc) {
                    $link_src = $tempDataSrc['link'];
                    $login = $tempDataSrc['login'];
                    $name_src = $tempDataSrc['name'];
                    $adres_src = $tempDataSrc['adres'];
                }
                $AsteriskGetLoginByNumberArayDst = array($this->AsteriskGetLoginByNumber($each['dst']));
                foreach ($AsteriskGetLoginByNumberArayDst as $tempDataDst) {
                    $link_dst = $tempDataDst['link'];
                    if (!empty($tempDataDst['login'])) {
                        $login = $tempDataDst['login'];
                    }
                }

                $debugData = wf_tag('pre') . print_r($each, true) . wf_tag('pre', true);

                $startTime = $each['calldate'];
                $tmpTime = strtotime($each['calldate']);
                $endTime = $tmpTime + $each['duration'];
                $endTime = date("H:i:s", $endTime);
                $answerTime = $tmpTime + ($each['duration'] - $each['billsec']);
                $answerTime = date("H:i:s", $answerTime);
                $tmpStats = __('Taken up the phone') . ': ' . $answerTime . "\n";
                $tmpStats.=__('End of call') . ': ' . $endTime;
                $sessionTimeStats = wf_tag('abbr', false, '', 'title="' . $tmpStats . '"');
                $sessionTimeStats.=$startTime;
                $sessionTimeStats.=wf_tag('abbr', true);
                $callDirection = '';

                $CallType = __('Dial');
                if (ispos($each['lastapp'], 'internal-caller-transfer')) {
                    $CallType = __('Call transfer');
                }

                $callStatus = $each['disposition'];
                $statusIcon = '';
                if (ispos($each['disposition'], 'ANSWERED')) {
                    $callStatus = __('Answered');
                    $statusIcon = wf_img('skins/calls/phone_green.png');
                }
                if (ispos($each['disposition'], 'NO ANSWER')) {
                    $callStatus = __('No answer');
                    $statusIcon = wf_img('skins/calls/phone_red.png');
                }

                if (ispos($each['disposition'], 'BUSY')) {
                    $callStatus = __('Busy');
                    $statusIcon = wf_img('skins/calls/phone_yellow.png');
                }

                if (ispos($each['disposition'], 'FAILED')) {
                    $callStatus = __('Failed');
                    $statusIcon = wf_img('skins/calls/phone_fail.png');
                }

                $speekTime = $each['billsec'];
                $totalTime = $totalTime + $each['billsec'];
                $speekTime = $this->AsteriskFormatTime($speekTime);

                $data[] = $callsCounter;
                $data[] = wf_modal($callsCounter, $callsCounter, $debugData, '', '500', '600');
                $data[] = $sessionTimeStats;
                $data[] = $link_src;
                $data[] = $name_src;
                $data[] = $adres_src;
                $data[] = $link_dst;
                $data[] = $CallType;
                $data[] = $statusIcon . ' ' . $callStatus;
                $data[] = $speekTime;

                if (!empty($this->recordingsPath)) {
                    if (isset($each['app_data']) and in_array($each['app_data'], $allVoiceFiles)) {
                        $fileUrl = self::URL_ME . '&astercallrecs=' . urlencode($each['app_data']);
                        $data[] = $this->getSoundcontrols($fileUrl);
                    } elseif (isset($each['recordingfile']) and in_array($each['recordingfile'], $allVoiceFiles)) {
                        $fileUrl = self::URL_ME . '&astercallrecs=' . urlencode($each['recordingfile']);
                        $data[] = $this->getSoundcontrols($fileUrl);
                    } else {
                        $data[] = '';
                    }
                }

                if (wf_CheckGet(array('countnum')) and ! isset($user_login) and $_GET['countnum']) {
                    $data[]= $each['countnum'];
                } else {
                        $itemId = $each['uniqueid'] . $each['disposition'][0];

                        if ($adcomments->haveComments($itemId)) {
                            $link_text = wf_tag('center') . $adcomments->getCommentsIndicator($itemId) . wf_tag('br') . wf_tag('span', false, '', 'style="font-size:14px;color: black;"') . $this->AsteriskGetCommentsForUser($itemId) . wf_tag('span', true) . wf_tag('center', true);
                        } else {
                            $link_text = wf_tag('center') . __('Add comments') . wf_tag('center', true);
                        }
                    if (!empty($login)) {
                        $data[]= wf_Link(self::URL_ME . '&addComments=' . $itemId . '&username=' . $login . '#profileending', $link_text, false);
                    } else {
                        $data[]= wf_Link(self::URL_ME . '&addComments=' . $itemId . '&AsteriskWindow=1', $link_text, false);
                    }
                }
                $json->addRow($data);
                unset($data);
            }
        }

        $json->getJson();
    }

    /**
     * Inits gsm/wav player for further usage
     *
     * @return string
     */
    public function initPlayer() {
        $result = '';
        $result .= wf_tag('script', false, '', 'src="modules/jsc/wavplay/embed/domready.js"') . wf_tag('script', true);
        $result .= wf_tag('script', false, '', 'src="modules/jsc/wavplay/embed/swfobject.js"') . wf_tag('script', true);
        $result .= wf_tag('script', false, '', 'src="modules/jsc/wavplay/embed/tinywav.js"') . wf_tag('script', true);
        return ($result);
    }

    /**
     * Returns controls for some recorded call file
     *
     * @param string $fileUrl
     *
     * @return string
     */
    public function getSoundControls($fileUrl) {
        $result = '';
        if (!empty($fileUrl)) {
            $result .= wf_tag('a', false, '', 'onclick="try{window.TinyWav.Play(\'' . $fileUrl . '\')}catch(E){alert(E)}"') . wf_img('skins/play.png', __('Play')) . wf_tag('a', true) . ' ';
            $result .= wf_tag('a', false, '', 'onclick="try{window.TinyWav.Pause()}catch(E){alert(E)}"') . wf_img('skins/pause.png', __('Pause')) . wf_tag('a', true) . ' ';
            $result .= wf_tag('a', false, '', 'onclick="try{window.TinyWav.Resume()}catch(E){alert(E)}"') . wf_img('skins/continue.png', __('Continue')) . wf_tag('a', true) . ' ';
            $result .= wf_Link($fileUrl, wf_img('skins/icon_download.png', __('Download')));
        }

        return ($result);
    }

    /**
     * Catches file download
     *
     * @return void
     */
    public function catchFileDownload() {
        if (wf_CheckGet(array('astercallrecs'))) {
            zb_DownloadFile($this->recordingsPath . $_GET['astercallrecs'], 'default');
        }
    }

    /**
     * Returns available calls files array
     *
     * @return array
     */
    protected function getCallsDir() {
        $result = array();
        $exp = '/^' . str_replace('*', '(.*)', str_replace('.', '\\.', $this->recordingsFormat)) . '$/';

        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->recordingsPath));

        foreach ($rii as $file) {
            if (!$file -> isDir()) {
                if (!empty($this->recordingsFormat) and preg_match($exp, $file->getPathname())) {
                    $result[] = str_ireplace($this->recordingsPath, '', $file->getPathname());
                } else {
                    $result[] = str_ireplace($this->recordingsPath, '', $file->getPathname());
                }
            }
        }

        //$result = rcms_scandir($this->voicePath, $this->callsFormat, 'file');
        return ($result);
    }
}

?>
