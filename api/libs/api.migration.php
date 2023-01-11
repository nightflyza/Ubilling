<?php

/**
 * And again one more database interconnection abstraction class
 */
class simpleOverlay {

    /**
     * Placeholder for DB driver.
     * 
     * @var string
     */
    protected $databaseDriver = '';

    /**
     * DB link object.
     * 
     * @var object
     */
    protected $databaseLink = '';

    public function __construct() {
        if (!extension_loaded('mysql')) {
            $this->databaseDriver = 'mysqli';
        } else {
            $this->databaseDriver = 'legacy';
        }
    }

    /**
     * Connect to MySQL using proper driver.
     * 
     * @param string $db_host
     * @param string $db_user
     * @param string $db_pass
     * @param string $db_name
     * 
     * @return object
     */
    public function connect($db_host, $db_user, $db_pass, $db_name) {

        if ($this->databaseDriver == 'mysqli') {
            $this->databaseLink = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
            if (!$this->databaseLink) {
                die("MySQL Connection error: " . mysqli_connect_error());
            }
            mysqli_set_charset($this->databaseLink, 'utf8');
        }
        if ($this->databaseDriver == 'legacy') {
            $this->databaseLink = mysql_connect($db_host, $db_user, $db_pass);
            if (!$this->databaseLink) {
                die("MySQL Connection error: " . mysql_error());
            }
            mysql_select_db($db_name);
            mysql_set_charset('utf8');
        }

        return $this->databaseLink;
    }

    /**
     * Close DB connection using proper driver.
     * 
     * @param object $connection
     */
    public function close($connection) {

        if ($this->databaseDriver == 'mysqli') {
            mysqli_close($connection);
        }
        if ($this->databaseDriver == 'legacy') {
            mysql_close($connection);
        }
    }

    /**
     * Escated unwanted chars.
     * 
     * @param string $string
     * @return string Escaped string
     */
    public function escapeString($string) {

        if ($this->databaseDriver == 'mysqli') {
            return mysqli_real_escape_string($this->databaseLink, $string);
        }
        if ($this->databaseDriver == 'legacy') {
            return mysql_real_escape_string($string);
        }
    }

    /**
     * Fetching data from DB.
     * 
     * @param string $query
     * @return array
     */
    public function simple_queryall($query) {
        $result = array();
        if ($this->databaseDriver == 'mysqli') {
            $queried = mysqli_query($this->databaseLink, $query) or die('wrong data input: ' . $query);
            while ($row = mysqli_fetch_assoc($queried)) {
                $result[] = $row;
            }
        }
        if ($this->databaseDriver == 'legacy') {
            $queried = mysql_query($query) or die('wrong data input: ' . $query);
            while ($row = mysql_fetch_assoc($queried)) {
                $result[] = $row;
            }
        }
        return($result);
    }

}

/**
 * Mikbill migration class
 */
class mikbill {

    /**
     * Placeholder for DB object.
     * 
     * @var object
     */
    protected $dbLoader = '';

    /**
     * Placeholder for string that should be fixed or at least try to fix.
     * 
     * @var string
     */
    protected $stringToFix = '';

    /**
     *
     * @var string
     */
    protected $stringFixed = '';

    /**
     *
     * @var array
     */
    protected $loginToFix = '';

    /**
     * Stores fixed login string.
     * 
     * @var string
     */
    protected $fixedLogin = '';

    /**
     * Placeholder for avarice.
     * 
     * @var object
     */
    protected $beggar = '';
    protected $usersData = array();
    protected $freezedData = array();
    protected $blockedData = array();
    protected $tariffsData = array();
    protected $cityData = array();
    protected $streetData = array();
    protected $housesData = array();
    protected $netsData = array();
    protected $loginPoint = '';
    protected $passwordPoint = '';
    protected $gridPoint = '';
    protected $ipPoint = '';
    protected $macPoint = '';
    protected $cashPoint = '';
    protected $downPoint = '';
    protected $realnamePoint = '';
    protected $tariffPoint = '';
    protected $speedPoint = '';
    protected $phonePoint = '';
    protected $mobilePoint = '';
    protected $addressPoint = '';

