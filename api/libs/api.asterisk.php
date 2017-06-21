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

    // Database's vars:
    private $connected;
    private $AsteriskDB;

    const URL_ME = '?module=asterisk';
    const CACHE_PATH = 'exports/';
    public function __construct () {
        $this->AsteriskLoadConf();
        $this->AsteriskLoadNumAliases();
        $this->AsteriskConnectDB();
    }

    /**
     * Load Asterisk config
     * 
     * @return array
     */
    protected function AsteriskLoadConf() {
        $this->config = $this->AsteriskGetConf();

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
            $cache = '1';
            zb_StorageSet('ASTERISK_CACHETIME', $cache);
        }

        $result['host'] = $host;
        $result['db'] = $db;
        $result['table'] = $table;
        $result['login'] = $login;
        $result['password'] = $password;
        $result['cachetime'] = $cache;
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
    protected function AsteriskQuery($query) {
        if ($this->connected) {
            $result = array();
            $result_query = $this->AsteriskDB->query($query, MYSQLI_USE_RESULT);
            while ($row = $result_query->fetch_assoc()) {
                $result[] = $row;
            }
            mysqli_free_result($result_query);
            $this->AsteriskDB->close();
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
        $inputs = '';
        if (! $this->connected) {
            $messages = new UbillingMessageHelper();
            $inputs .= $messages->getStyledMessage(__('Connection error for Asterisk Database'), 'error').wf_tag('br/', false);
        }
        $inputs.= wf_TextInput('newhost', __('Asterisk host'), $this->config['host'], true);
        $inputs.= wf_TextInput('newdb', __('Database name'), $this->config['db'], true);
        $inputs.= wf_TextInput('newtable', __('CDR table name'), $this->config['table'], true);
        $inputs.= wf_TextInput('newlogin', __('Database login'), $this->config['login'], true);
        $inputs.= wf_TextInput('newpassword', __('Database password'), $this->config['password'], true);
        $inputs.= wf_TextInput('newcachetime', __('Cache time'), $this->config['cachetime'], true);
        $inputs.= wf_Submit(__('Save'));
        $result = wf_Form("", "POST", $inputs, 'glamour');
        $result.= wf_BackLink(self::URL_ME);
        return ($result);
    }

    /**
     * Returns number aliases aka phonebook form
     * 
     * @return string 
     */
    public function AsteriskAliasesForm() {
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
    public function AsteriskUpdateConfig($newhost, $newdb, $newtable, $newlogin, $newpassword, $newcachetime = '1') {
        zb_StorageSet('ASTERISK_HOST', $newhost);
        zb_StorageSet('ASTERISK_DB', $newdb);
        zb_StorageSet('ASTERISK_TABLE', $newtable);
        zb_StorageSet('ASTERISK_LOGIN', $newlogin);
        zb_StorageSet('ASTERISK_PASSWORD', $newpassword);
        zb_StorageSet('ASTERISK_CACHETIME', $newcachetime);
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
        if (cfr('ASTERISK')) {
            $inputs.=wf_Link(self::URL_ME . '&config=true', wf_img('skins/icon_extended.png') . ' ' . __('Settings'), false, 'ubButton') . ' ';
        }
        $inputs.= wf_DatePickerPreset('datefrom', curdate()) . ' ' . __('From');
        $inputs.= wf_DatePickerPreset('dateto', curdate()) . ' ' . __('To');
        if (!isset($user_login)) {
            $inputs.= wf_Trigger('countnum', 'Показать самых назойливых', false);
        }
        $inputs.= wf_Submit(__('Show'));
        $result = wf_Form("", "POST", $inputs, 'glamour');
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
            $query_mobile_dop = "SELECT `login`,`content` FROM `cfitems` WHERE `typeid`='2'";
            $result_p = simple_queryall($query_phone);
            $result_m = simple_queryall($query_mobile);
            $result_md = simple_queryall($query_mobile_dop);

            foreach ($result_p as $data) {
                $result[$data['login']]['phone'] = substr($data['phone'], -10);
                $result_a[substr($data['phone'], -10)] = $data['login'];
            }
            foreach ($result_m as $data) {
                $result[$data['login']]['mobile'] = substr($data['mobile'], -10);
                $result_a[substr($data['mobile'], -10)] = $data['login'];
            }
            foreach ($result_md as $data) {
                $result[$data['login']]['dop_mob'] = substr($data['content'], -10);
                $result_a[substr($data['content'], -10)] = $data['login'];
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
     * Parse Asterisk RAW CDR data
     * 
     * @param string $data - raw CDR
     * 
     * @return void
     */
    //need review with real CDR data
    protected function AsteriskParseCDR($data) {
        $normalData = $data;
        $adcomments = new ADcomments('ASTERISK'); // minus one SQL query per call
        // only one instance of object required

        if (!empty($normalData)) {
            $totalTime = 0;
            $callsCounter = 0;
            $cells = wf_TableCell('#');
            $cells.= wf_TableCell(__('Time'));
            $cells.= wf_TableCell(__('From'));
            $cells.= wf_TableCell(__('Real Name'));
            $cells.= wf_TableCell(__('Address'));
            $cells.= wf_TableCell(__('To'));
            $cells.= wf_TableCell(__('Type'));
            $cells.= wf_TableCell(__('Status'));
            $cells.= wf_TableCell(__('Talk time'));
            if (wf_CheckPost(array('countnum')) and ! isset($user_login) and $_POST['countnum']) {
                $cells.= wf_TableCell(__('Назойливость'));
            } else {
                $cells.= wf_TableCell(__('Comments'));
            }

            $rows = wf_TableRow($cells, 'row1');

            foreach ($normalData as $io => $each) {

                if (isset($normalData[$io - 1]['src'])) {
                    if ($normalData[$io]['src'] == $normalData[$io - 1]['src'] and $normalData[$io - 1]['disposition'] == 'NO ANSWER' and $normalData[$io]['disposition'] != 'ANSWERED')
                        continue;
                    if ($normalData[$io]['src'] == $normalData[$io - 1]['src'] and $normalData[$io - 1]['dst'] == 'hangup')
                        continue;
                    if ($normalData[$io]['src'] == $normalData[$io - 1]['src'] and $normalData[$io - 1]['dst'] == 'musiconhold')
                        continue;
                }

                $callsCounter++;
                $AsteriskGetLoginByNumberAraySrc = array($this->AsteriskGetLoginByNumber($each['src']));
                foreach ($AsteriskGetLoginByNumberAraySrc as $data) {
                    $link_src = $data['link'];
                    $login = $data['login'];
                    $name_src = $data['name'];
                    $adres_src = $data['adres'];
                }
                $debugData = wf_tag('pre') . print_r($each, true) . wf_tag('pre', true);

                $startTime = $each['calldate'];
                //$startTime=  explode(' ', $each['calldate']);
                //@$startTime=$startTime[1];
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

                $cells = wf_TableCell(wf_modal($callsCounter, $callsCounter, $debugData, '', '500', '600'), '', '', 'sorttable_customkey="' . $callsCounter . '"');
                $cells.= wf_TableCell($sessionTimeStats, '', '', 'sorttable_customkey="' . $tmpTime . '"');
                $cells.= wf_TableCell($link_src);
                $cells.= wf_TableCell($name_src);
                $cells.= wf_TableCell($adres_src);

                $AsteriskGetLoginByNumberArayDst = array($this->AsteriskGetLoginByNumber($each['dst']));
                foreach ($AsteriskGetLoginByNumberArayDst as $data) {
                    $link_dst = $data['link'];
                    if (!empty($data['login'])) {
                        $login = $data['login'];
                    }
                }
                //$cells.=  wf_TableCell(zb_AsteriskGetNumAlias($each['dst']));
                $cells.= wf_TableCell($link_dst);

                $CallType = __('Dial');
                if (ispos($each['lastapp'], 'internal-caller-transfer')) {
                    $CallType = __('Call transfer');
                }

                $cells.= wf_TableCell($CallType);

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

                $cells.= wf_TableCell($statusIcon . ' ' . $callStatus);
                $speekTime = $each['billsec'];
                $totalTime = $totalTime + $each['billsec'];
                $speekTime = zb_AsteriskFormatTime($speekTime);

                $cells.= wf_TableCell($speekTime, '', '', 'sorttable_customkey="' . $each['billsec'] . '"');

                if (wf_CheckPost(array('countnum')) and ! isset($user_login) and $_POST['countnum']) {
                    $cells.= wf_TableCell(__($each['countnum']));
                } else {
                        $itemId = $each['uniqueid'] . $each['disposition']{0};

                        if ($adcomments->haveComments($itemId)) {
                            $link_text = wf_tag('center') . $adcomments->getCommentsIndicator($itemId) . wf_tag('br') . wf_tag('span', false, '', 'style="font-size:14px;color: black;"') . zb_CheckCommentsForUser('ASTERISK', $itemId) . wf_tag('span', true) . wf_tag('center', true);
                        } else {
                            $link_text = wf_tag('center') . __('Add comments') . wf_tag('center', true);
                        }
                    if (!empty($login)) {
                        $cells.= wf_TableCell(wf_Link(self::URL_ME . '&addComments=' . $itemId . '&username=' . $login . '#profileending', $link_text, false));
                    } else {
                        $cells.= wf_TableCell(wf_Link(self::URL_ME . '&addComments=' . $itemId . '&AsteriskWindow=1', $link_text, false));
                    }
                }

                $rows.= wf_TableRow($cells, 'row3');
            }

            $result = wf_TableBody($rows, '100%', '0', 'sortable');
            $result.=__('Time spent on calls') . ': ' . zb_AsteriskFormatTime($totalTime) . wf_tag('br');
            $result.=__('Total calls') . ': ' . $callsCounter;
            show_window('', $result);
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
    public function AsteriskGetCDR($from, $to, $user_login = '') {
        // Load needed function
        $this->AsteriskGetLoginByNumberQuery();
        $this->AsteriskGetUserAllRealnames();
        $this->AsteriskGetFulladdress();

        $from = mysql_real_escape_string($from);
        $to = mysql_real_escape_string($to);
        $asteriskTable = mysql_real_escape_string($this->config['table']);

        //caching
        $cacheUpdate = true;
        $cacheName = $from . $to;
        $cacheName = md5($cacheName);
        $cacheName = self::CACHE_PATH . 'ASTERISK_' . $cacheName;
        $cachetime = time() - ($this->config['cachetime'] * 60);

        if (file_exists($cacheName)) {
            if ((filemtime($cacheName) > $cachetime)) {
                $rawResult = file_get_contents($cacheName);
                $rawResult = unserialize($rawResult);
                $cacheUpdate = false;
            } else {
                $cacheUpdate = true;
            }
        } else {
            $cacheUpdate = true;
        }

        if (! empty($user_login)) {
//connect to Asterisk database and fetch some data
            $phone = $this->result_LoginByNumber[$user_login]['phone'];
            $mobile = $this->result_LoginByNumber[$user_login]['mobile'];
            $dop_mobile = $this->result_LoginByNumber[$user_login]['dop_mob'];

            if (!empty($phone) and empty($mobile) and empty($dop_mobile)) {
                $query = "select * from `" . $asteriskTable . "` where `calldate` BETWEEN '" . $from . " 00:00:00' AND '" . $to . " 23:59:59' AND (`src` LIKE '%" . $phone . "' OR `dst` LIKE '%" . $phone . "') AND `lastapp`='dial' ORDER BY `calldate` DESC";
            } elseif (!empty($mobile) and empty($phone) and empty($dop_mobile)) {
                $query = "select * from `" . $asteriskTable . "` where `calldate` BETWEEN '" . $from . " 00:00:00' AND '" . $to . " 23:59:59' AND (`src` LIKE '%" . $mobile . "' OR `dst` LIKE '%" . $mobile . "') AND `lastapp`='dial' ORDER BY `calldate` DESC";
            } elseif (!empty($dop_mobile) and empty($phone) and empty($mobile)) {
                $query = "select * from `" . $asteriskTable . "` where `calldate` BETWEEN '" . $from . " 00:00:00' AND '" . $to . " 23:59:59' AND (`src` LIKE '%" . $dop_mobile . "' OR `dst` LIKE '%" . $dop_mobile . "')  AND `lastapp`='dial' ORDER BY `calldate` DESC";
            } elseif (!empty($phone) and ! empty($mobile) and empty($dop_mobile)) {
                $query = "select * from `" . $asteriskTable . "` where `calldate` BETWEEN '" . $from . " 00:00:00' AND '" . $to . " 23:59:59' AND (`src` LIKE '%" . $phone . "' OR `dst` LIKE '%" . $phone . "' OR `src` LIKE '%" . $mobile . "' OR `dst` LIKE '%" . $mobile . "') AND `lastapp`='dial' ORDER BY `calldate` DESC";
            } elseif (!empty($phone) and ! empty($dop_mobile) and empty($mobile)) {
                $query = "select * from `" . $asteriskTable . "` where `calldate` BETWEEN '" . $from . " 00:00:00' AND '" . $to . " 23:59:59' AND (`src` LIKE '%" . $phone . "' OR `dst` LIKE '%" . $phone . "' OR `src` LIKE '%" . $dop_mobile . "' OR `dst` LIKE '%" . $dop_mobile . "') AND `lastapp`='dial' ORDER BY `calldate` DESC";
            } elseif (!empty($mobile) and ! empty($dop_mobile) and empty($phone)) {
                $query = "select * from `" . $asteriskTable . "` where `calldate` BETWEEN '" . $from . " 00:00:00' AND '" . $to . " 23:59:59' AND (`src` LIKE '%" . $mobile . "' OR `dst` LIKE '%" . $mobile . "' OR `src` LIKE '%" . $dop_mobile . "' OR `dst` LIKE '%" . $dop_mobile . "') AND `lastapp`='dial' ORDER BY `calldate` DESC";
            } elseif (!empty($phone) and ! empty($mobile) and ! empty($dop_mobile)) {
                $query = "select * from `" . $asteriskTable . "` where `calldate` BETWEEN '" . $from . " 00:00:00' AND '" . $to . " 23:59:59' AND (`src` LIKE '%" . $phone . "' OR `dst` LIKE '%" . $phone . "' OR `src` LIKE '%" . $mobile . "' OR `dst` LIKE '%" . $mobile . "' OR `src` LIKE '%" . $dop_mobile . "' OR `dst` LIKE '%" . $dop_mobile . "')  AND `lastapp`='dial' ORDER BY `calldate` DESC" ;
            }
            if (!empty($query)) {
                $rawResult = $this->AsteriskQuery($query);
                $cacheContent = serialize($rawResult);
            }
        } elseif (wf_CheckPost(array('countnum')) and ! isset($user_login)) {
            $query = "select *,count(`src`) as `countnum`  from `" . $asteriskTable . "` where `calldate` BETWEEN '" . $from . " 00:00:00' AND '" . $to . " 23:59:59' AND `lastapp`='dial' GROUP BY `src`";
            $rawResult = $this->AsteriskQuery($query);
            $cacheContent = serialize($rawResult);
        } elseif ($cacheUpdate and ! isset($user_login)) {
            $query = "select * from `" . $asteriskTable . "` where `calldate` BETWEEN '" . $from . " 00:00:00' AND '" . $to . " 23:59:59'  AND `lastapp`='dial' ORDER BY `calldate` DESC";
            $rawResult = $this->AsteriskQuery($query);
            $cacheContent = serialize($rawResult);
            file_put_contents($cacheName, $cacheContent);
        }

        // Check for rawResult
        if (!empty($rawResult)) {
            //here is data parsing
            $this->AsteriskParseCDR($rawResult);
        } elseif (empty($rawResult) and ! $this->connected) {
            rcms_redirect(self::URL_ME . '&config=true');
        } else {
            show_error(__('Empty reply received'));
        }
    }

}

?>
