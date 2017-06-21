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

    // Database's vars:
    private $connected;
    private $AsteriskDB;

    const URL_ME = '?module=asterisk';

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
     * Load Asterisk CDR
     * 
     * @param string $from - start date
     * @param string $to  - end date
     * 
     * @return void
     */
    public function AsteriskLoadCDR($from, $to) {
        return ($this->AsteriskGetCDR($from, $to));
    }

    /**
     * Gets Asterisk CDR data from database and manage cache
     * 
     * @param string $from - start date
     * @param string $to  - end date
     * 
     * @return void
     */
    protected function AsteriskGetCDR($from, $to) {
        $from = mysql_real_escape_string($from);
        $to = mysql_real_escape_string($to);
        $asteriskTable = mysql_real_escape_string($this->config['table']);
        $cachePath = 'exports/';

//caching
        $cacheUpdate = true;
        $cacheName = $from . $to;
        $cacheName = md5($cacheName);
        $cacheName = $cachePath . $cacheName . '.asterisk';
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

        if (isset($user_login)) {
//connect to Asterisk database and fetch some data
            $phonesQueryData = zb_LoginByNumberQuery(); // why? why use this callback three times?
            $phone = $phonesQueryData[$user_login][0];
            $mobile = $phonesQueryData[$user_login][1];
            $dop_mobile = $phonesQueryData[$user_login][2];

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
            zb_AsteriskParseCDR($rawResult);
        } elseif (empty($rawResult) and ! $this->connected) {
            rcms_redirect(self::URL_ME . '&config=true');
        } else {
            show_error(__('Empty reply received'));
        }
    }

}

?>