    public function __construct() {
        $this->greed = new Avarice();
        $this->beggar = $this->greed->runtime('MIKMIGR');
        $this->loginPoint = $this->beggar['INF']['login'];
        $this->passwordPoint = $this->beggar['INF']['password'];
        $this->gridPoint = $this->beggar['INF']['grid'];
        $this->ipPoint = $this->beggar['INF']['ip'];
        $this->macPoint = $this->beggar['INF']['mac'];
        $this->cashPoint = $this->beggar['INF']['cash'];
        $this->downPoint = $this->beggar['INF']['down'];
        $this->realnamePoint = $this->beggar['INF']['realname'];
        $this->tariffPoint = $this->beggar['INF']['tariff'];
        $this->speedPoint = $this->beggar['INF']['speed'];
        $this->phonePoint = $this->beggar['INF']['phone'];
        $this->mobilePoint = $this->beggar['INF']['mobile'];
        $this->addressPoint = $this->beggar['INF']['address'];

        $this->dbLoader = new simpleOverlay();
        ini_set('max_execution_time', 1800);
    }

    /**
     * 
     * @param type $string         
     */
    public function translit($string) {
        $result = zb_TranslitString($string);
        return (str_replace(array(' ', '*'), '_', $result));
    }

    /**
     * Fix string encoding when broken while convertion to utf8.     
     */
    private function fixEncode($string) {
        $replace_array[9557] = 'і';
        $replace_array[9570] = 'Е';
        $replace_array[9572] = 'І';
        $replace_array[9555] = 'є';
        $replace_array[9558] = 'ї';

        $array = preg_split('//u', $string, null, PREG_SPLIT_NO_EMPTY);
        foreach ($array as &$each) {
            $converted = $this->_uniord($each);
            if (isset($replace_array[$converted])) {
                $each = $replace_array[$converted];
            }
        }

        $string = implode("", $array);
        $result = strtr($string, $replace_array);

        return ($result);
    }

    protected function fixLogin() {
        $this->loginToFix = strtolower($this->loginToFix);
        $this->loginToFix = str_replace('-', '', $this->loginToFix);
        $this->fixedLogin = trim($this->loginToFix);
    }

    /**
     * Returns char's byte number.
     * 
     * @param type $c
     * @return boolean|int
     */
    private function _uniord($c) {
        if (ord($c[0]) >= 0 && ord($c[0]) <= 127)
            return ord($c[0]);
        if (ord($c[0]) >= 192 && ord($c[0]) <= 223)
            return (ord($c[0]) - 192) * 64 + (ord($c[1]) - 128);
        if (ord($c[0]) >= 224 && ord($c[0]) <= 239)
            return (ord($c[0]) - 224) * 4096 + (ord($c[1]) - 128) * 64 + (ord($c[2]) - 128);
        if (ord($c[0]) >= 240 && ord($c[0]) <= 247)
            return (ord($c[0]) - 240) * 262144 + (ord($c[1]) - 128) * 4096 + (ord($c[2]) - 128) * 64 + (ord($c[3]) - 128);
        if (ord($c[0]) >= 248 && ord($c[0]) <= 251)
            return (ord($c[0]) - 248) * 16777216 + (ord($c[1]) - 128) * 262144 + (ord($c[2]) - 128) * 4096 + (ord($c[3]) - 128) * 64 + (ord($c[4]) - 128);
        if (ord($c[0]) >= 252 && ord($c[0]) <= 253)
            return (ord($c[0]) - 252) * 1073741824 + (ord($c[1]) - 128) * 16777216 + (ord($c[2]) - 128) * 262144 + (ord($c[3]) - 128) * 4096 + (ord($c[4]) - 128) * 64 + (ord($c[5]) - 128);
        if (ord($c[0]) >= 254 && ord($c[0]) <= 255)    //  error
            return FALSE;
        return 0;
    }

    public function web_MikbillMigrationNetworksForm() {

        $period = array('day' => __('day'), 'month' => __('month'));

        $inputs = wf_TextInput('db_user', __('Database user'), '', true, 20);
        $inputs .= wf_TextInput('db_pass', __('Database password'), '', true, 20);
        $inputs .= wf_TextInput('db_host', __('Database host'), '', true, 20);
        $inputs .= wf_TextInput('db_name', __('Database name'), 'mikbill', true, 20);
        $inputs .= wf_Selector('tariff_period', $period, __('Tariff period'), '', true);
        $inputs .= wf_CheckInput('login_as_pass', __('Use login as password'), true, false);
        $inputs .= wf_CheckInput('contract_as_uid', __('Use contract same as UID'), true, false);
        $inputs .= wf_delimiter();

        $inputs .= wf_Submit(__('Send'));
        $form = wf_Form("", 'POST', $inputs, 'glamour');
        return($form);
    }

    public function web_MikbillMigrationNetnumForm() {
        $inputs = wf_TextInput('netnum', __('networks number'), '', true, 20);
        $inputs .= wf_Submit(__('Save'));
        $form = wf_Form("", 'POST', $inputs, 'glamour');
        return($form);
    }

    protected function get_netid($user_arr, $your_networks) {
        $net_id = array();
        foreach ($user_arr as $each_user => $io) {
            $ip = $io[$this->beggar['INF']['ip']];
            $id = $io[$this->beggar['INF']['id']];
            $usr_split = explode(".", $ip);
            if (isset($usr_split[1])) {
                $ip = $usr_split[0] . '.' . $usr_split[1] . '.' . $usr_split[2];
                foreach ($your_networks as $each_net => $ia) {
                    if ($ip == $ia['net']) {
                        $net_id[$id] = $each_net + 1;
                    }
                }
            }
        }
        return($net_id);
    }

    protected function get_lastcityid() {
        $query = "SELECT * FROM `city` ORDER BY `id` DESC LIMIT 1";
        $data = simple_query($query);
        $result = $data['id'];
        if (empty($result)) {
            return 1;
        }
        return $result;
    }

    protected function get_laststreetid() {
        $query = "SELECT * FROM `street` ORDER BY `id` DESC LIMIT 1";
        $data = simple_query($query);
        $result = $data['id'];
        if (empty($result)) {
            return 1;
        }
        return $result;
    }

    protected function get_lasthouseid() {
        $query = "SELECT * FROM `build` ORDER BY `id` DESC LIMIT 1";
        $data = simple_query($query);
        $result = $data['id'];
        if (empty($result)) {
            return 1;
        }
        return $result;
    }

    protected function get_aptid() {
        $query = "SELECT * FROM `apt` ORDER BY `id` DESC LIMIT 1";
        $data = simple_query($query);
        $result = $data['id'];
        if (empty($result)) {
            return 1;
        }
        return $result;
    }

    protected function get_aptnum($buildid) {
        $query = "SELECT * FROM `apt` WHERE buildid='" . $buildid . "'";
        $data = simple_query($query);
        $result = $data['apt'] + 1;
        if (empty($result)) {
            return 1;
        }
        return $result;
    }

    protected function cidr_match($ip, $network, $cidr) {
        if ((ip2int($ip) & ~((1 << (32 - $cidr)) - 1) ) == ip2int($network)) {
            return true;
        }
        return false;
    }

    protected function loadDbData($db_host, $db_user, $db_pass, $db_name) {
        $db_link = $this->dbLoader->connect($db_host, $db_user, $db_pass, $db_name);

// sql queries to find needed data
        $users = $this->beggar['INF']['users'];
        $tariffs = $this->beggar['INF']['tariffs'];
        $freezed = 'SELECT * FROM `usersfreeze`';
        $blocked = 'SELECT * FROM `usersblok`';
        $city = "SELECT * FROM `lanes_settlements`";
        $street = "SELECT * FROM `lanes`";
        $houses = "SELECT * FROM `lanes_houses`";
        $nets = "(SELECT DISTINCT SUBSTRING_INDEX(`local_ip`,'.',3) AS `net` FROM `users`) UNION (SELECT DISTINCT SUBSTRING_INDEX(`framed_ip`,'.',3) AS `net` FROM `users`)";

//sql data
        $this->usersData = $this->dbLoader->simple_queryall($users);
        $this->freezedData = $this->dbLoader->simple_queryall($freezed);
        $this->blockedData = $this->dbLoader->simple_queryall($blocked);
        $this->tariffsData = $this->dbLoader->simple_queryall($tariffs);
        $this->cityData = $this->dbLoader->simple_queryall($city);
        $this->streetData = $this->dbLoader->simple_queryall($street);
        $this->housesData = $this->dbLoader->simple_queryall($houses);
        $this->netsData = $this->dbLoader->simple_queryall($nets);
        $this->dbLoader->close($db_link);

        if (!($db_config = @parse_ini_file('config/' . 'mysql.ini'))) {
            print('Cannot load mysql configuration');
            exit;
        }

        $this->dbLoader->connect($db_config['server'], $db_config['username'], $db_config['password'], $db_config['db']);
    }

    protected function initIncrement($tablename = '') {
        $j = 0;

        if (!empty($tablename)) {
            $j = simple_get_lastid('nethosts');
        }
        if (empty($j)) {
            $j = 0;
        }
        return $j;
    }

    public function ConvertMikBill($db_user, $db_pass, $db_host, $db_name, $tariff_period, $login_as_pass, $contract_as_uid) {

        $this->loadDbData($db_host, $db_user, $db_pass, $db_name);
        //eval($beggar['INF']['text']);

        $user_arr = array();
        $i = 0;
        $new_city_data = array();
        $new_street_data = array();
        $new_house_data = array();
        $allIP = array();
        $duplicateIP = array();

        $net_counts = count($this->netsData);

        $j = $this->initIncrement('nethosts');
        foreach ($this->usersData as $eachuser => $io) {
            $this->loginToFix = $io[$this->beggar['DAT']['login']];
            $this->fixLogin();
            $user_arr[$this->fixedLogin][$this->loginPoint] = $this->fixedLogin; //0
            $user_arr[$this->fixedLogin][$this->passwordPoint] = $io[$this->beggar['DAT']['password']]; //1
            $user_arr[$this->fixedLogin][$this->gridPoint] = $io[$this->beggar['DAT']['grid']];  //2
            if ($io['real_ip']) {
                $eachIp = $io['framed_ip'];
            } else {
                $eachIp = $io['local_ip'];
            }
            $user_arr[$this->fixedLogin][$this->ipPoint] = $eachIp; //3
            $user_arr[$this->fixedLogin][$this->macPoint] = $io[$this->beggar['DAT']['mac']]; //4
            $user_arr[$this->fixedLogin][$this->cashPoint] = $io[$this->beggar['DAT']['cash']]; //5
            $user_arr[$this->fixedLogin][$this->downPoint] = $io[$this->beggar['DAT']['down']]; //6                        
            $user_arr[$this->fixedLogin][$this->realnamePoint] = $this->fixEncode($io[$this->beggar['DAT']['realname']]);  //7
            foreach ($this->tariffsData as $eachtariff => $ia) {
                if ($io[$this->gridPoint] == $ia[$this->beggar['DAT']['grid']]) {
                    $user_arr[$this->fixedLogin][$this->tariffPoint] = $ia[$this->beggar['DAT']['tariff']]; //8
                    $user_arr[$this->fixedLogin][$this->speedPoint] = $ia[$this->beggar['DAT']['speed']]; //9
                }
            }
            $user_arr[$this->fixedLogin][$this->beggar['INF']['id']] = $this->beggar['UDATA'] + $j++;  //10
            $user_arr[$this->fixedLogin][$this->phonePoint] = $io[$this->beggar['DAT']['phone']]; //11
            $user_arr[$this->fixedLogin][$this->mobilePoint] = $io[$this->beggar['DAT']['mobile']]; //12                        
            $user_arr[$this->fixedLogin][$this->addressPoint] = $this->fixEncode($io[$this->beggar['DAT']['address']]); //13
            $user_arr[$this->fixedLogin]['buildid'] = $io['houseid'];
            $user_arr[$this->fixedLogin]['aptnum'] = $io['app'];
            $user_arr[$this->fixedLogin]['note'] = $this->fixEncode($io['prim']);
            $user_arr[$this->fixedLogin]['credit'] = $io['credit'];
            $user_arr[$this->fixedLogin]['entrance'] = $io['porch'];
            $user_arr[$this->fixedLogin]['floor'] = $io['floor'];
            $user_arr[$this->fixedLogin]['freeze'] = 0;
            $user_arr[$this->fixedLogin]['uid'] = $io['uid'];
            $allIP[$eachIp] = $this->fixedLogin;
        }

        foreach ($this->blockedData as $eachuser => $io) {
            $this->stringToFix = $io[$this->beggar['DAT']['login']];
            $this->fixLogin();
            if ($io['real_ip']) {
                $eachIp = $io['framed_ip'];
            } else {
                $eachIp = $io['local_ip'];
            }
            if (!isset($allIP[$eachIp])) {
                $user_arr[$this->fixedLogin][$this->loginPoint] = $this->fixedLogin; //0
                $user_arr[$this->fixedLogin][$this->passwordPoint] = $io[$this->beggar['DAT']['password']]; //1
                $user_arr[$this->fixedLogin][$this->gridPoint] = $io[$this->beggar['DAT']['grid']];  //2
                $user_arr[$this->fixedLogin][$this->ipPoint] = $eachIp; //3
                $user_arr[$this->fixedLogin][$this->macPoint] = $io[$this->beggar['DAT']['mac']]; //4
                $user_arr[$this->fixedLogin][$this->cashPoint] = $io[$this->beggar['DAT']['cash']]; //5
                $user_arr[$this->fixedLogin][$this->downPoint] = 1; //6
                $user_arr[$this->fixedLogin][$this->realnamePoint] = $this->fixEncode($io[$this->beggar['DAT']['realname']]);  //7
                foreach ($this->tariffsData as $eachtariff => $ia) {
                    if ($io[$this->gridPoint] == $ia[$this->beggar['DAT']['grid']]) {
                        $user_arr[$this->fixedLogin][$this->tariffPoint] = $ia[$this->beggar['DAT']['tariff']]; //8
                        $user_arr[$this->fixedLogin][$this->speedPoint] = $ia[$this->beggar['DAT']['speed']]; //9
                    }
                }
                $user_arr[$this->fixedLogin][$this->beggar['INF']['id']] = $this->beggar['UDATA'] + $j++;  //10
                $user_arr[$this->fixedLogin][$this->phonePoint] = $io[$this->beggar['DAT']['phone']]; //11
                $user_arr[$this->fixedLogin][$this->mobilePoint] = $io[$this->beggar['DAT']['mobile']]; //12
                $user_arr[$this->fixedLogin][$this->addressPoint] = $this->fixEncode($io[$this->beggar['DAT']['address']]); //13
                $user_arr[$this->fixedLogin]['buildid'] = $io['houseid'];
                $user_arr[$this->fixedLogin]['aptnum'] = $io['app'];
                $user_arr[$this->fixedLogin]['note'] = $this->fixEncode($io['prim']);
                $user_arr[$this->fixedLogin]['credit'] = $io['credit'];
                $user_arr[$this->fixedLogin]['entrance'] = $io['porch'];
                $user_arr[$this->fixedLogin]['floor'] = $io['floor'];
                $user_arr[$this->fixedLogin]['freeze'] = 1;
                $user_arr[$this->fixedLogin]['uid'] = $io['uid'];
                $allIP[$eachIp] = $this->fixedLogin;
            } else {
                $duplicateIP[$this->fixedLogin] = $allIP[$eachIp];
            }
        }

        foreach ($this->freezedData as $eachuser => $io) {
            $this->stringToFix = $io[$this->beggar['DAT']['login']];
            $this->fixLogin();
            if ($io['real_ip']) {
                $eachIp = $io['framed_ip'];
            } else {
                $eachIp = $io['local_ip'];
            }

            if (!isset($allIP[$eachIp])) {
                $user_arr[$this->fixedLogin][$this->loginPoint] = $this->fixedLogin; //0
                $user_arr[$this->fixedLogin][$this->passwordPoint] = $io[$this->beggar['DAT']['password']]; //1
                $user_arr[$this->fixedLogin][$this->gridPoint] = $io[$this->beggar['DAT']['grid']];  //2
                $user_arr[$this->fixedLogin][$this->ipPoint] = $eachIp; //3
                $user_arr[$this->fixedLogin][$this->macPoint] = $io[$this->beggar['DAT']['mac']]; //4
                $user_arr[$this->fixedLogin][$this->cashPoint] = $io[$this->beggar['DAT']['cash']]; //5
                $user_arr[$this->fixedLogin][$this->downPoint] = $io[$this->beggar['DAT']['down']]; //6
                $user_arr[$this->fixedLogin][$this->realnamePoint] = $this->fixEncode($io[$this->beggar['DAT']['realname']]);  //7
                foreach ($this->tariffsData as $eachtariff => $ia) {
                    if ($io[$this->gridPoint] == $ia[$this->beggar['DAT']['grid']]) {
                        $user_arr[$this->fixedLogin][$this->tariffPoint] = $ia[$this->beggar['DAT']['tariff']]; //8
                        $user_arr[$this->fixedLogin][$this->speedPoint] = $ia[$this->beggar['DAT']['speed']]; //9
                    }
                }
                $user_arr[$this->fixedLogin][$this->beggar['INF']['id']] = $this->beggar['UDATA'] + $j++;  //10
                $user_arr[$this->fixedLogin][$this->phonePoint] = $io[$this->beggar['DAT']['phone']]; //11
                $user_arr[$this->fixedLogin][$this->mobilePoint] = $io[$this->beggar['DAT']['mobile']]; //12
                $user_arr[$this->fixedLogin][$this->addressPoint] = $this->fixEncode($io[$this->beggar['DAT']['address']]); //13
                $user_arr[$this->fixedLogin]['buildid'] = $io['houseid'];
                $user_arr[$this->fixedLogin]['aptnum'] = $io['app'];
                $user_arr[$this->fixedLogin]['note'] = $this->fixEncode($io['prim']);
                $user_arr[$this->fixedLogin]['credit'] = $io['credit'];
                $user_arr[$this->fixedLogin]['entrance'] = $io['porch'];
                $user_arr[$this->fixedLogin]['floor'] = $io['floor'];
                $user_arr[$this->fixedLogin]['freeze'] = 1;
                $user_arr[$this->fixedLogin]['uid'] = $io['uid'];
                $allIP[$eachIp] = $this->fixedLogin;
            } else {
                $duplicateIP[$this->fixedLogin] = $allIP[$eachIp];
            }
        }

        $val = array_keys($user_arr);
        $val = array_unique($val);

        $user_count = count($user_arr);

//creating table users
        fpc_start($this->beggar['DUMP'], "users");
        foreach ($user_arr as $eachUser => $io) {

            $login = $io[$this->loginPoint];
            if ($login_as_pass) {
                $password = $io[$this->loginPoint];
            } else {
                $password = $io[$this->passwordPoint];
            }
            $ip = $io[$this->ipPoint];
            $cash = $io[$this->cashPoint];
            $down = $io[$this->downPoint];
            $tariff = $this->translit($io[$this->tariffPoint]);
            $credit = $io['credit'];
            $freeze = $io['freeze'];
            if ($i < ($user_count - 1)) {
                file_put_contents($this->beggar['DUMP'], "('" . $login . "','" . $password . "',$freeze,$down,1,1,'" . $tariff . "','','','','','',''," . $credit . ", '', '', '', '', '', '', '', '', '', '', '', 0, '" . $ip . "', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $cash, 0, 0, 0, 86400, 1441152420, ''), ", FILE_APPEND);
                $i++;
            } else {
                file_put_contents($this->beggar['DUMP'], "('" . $login . "', '" . $password . "', $freeze, $down, 1, 1, '" . $tariff . "', '', '', '', '', '', '', " . $credit . ", '', '', '', '', '', '', '', '', '', '', '', 0, '" . $ip . "', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, $cash, 0, 0, 0, 86400, 1441152420, '');\n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "users");

//creating table tariffs
        $tariffs_count = count($this->tariffsData);
        $i = $this->initIncrement();
        fpc_start($this->beggar['DUMP'], "tariffs");
        foreach ($this->tariffsData as $eachtariff => $io) {

            $tariff_name = $this->translit($io['packet']);
            $fee = $io['fixed_cost'];
            if ($i < ($tariffs_count - 1)) {
                file_put_contents($this->beggar['DUMP'], "('" . $tariff_name . "', 0, 0, 0, 0, 0, '0:0-0:0', 1, 1, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, $fee, 0, 'up+down', '" . $tariff_period . "'),\n", FILE_APPEND);
                $i++;
            } else {
                file_put_contents($this->beggar['DUMP'], "('" . $tariff_name . "', 0, 0, 0, 0, 0, '0:0-0:0', 1, 1, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, $fee, 0, 'up+down', '" . $tariff_period . "');\n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "tariffs");

//create table contracts
        $i = $this->initIncrement();
        fpc_start($this->beggar['DUMP'], "contracts");
        foreach ($user_arr as $eachUser => $io) {

            $login = $io[$this->loginPoint];
            if ($contract_as_uid) {
                $contract = $io['uid'];
            } else {
                $contract = $login;
            }
            $id = $io[$this->beggar['INF']['id']];
            if ($i < ($user_count - 1)) {
                file_put_contents($this->beggar['DUMP'], "($id, '" . $login . "', '" . $contract . "'),\n", FILE_APPEND);
                $i++;
            } else {
                file_put_contents($this->beggar['DUMP'], "($id, '" . $login . "', '" . $contract . "');\n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "contracts");

//create table networks
        $i = $this->initIncrement();
        $j = $this->initIncrement();
        fpc_start($this->beggar['DUMP'], "networks");
        //foreach ($your_networks as $each_net => $io) {
        foreach ($this->netsData as $each_net => $io) {

            $start_ip = $io['net'] . '.0';
            $last_ip = $io['net'] . '.254';
            $net = $io['net'] . '.0/24';
            $net_type = 'dhcpstatic';
            $radius = 0;
            $j += $this->beggar['UDATA'];
            if ($i < ($net_counts - 1)) {
                file_put_contents($this->beggar['DUMP'], "($j, '" . $start_ip . "', '" . $last_ip . "', '" . $net . "', '" . $net_type . "', $radius),\n", FILE_APPEND);
                $i++;
            } else {
                file_put_contents($this->beggar['DUMP'], "($j, '" . $start_ip . "', '" . $last_ip . "', '" . $net . "', '" . $net_type . "', $radius);\n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "networks");

//create table nethosts	
        $i = $this->initIncrement();
        $net_id = $this->get_netid($user_arr, $this->netsData);
        fpc_start($this->beggar['DUMP'], "nethosts");
        foreach ($user_arr as $each_user => $io) {

            $login = $io[$this->loginPoint];
            $ip = $io[$this->ipPoint];
            $mac = strtolower($io[$this->macPoint]);
            $id = $io[$this->beggar['INF']['id']];
            if ($i < ($user_count - 1)) {
                if (!isset($net_id[$id])) {
                    //echo $login . '<br />';
                } else {
                    file_put_contents($this->beggar['DUMP'], "($id, $net_id[$id], '" . $ip . "', '" . $mac . "', 'NULL'),\n", FILE_APPEND);
                }
                $i++;
            } else {
                if (!isset($net_id[$id])) {
                    $net_id[$id] = 1;
                }
                file_put_contents($this->beggar['DUMP'], "($id, $net_id[$id], '" . $ip . "', '" . $mac . "', 'NULL'); \n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "nethosts");


        //create table phones
        $i = $this->initIncrement();
        fpc_start($this->beggar['DUMP'], "phones");
        foreach ($user_arr as $each_user => $io) {

            $login = $io[$this->loginPoint];
            $id = $io[$this->beggar['INF']['id']];
            $phone = $io[$this->phonePoint];
            $mobile = $io[$this->mobilePoint];
            if ($i < ($user_count - 1)) {
                file_put_contents($this->beggar['DUMP'], "($id, '" . $login . "', '" . $phone . "', '" . $mobile . "'),\n", FILE_APPEND);
                $i++;
            } else {
                file_put_contents($this->beggar['DUMP'], "($id, '" . $login . "', '" . $phone . "', '" . $mobile . "');\n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "phones");

//create table services
        $i = $this->initIncrement();
        fpc_start($this->beggar['DUMP'], "services");
        foreach ($this->netsData as $each_net => $io) {
            $t_net_id = $each_net + 1;
            if ($i < ($net_counts - 1)) {
                file_put_contents($this->beggar['DUMP'], "($t_net_id, $t_net_id, '" . $t_net_id . "'),\n ", FILE_APPEND);
                $i++;
            } else {
                file_put_contents($this->beggar['DUMP'], "($t_net_id, $t_net_id, '" . $t_net_id . "');\n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "services");

        //create table realname
        $i = $this->initIncrement();
        fpc_start($this->beggar['DUMP'], "realname");
        foreach ($user_arr as $each_user => $io) {

            $login = $io[$this->loginPoint];
            $id = $io[$this->beggar['INF']['id']];
            $search[] = "'";
            $search[] = "\\";
            $search[] = "/";
            $fio = str_replace($search, '', $io[$this->realnamePoint]);
            if ($i < ($user_count - 1)) {
                file_put_contents($this->beggar['DUMP'], "($id, '" . $login . "', '" . $fio . "'), ", FILE_APPEND);
                $i++;
            } else {
                file_put_contents($this->beggar['DUMP'], "($id, '" . $login . "', '" . $fio . "'); \n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "realname");

//create table speeds
        $i = $this->initIncrement();
        fpc_start($this->beggar['DUMP'], "speeds");
        foreach ($this->tariffsData as $eachtariff => $io) {
            $tariff_name = $this->translit($io['packet']);
            $tariff_speed = $io['speed_rate'];
            if ($i < ($tariffs_count - 1)) {
                file_put_contents($this->beggar['DUMP'], "(NULL, '" . $tariff_name . "', '" . $tariff_speed . "', '" . $tariff_speed . "', NULL, NULL, NULL, NULL), \n", FILE_APPEND);
                $i++;
            } else {
                file_put_contents($this->beggar['DUMP'], "(NULL, '" . $tariff_name . "', '" . $tariff_speed . "', '" . $tariff_speed . "', NULL, NULL, NULL, NULL); \n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "speeds");

//create table userspeeds
        $i = $this->initIncrement();
        fpc_start($this->beggar['DUMP'], "userspeeds");
        foreach ($user_arr as $each_user => $io) {

            $login = $io[$this->loginPoint];
            $id = $io[$this->beggar['INF']['id']];
            if ($i < ($user_count - 1)) {
                file_put_contents($this->beggar['DUMP'], "($id, '" . $login . "', 0),\n", FILE_APPEND);
                $i++;
            } else {
                file_put_contents($this->beggar['DUMP'], "($id, '" . $login . "', 0);\n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "userspeeds");

//create table notes for addresses
        $i = $this->initIncrement();
        $j = $this->initIncrement('nethosts');
        if (empty($j)) {
            $j = 0;
        }
        fpc_start($this->beggar['DUMP'], "notes");
        foreach ($user_arr as $each_user => $io) {

            $login = $io[$this->loginPoint];
            $note = $this->dbLoader->escapeString($io['note']);
            $j += $this->beggar['UDATA'];
            if ($i < ($user_count - 1)) {
                file_put_contents($this->beggar['DUMP'], "($j, '" . $login . "', '" . $note . "'),\n", FILE_APPEND);
                $i++;
            } else {
                file_put_contents($this->beggar['DUMP'], "($j, '" . $login . "', '" . $note . "');\n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "notes");

        //city and address section
        $i = $this->initIncrement();
        $j = $this->get_lastcityid();
        $city_count = count($this->cityData);
        fpc_start($this->beggar['DUMP'], "city");
        foreach ($this->cityData as $index => $eachCity) {
            $city_name = $this->fixEncode($eachCity['settlementname']);
            $j += $this->beggar['UDATA'];
            $new_city_data[$eachCity['settlementid']] = $j;
            if ($i < ($city_count - 1)) {
                file_put_contents($this->beggar['DUMP'], "($j, '" . $city_name . "', ''),\n", FILE_APPEND);
                $i++;
            } else {
                file_put_contents($this->beggar['DUMP'], "($j, '" . $city_name . "', '');\n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "city");

        $i = $this->initIncrement();
        $j = $this->get_laststreetid();
        $street_count = count($this->streetData);
        fpc_start($this->beggar['DUMP'], "street");
        foreach ($this->streetData as $index => $eachStreet) {
            $street_name = $this->fixEncode(str_replace($search, '', $eachStreet['lane']));
            $settlementid = $eachStreet['settlementid'];
            $city_id = $new_city_data[$settlementid];
            $j += $this->beggar['UDATA'];
            $new_street_data[$eachStreet['laneid']] = $j;
            if ($i < ($street_count - 1)) {
                file_put_contents($this->beggar['DUMP'], "($j, $city_id, '" . $street_name . "', ''),\n", FILE_APPEND);
                $i++;
            } else {
                file_put_contents($this->beggar['DUMP'], "($j, $city_id, '" . $street_name . "', '');\n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "street");

        $i = $this->initIncrement();
        $j = $this->get_lasthouseid();
        $house_count = count($this->housesData);
        fpc_start($this->beggar['DUMP'], "build");
        foreach ($this->housesData as $index => $eachHouse) {
            $build_num = $eachHouse['house'];
            $street_id = $new_street_data[$eachHouse['laneid']];
            $j += $this->beggar['UDATA'];
            $new_house_data[$eachHouse['houseid']] = $j;
            if ($i < ($house_count - 1)) {
                file_put_contents($this->beggar['DUMP'], "( $j, $street_id, '" . $build_num . "', NULL),\n", FILE_APPEND);
                $i++;
            } else {
                file_put_contents($this->beggar['DUMP'], "($j, $street_id, '" . $build_num . "', NULL);\n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "build");



        $i = $this->initIncrement();
        $j = $this->get_aptid();
        fpc_start($this->beggar['DUMP'], "apt");
        foreach ($user_arr as $each_user => $io) {

            $build_id = str_replace($search, '', $new_house_data[$io['buildid']]);
            $j += $this->beggar['UDATA'];
            $addr[$io[$this->loginPoint]] = $j;
            if (empty($io['aptnum'])) {
                $io['aptnum'] = 0;
            }
            if ($i < ($user_count - 1)) {
                file_put_contents($this->beggar['DUMP'], "($j, $build_id, '" . $io['entrance'] . "', '" . $io['floor'] . "', '" . $io['aptnum'] . "'),\n", FILE_APPEND);
                $i++;
            } else {
                file_put_contents($this->beggar['DUMP'], "($j, $build_id, '" . $io['entrance'] . "', '" . $io['floor'] . "', '" . $io['aptnum'] . "');\n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "apt");

        $i = $this->initIncrement();
        fpc_start($this->beggar['DUMP'], "address");
        foreach ($user_arr as $each_user => $io) {

            $j += $this->beggar['UDATA'];
            if ($i < ($user_count - 1)) {
                file_put_contents($this->beggar['DUMP'], "(NULL, '" . $io[$this->loginPoint] . "', " . $addr[$io[$this->loginPoint]] . "),\n", FILE_APPEND);
                $i++;
            } else {
                file_put_contents($this->beggar['DUMP'], "(NULL, '" . $io[$this->loginPoint] . "', " . $addr[$io[$this->loginPoint]] . ");\n", FILE_APPEND);
            }
        }
        fpc_end($this->beggar['DUMP'], "address");

        return $duplicateIP;
    }

}
