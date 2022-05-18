<?php

/**
 * Returns user IP by password auth
 * 
 * @param string $login
 * @param string $password
 * @return string/void on error
 */
function zbs_UserCheckLoginAuth($login, $password) {
    $result = '';
    $login = vf($login);
    $login = preg_replace('#[^a-z0-9A-Z\-_\.]#Uis', '', $login);
    $login = preg_replace('/\0/s', '', $login);
    $password = vf($password);
    $password = preg_replace('#[^a-z0-9A-Z\-_\.]#Uis', '', $password);
    $password = preg_replace('/\0/s', '', $password);
    if (!empty($login) AND ( !empty($password))) {
        $query = "SELECT `IP` from `users` WHERE `login`='" . $login . "' AND MD5(`password`)='" . $password . "'";
        $data = simple_query($query);
        if (!empty($data)) {
            $result = $data['IP'];
        }
    }
    return ($result);
}

/**
 * Returns user IP or try identify it by login
 * 
 * @param bool $debug
 * @return string
 */
function zbs_UserDetectIp($debug = false) {
    $glob_conf = zbs_LoadConfig();
    $ip = '';

    //force REST API auth
    if (ubRouting::checkGet('uberlogin', 'uberpassword')) {
        $ip = zbs_UserCheckLoginAuth(vf(ubRouting::get('uberlogin')), vf(ubRouting::get('uberpassword')));
        if (!empty($ip)) {
            return($ip);
        } else {
            header('HTTP/1.1 401 Unauthorized', true, 401);
            die('ERROR_WRONG_UBERAUTH');
        }
    }

    //default auth method
    if ($glob_conf['auth'] == 'ip') {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    //password based auth
    if ($glob_conf['auth'] == 'login') {
        if ((isset($_COOKIE['ulogin'])) AND ( isset($_COOKIE['upassword']))) {
            $ulogin = trim(vf($_COOKIE['ulogin']));
            $upassword = trim(vf($_COOKIE['upassword']));
            $ip = zbs_UserCheckLoginAuth($ulogin, $upassword);
        }
    }

    //trying to find user by IP, than if failed - login based auth.
    if ($glob_conf['auth'] == 'both') {
        $authorizedByIp = false;
        $ipCheck = $_SERVER['REMOTE_ADDR'];
        $remoteUserLogin = zbs_UserGetLoginByIp($ipCheck);
        if ($remoteUserLogin) {
            if (isIpAuthAllowed($remoteUserLogin)) {
                $ip = $ipCheck;
                $authorizedByIp = true;
            }
        }

        if (!$authorizedByIp) {
            if ((isset($_COOKIE['ulogin'])) AND ( isset($_COOKIE['upassword']))) {
                $ulogin = trim(vf($_COOKIE['ulogin']));
                $upassword = trim(vf($_COOKIE['upassword']));
                $ip = zbs_UserCheckLoginAuth($ulogin, $upassword);
            }
        }
    }

    if ($debug) {
        //$ip = '172.30.0.2';
    }

    return($ip);
}

/**
 * Checks is user allowed for automated IP auth
 * 
 * @param string $login
 * 
 * @return bool
 */
function isIpAuthAllowed($login) {
    $result = true;
    $login = mysql_real_escape_string($login);
    $query = "SELECT * from `ipauth_denied` WHERE `login`='" . $login . "'";
    $denied = simple_query($query);
    if (!empty($denied)) {
        $result = false;
    }
    return($result);
}

/**
 * Returns user login by its IP address
 * 
 * @param string $ip
 * @return string
 */
function zbs_UserGetLoginByIp($ip) {
    $glob_conf = zbs_LoadConfig();
    $result = '';
    if (!empty($ip)) {
        $query = "SELECT `login` from `users` where `IP`='" . $ip . "'";
        $result = simple_query($query);
    }
    if (!empty($result)) {
        return($result['login']);
    } else {
        if ($glob_conf['auth'] == 'ip') {
            if ((isset($glob_conf['authfailredir']))) {
                if (!empty($glob_conf['authfailredir'])) {
                    rcms_redirect($glob_conf['authfailredir']);
                    die('Unknown user');
                } else {
                    die('Unknown user EX_EMPTY_AUTHFAILREDIR');
                }
            } else {
                die('Unknown user EX_NO_AUTHFAILREDIR_DEFINED');
            }
        }
    }
}

/**
 * Checks is table with some name exists, and returns int value 0/1 used as bool (Oo)
 * 
 * @param string $tablename
 * @return int
 */
function zbs_CheckTableExists($tablename) {
    $query = "SELECT CASE WHEN (SELECT COUNT(*) AS STATUS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = (SELECT DATABASE()) AND TABLE_NAME = '" . $tablename . "') = 1 THEN (SELECT 1)  ELSE (SELECT 0) END AS result;";
    $result = simple_query($query);
    return ($result['result']);
}

/**
 * Returns current stargazer DB version
 * =<2.408 - 0
 * >=2.409 - 1+
 * 
 * @return int
 */
function zbs_CheckDbSchema() {
    if (zbs_CheckTableExists('info')) {
        $query = "SELECT `version` from `info`";
        $result = simple_query($query);
        $result = $result['version'];
    } else {
        $result = 0;
    }
    return ($result);
}

/**
 * Returns user online left days
 * 
 * @param string $login existing users login
 * @param double $userBalance current users balance
 * @param string $userTariff users tariff
 * @param bool   $rawdays show only days count
 * @return string
 */
function zbs_GetOnlineLeftCount($login, $userBalance, $userTariff, $rawDays = false) {
    // DEFINE VARS:
    $us_config = zbs_LoadConfig();
    $tariffData = zbs_UserGetTariffData($userTariff);
    if (empty($tariffData)) {
        //user have no tariff
        $tariffData['name'] = '*_NO_TARIFF_*';
        $tariffData['Fee'] = 0;
        $tariffData['period'] = 'month';
    }
    $tariffFee = $tariffData['Fee'];
    $tariffPeriod = isset($tariffData['period']) ? $tariffData['period'] : 'month';
    $includeVServices = (!empty($us_config['ONLINELEFT_CONSIDER_VSERVICES']));
    $vservicesPeriodON = (!empty($us_config['VSERVICES_CONSIDER_PERIODS']));

    $daysOnLine = 0;
    $balanceExpire = '';
    $totalVsrvPrice = 0;

    if ($userBalance >= 0) {
        // check if tariff has no zero price and is not free
        if ($tariffFee > 0) {
            if ($includeVServices) {
                $totalVsrvPrice = ($vservicesPeriodON) ? zbs_vservicesGetUserPricePeriod($login, $tariffPeriod) : zbs_vservicesGetUserPrice($login);
                $tariffFee += $totalVsrvPrice;
            }

            //spread fee
            if ($us_config['ONLINELEFT_SPREAD'] != 0) {
                if ($tariffPeriod == 'month') {
                    //monthly period
                    while ($userBalance >= 0) {
                        $daysOnLine++;
                        $dayFee = $tariffFee / date('t', time() + ($daysOnLine * 24 * 60 * 60));
                        $userBalance = $userBalance - $dayFee;
                    }
                } else {
                    //daily period
                    while ($userBalance >= 0) {
                        $daysOnLine++;
                        $userBalance = $userBalance - $tariffFee;
                    }
                }
            } else {
                //non spread fee
                if ($tariffPeriod == 'month') {
                    //monthly non spread fee
                    while ($userBalance >= 0) {
                        $daysOnLine = $daysOnLine + date('t', time() + ($daysOnLine * 24 * 60 * 60)) - date('d', time() + ($daysOnLine * 24 * 60 * 60)) + 1;
                        $userBalance = $userBalance - $tariffFee;
                    }
                } else {
                    //daily non spread fee
                    while ($userBalance >= 0) {
                        $daysOnLine++;
                        $userBalance = $userBalance - $tariffFee;
                    }
                }
            }
        }

        // STYLING OF THE RESULT:
        switch ($us_config['ONLINELEFT_STYLE']) {
            case 'days':
                $balanceExpire = ", " . __('enought for') . ' ' . $daysOnLine . ' ' . __('days');
                break;
            case 'date':
                $balanceExpire = ", " . __('enought till the') . ' ' . date("d.m.Y", time() + ($daysOnLine * 24 * 60 * 60));
                break;
            case 'mixed':
                $balanceExpire = ", " . __('enought for') . ' ' . $daysOnLine . ' ' . __('days')
                        . ", " . __('till the') . ' ' . date("d.m.Y", time() + ($daysOnLine * 24 * 60 * 60));
                ;
                break;
            default:
                $balanceExpire = NULL;
                break;
        }

        if ($includeVServices and ! empty($totalVsrvPrice) and ! empty($balanceExpire)) {
            if ($us_config['ROUND_PROFILE_CASH']) {
                $totalVsrvPrice = web_roundValue($totalVsrvPrice, 2);
            }

            $balanceExpire .= la_delimiter(0) . '(' . __('including additional services') . ' ' . $totalVsrvPrice . ' ' . $us_config['currency']
                    . ' / ' . __($tariffPeriod) . ')';
        }
    } else {
        //fast credit control id debt
        $creditControl = '';
        if (isset($us_config['ONLINELEFT_CREDIT'])) {
            if ($us_config['ONLINELEFT_CREDIT']) {
                if ($us_config['SC_ENABLED']) {
                    $creditControl = ' ' . la_Link('?module=creditor', __('Get credit') . '?', false, '');
                }
            }
        }
        $balanceExpire = la_tag('span', false, '', 'style="color:red;"') . ', ' . __('indebtedness!') . ' ' . $creditControl . la_tag('span', true);
    }

    if ($rawDays) {
        $balanceExpire = $daysOnLine;
    }

    return ($balanceExpire);
}

/**
 * Renders user login form
 * 
 * @return void
 */
function zbs_LoginForm() {
    $inputs = la_tag('label') . __('Login') . la_tag('label', true) . la_tag('br');
    $inputs .= la_TextInput('ulogin', '', '', true);
    $inputs .= la_tag('label') . __('Password') . la_tag('label', true) . la_tag('br');
    $inputs .= la_PasswordInput('upassword', '', '', true);

    $inputs .= la_Submit(__('Enter'));
    $form = la_Form('', 'POST', $inputs, 'loginform');

    $cells = la_TableCell($form, '', '', 'align="center"');
    $rows = la_TableRow($cells);
    $result = la_TableBody($rows, '100%', 0);

    show_window(__('Login with your account'), $result);
}

/**
 * Renders user logout form (only for login/both auth)
 * 
 * @param bool $return
 * 
 * @return void
 */
function zbs_LogoutForm($return = false) {
    global $us_config;
    if ($us_config['auth'] == 'login' OR $us_config['auth'] == 'both') {
        $form = '';
        $inputs = la_HiddenInput('ulogout', 'true');
        $inputs .= la_Submit(__('Logout'));
        if (isset($_COOKIE['upassword'])) {
            if ($_COOKIE['upassword'] != 'nopassword') {
                $form .= la_Form('', 'POST', $inputs);
            }
        }

        if (!$return) {
            show_window('', $form);
        } else {
            return ($form);
        }
    }
}

/**
 * Returns language selecting box
 * 
 * @return string
 */
function zbs_LangSelector() {
    $glob_conf = zbs_LoadConfig();
    $form = '';
    if ($glob_conf['allowclang']) {
        $allangs = rcms_scandir("languages");

        if (!empty($allangs)) {
            $inputs = la_tag('select', false, '', 'name="changelang" onChange="this.form.submit();"');
            $inputs .= la_tag('option', false, '', 'value="-"') . __('Language') . la_tag('option', true);
            foreach ($allangs as $eachlang) {
                $langIdPath = 'languages/' . $eachlang . '/langid.txt';
                if (file_exists($langIdPath)) {
                    $eachlangid = file_get_contents($langIdPath);
                } else {
                    $eachlangid = $eachlang;
                }
                $inputs .= la_tag('option', false, '', 'value="' . $eachlang . '"') . $eachlangid . la_tag('option', true);
            }
            $inputs .= la_tag('select', true);
            $inputs .= ' ';
            $form = la_Form('', 'GET', $inputs);
        }
    } else {
        $form = '';
    }
    return($form);
}

/**
 * Returns array of available cities as id=>city name
 * 
 * @return array
 */
function zbs_AddressGetFullCityNames() {
    $query = "SELECT * from `city`";
    $result = array();
    $all_data = simple_queryall($query);
    if (!empty($all_data)) {
        foreach ($all_data as $io => $eachcity) {
            $result[$eachcity['id']] = $eachcity['cityname'];
        }
    }

    return($result);
}

/**
 * Returns array of availble user address as login=>address
 * 
 * @return array
 */
function zbs_AddressGetFulladdresslist() {
    $alterconf = zbs_LoadConfig();
    $result = array();
    $query_full = "
        SELECT `address`.`login`,`city`.`cityname`,`street`.`streetname`,`build`.`buildnum`,`apt`.`apt` FROM `address`
        INNER JOIN `apt` ON `address`.`aptid`= `apt`.`id`
        INNER JOIN `build` ON `apt`.`buildid`=`build`.`id`
        INNER JOIN `street` ON `build`.`streetid`=`street`.`id`
        INNER JOIN `city` ON `street`.`cityid`=`city`.`id`";
    $full_adress = simple_queryall($query_full);
    if (!empty($full_adress)) {
        foreach ($full_adress as $ArrayData) {
            // zero apt handle
            if ($alterconf['ZERO_TOLERANCE']) {
                $apartment_filtered = ($ArrayData['apt'] == 0) ? '' : '/' . $ArrayData['apt'];
            } else {
                $apartment_filtered = '/' . $ArrayData['apt'];
            }
            if ($alterconf['CITY_DISPLAY']) {
                $result[$ArrayData['login']] = $ArrayData['cityname'] . ' ' . $ArrayData['streetname'] . ' ' . $ArrayData['buildnum'] . $apartment_filtered;
            } else {
                $result[$ArrayData['login']] = $ArrayData['streetname'] . ' ' . $ArrayData['buildnum'] . $apartment_filtered;
            }
        }
    }
    return($result);
}

/**
 * Returns array of availble user address as login=>array('cityname' = ..., 'streetname' = ..., 'buildnum' = ..., 'apt' = ...)
 * 
 * @return array
 */
function zbs_AddressGetFulladdresslistStruct($login) {
    $login = mysql_real_escape_string($login);
    $alterconf = zbs_LoadConfig();
    $result = array();
    $query_full = "
        SELECT `address`.`login`,`city`.`cityname`,`street`.`streetname`,`build`.`buildnum`,`apt`.`apt` FROM `address`
        INNER JOIN `apt` ON `address`.`aptid`= `apt`.`id`
        INNER JOIN `build` ON `apt`.`buildid`=`build`.`id`
        INNER JOIN `street` ON `build`.`streetid`=`street`.`id`
        INNER JOIN `city` ON `street`.`cityid`=`city`.`id` WHERE `login`='" . $login . "'";
    $full_adress = simple_queryall($query_full);
    if (!empty($full_adress)) {
        foreach ($full_adress as $ArrayData) {
            // zero apt handle
            if ($alterconf['ZERO_TOLERANCE']) {
                $apartment_filtered = ($ArrayData['apt'] == 0) ? '' : '/' . $ArrayData['apt'];
            } else {
                $apartment_filtered = '/' . $ArrayData['apt'];
            }
            if ($alterconf['CITY_DISPLAY']) {
                $result[$ArrayData['login']]['cityname'] = $ArrayData['cityname'];
                $result[$ArrayData['login']]['streetname'] = $ArrayData['streetname'];
                $result[$ArrayData['login']]['buildnum'] = $ArrayData['buildnum'];
                $result[$ArrayData['login']]['apt'] = $ArrayData['apt'];
            } else {
                $result[$ArrayData['login']]['streetname'] = $ArrayData['streetname'];
                $result[$ArrayData['login']]['buildnum'] = $ArrayData['buildnum'];
                $result[$ArrayData['login']]['apt'] = $ArrayData['apt'];
            }
        }
    }
    return($result);
}

/**
 * Returns array of stargazer user data
 * 
 * @param string $login
 * @return array
 */
function zbs_UserGetStargazerData($login) {
    $login = mysql_real_escape_string($login);
    $query = "SELECT * from `users` WHERE `login`='" . $login . "'";
    $result = simple_query($query);
    return($result);
}

/**
 * Returns array of all stargazer user data
 * 
 * @param string $login
 * 
 * @return array
 */
function zbs_UserGetAllStargazerData() {
    $result = array();
    $query = "SELECT * from `users`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['login']] = $each;
        }
    }
    return($result);
}

/**
 * Returns array of all available user realnames as login=>realname
 * 
 * @return array
 */
function zbs_UserGetAllRealnames() {
    $query_fio = "SELECT * from `realname`";
    $allfioz = simple_queryall($query_fio);
    $fioz = array();
    if (!empty($allfioz)) {
        foreach ($allfioz as $ia => $eachfio) {
            $fioz[$eachfio['login']] = $eachfio['realname'];
        }
    }
    return($fioz);
}

/**
 * Returns contract number by user login
 * 
 * @param string $login
 * @return string
 */
function zbs_UserGetContract($login) {
    $login = vf($login);
    $query = "SELECT `contract` from `contracts` WHERE `login`='" . $login . "'";
    $contract_arr = simple_query($query);
    return($contract_arr['contract']);
}

/**
 * Returns email address by user login
 * 
 * @param string $login
 * @return string
 */
function zbs_UserGetEmail($login) {
    $login = vf($login);
    $query = "SELECT `email` from `emails` WHERE `login`='" . $login . "'";
    $email_arr = simple_query($query);
    return($email_arr['email']);
}

/**
 * Returns mobile number by user login
 * 
 * @param string $login
 * @return string
 */
function zbs_UserGetMobile($login) {
    $query = "SELECT `mobile` from `phones` WHERE `login`='" . $login . "'";
    $phone_arr = simple_query($query);
    return($phone_arr['mobile']);
}

/**
 * Returns phone number by user login
 * 
 * @param string $login
 * @return string
 */
function zbs_UserGetPhone($login) {
    $query = "SELECT `phone` from `phones` WHERE `login`='" . $login . "'";
    $phone_arr = simple_query($query);
    return($phone_arr['phone']);
}

/**
 * Renders UBagent user data (deprecated)
 * 
 * @param string $login
 * 
 * @return void
 */
function zbs_UserShowAgentData($login) {
    if (isset($_GET['payments'])) {
        $allpayments = zbs_CashGetUserPayments($login);
        $paycount = (sizeof($allpayments)) * 3;
        $i = 1;
        $cn = 0;
        $payments = '<?xml version="1.0"?>
                <CONFIG>
                <grid version="3">
                <saveoptions create="False" content="True" position="False"/>
                <content>
                <cells cellcount="' . $paycount . '">
            ' . "\n";
        if (!empty($allpayments)) {
            foreach ($allpayments as $io => $eachpayment) {
                $cn++;
                $payments .= '<cell' . $cn . ' row="' . $i . '" text="' . $eachpayment['date'] . '" column="1"/>' . "\n";
                $cn++;
                $payments .= '<cell' . $cn . ' row="' . $i . '" text="' . $eachpayment['summ'] . '" column="2"/>' . "\n";
                $cn++;
                $payments .= '<cell' . $cn . ' row="' . $i . '" text="' . $eachpayment['balance'] . '" column="3"/>' . "\n";
                $i++;
            }
        }
        $payments .= '</cells>
                </content>
                </grid>
                </CONFIG>';
        print($payments);
        die();
    }

    if (isset($_GET['paymentsplain'])) {
        $allpayments = zbs_CashGetUserPayments($login);
        $payments = '';
        if (!empty($allpayments)) {
            foreach ($allpayments as $io => $eachpayment) {
                $payments .= $eachpayment['date'] . ' ' . $eachpayment['summ'] . ' ' . $eachpayment['balance'] . "\n";
            }
        }

        print($payments);
        die();
    }

    if (isset($_GET['messages'])) {
        $msg_result = '';
        $msg_query = "SELECT * from `ticketing` WHERE `to`= '" . $login . "' AND `from`='NULL' AND `status`='1' ORDER BY `date` DESC";
        $allmessages = simple_queryall($msg_query);
        if (!empty($allmessages)) {
            foreach ($allmessages as $io => $eachmessage) {
                $msg_result .= $eachmessage['date'] . "\r\n";
                $msg_result .= $eachmessage['text'] . "\r\n";
                $msg_result .= "\n";
                $msg_result .= "\n";
                $msg_result .= "\n";
            }
        }
        print($msg_result);
        die();
    }


    $us_config = zbs_LoadConfig();
    $us_currency = $us_config['currency'];
    $userdata = zbs_UserGetStargazerData($login);
    $alladdress = zbs_AddressGetFulladdresslist();
    $allrealnames = zbs_UserGetAllRealnames();
    $contract = zbs_UserGetContract($login);
    $email = zbs_UserGetEmail($login);
    $mobile = zbs_UserGetMobile($login);
    $phone = zbs_UserGetPhone($login);
    if ($userdata['CreditExpire'] != 0) {
        $credexpire = date("d-m-Y", $userdata['CreditExpire']);
    } else {
        $credexpire = '';
    }

    $traffdown = 0;
    $traffup = 0;
    $traffdgb = 0;
    $traffugb = 0;

    for ($i = 0; $i <= 9; $i++) {
        $traffdown = $traffdown + $userdata['D' . $i];
        $traffup = $traffup + $userdata['U' . $i];
    }

    $traffdgb = round($traffdown / 1073741824);
    $traffugb = round($traffup / 1073741824);

    if ($traffdgb == 0) {
        $traffdgb = 1;
    }

    if ($traffugb == 0) {
        $traffugb = 1;
    }

    $result = '[USERINFO]' . "\n";
    $result .= 'fulladdress=' . @$alladdress[$login] . "\n";
    $result .= 'realname=' . @$allrealnames[$login] . "\n";
    $result .= 'login=' . $login . "\n";
    $result .= 'password=' . @$userdata['Password'] . "\n";
    $result .= 'cash=' . @round($userdata['Cash'], 2) . "\n";
    $result .= 'login=' . $login . "\n";
    $result .= 'password=' . @$userdata['Password'] . "\n";
    $result .= 'ip=' . @$userdata['IP'] . "\n";
    $result .= 'phone=' . $phone . "\n";
    $result .= 'mobile=' . $mobile . "\n";
    $result .= 'email=' . $email . "\n";
    $result .= 'credit=' . @$userdata['Credit'] . "\n";
    $result .= 'creditexpire=' . $credexpire . "\n";
    $result .= 'payid=' . ip2int($userdata['IP']) . "\n";
    $result .= 'contract=' . $contract . "\n";
    $result .= 'tariff=' . $userdata['Tariff'] . "\n";
    $result .= 'tariffnm=' . $userdata['TariffChange'] . "\n";
    $result .= 'traffd=' . $traffdgb . "\n";
    $result .= 'traffu=' . $traffugb . "\n";
    $result .= 'traffd_conv=' . zbs_convert_size($traffdown) . "\n";
    $result .= 'traffu_conv=' . zbs_convert_size($traffup) . "\n";
    $result .= 'trafftotal_conv=' . zbs_convert_size($traffdown + $traffup) . "\n";



    $result .= "\n";
    $result .= '[CONF]' . "\n";
    $result .= ' currency=' . $us_currency;
    print($result);
    die();
}

/**
 * Renders some data as XML
 * 
 * @param array $data data array for rendering
 * @param string $mainSection all output data parent element tag name
 * @param string $subSection parent tag for each data qunique element tag name
 * @param string $format output format: xml or json
 * @param bool $messages is data contain announcements data for render
 * 
 * @return void
 */
function zbs_XMLAgentRender($data, $mainSection = '', $subSection = '', $format = 'xml', $messages = false) {
    $result = '';
    //XML legacy output
    if ($format == 'xml') {
        $result .= '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;

        if (!empty($mainSection)) {
            $result .= '<' . $mainSection . '>' . PHP_EOL;
        }
        if (!empty($data)) {

            foreach ($data as $index => $record) {
                if (!empty($subSection)) {
                    $result .= '<' . $subSection . '>' . PHP_EOL;
                }

                //normal data output
                if (!$messages) {
                    foreach ($record as $tag => $value) {
                        $result .= "\t" . '<' . $tag . '>' . $value . '</' . $tag . '>' . PHP_EOL;
                    }
                } else {
                    //announcements data output
                    $result .= '<message unic="' . $record['unic'] . '" title="' . $record['title'] . '">' . $record['text'] . '</message>' . PHP_EOL;
                }

                if (!empty($subSection)) {
                    $result .= '</' . $subSection . '>' . PHP_EOL;
                }
            }
        }

        if (!empty($mainSection)) {
            $result .= '</' . $mainSection . '>' . PHP_EOL;
        }
    }

    //JSON data output
    if ($format == 'json') {
        $jsonData = array();
        $rcount = 0;
        if (!empty($data)) {
            foreach ($data as $index => $record) {
                if (!empty($record)) {
                    foreach ($record as $tag => $value) {
                        if (!empty($subSection) OR $messages) {
                            $jsonData[$rcount][$tag] = $value;
                        } else {
                            $jsonData[$tag] = $value;
                        }
                    }
                }
                $rcount++;
            }
        }
        $result .= json_encode($jsonData);
    }


    //pushing result to client
    $contentType = 'text';
    if ($format == 'json') {
        $contentType = 'application/json';
    }

    header('Last-Modified: ' . gmdate('r'));
    header('Content-Type: ' . $contentType . '; charset=UTF-8');
    header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
    header("Pragma: no-cache");
    header('Access-Control-Allow-Origin: *');
    die($result);
}

/**
 * Returns XML-agent user data
 * 
 * @param string $login
 */
function zbs_UserShowXmlAgentData($login) {
    $us_config = zbs_LoadConfig();
    $outputFormat = 'xml';
    if (ubRouting::checkGet('json')) {
        $outputFormat = 'json';
    }
    //payments export
    if (ubRouting::checkGet('payments')) {
        if ($us_config['PAYMENTS_ENABLED']) {
            $allpayments = zbs_CashGetUserPayments($login);
            $payments = array();
            if (!empty($allpayments)) {
                foreach ($allpayments as $io => $eachpayment) {
                    $payments[$eachpayment['id']]['date'] = $eachpayment['date'];
                    $payments[$eachpayment['id']]['summ'] = $eachpayment['summ'];
                    $payments[$eachpayment['id']]['balance'] = $eachpayment['balance'];
                }
                zbs_XMLAgentRender($payments, 'data', 'payment', $outputFormat);
            } else {
                zbs_XMLAgentRender(array(), 'data', 'payment', $outputFormat);
            }
        } else {
            zbs_XMLAgentRender(array(), 'data', 'payment', $outputFormat);
        }
    }

    //announcements export
    if (ubRouting::checkGet('announcements')) {
        if ($us_config['AN_ENABLED']) {
            $announcements_query = "SELECT * from `zbsannouncements` WHERE `public`='1' ORDER by `id` DESC";
            $allAnnouncements = simple_queryall($announcements_query);
            $annArr = array();
            if (!empty($allAnnouncements)) {
                foreach ($allAnnouncements as $ian => $eachAnnouncement) {
                    $annText = strip_tags($eachAnnouncement['text']);
                    $allTitle = strip_tags($eachAnnouncement['title']);
                    $annArr[] = array(
                        'unic' => $eachAnnouncement['id'],
                        'title' => $allTitle,
                        'text' => $annText
                    );
                }
            }
            zbs_XMLAgentRender($annArr, 'data', '', $outputFormat, true);
        } else {
            zbs_XMLAgentRender(array(), 'data', '', $outputFormat, true);
        }
    }

    //helpdesk tickets export
    if (ubRouting::checkGet('tickets')) {
        if ($us_config['TICKETING_ENABLED']) {
            $queryTickets = "SELECT * from `ticketing`  ORDER BY `date` DESC";
            $allTickets = simple_queryall($queryTickets);
            $ticketsArr = array();
            $myTickets = array();

            if (!empty($allTickets)) {
                foreach ($allTickets as $io => $each) {
                    if ($each['from'] == $login OR $each['to'] == $login OR isset($myTickets[$each['replyid']])) {
                        $myTickets[$each['id']] = $each['id'];
                        $ticketsArr[$each['id']]['id'] = $each['id'];
                        $ticketsArr[$each['id']]['date'] = $each['date'];
                        $ticketsArr[$each['id']]['from'] = $each['from'];
                        $ticketsArr[$each['id']]['to'] = $each['to'];
                        $ticketsArr[$each['id']]['replyid'] = $each['replyid'];
                        $ticketsArr[$each['id']]['status'] = $each['status'];
                        $ticketsArr[$each['id']]['text'] = $each['text'];
                    }
                }
                zbs_XMLAgentRender($ticketsArr, 'data', 'ticket', $outputFormat, false);
            }
            die();
        } else {
            zbs_XMLAgentRender(array(), 'data', '', $outputFormat, false);
        }
    }

    //online payments URLs export
    if (ubRouting::checkGet('opayz')) {
        if ($us_config['OPENPAYZ_ENABLED']) {
            $paySys = explode(",", $us_config['OPENPAYZ_PAYSYS']);
            $payDesc = array();
            $opayzArr = array();

            if (file_exists('config/opayz.ini')) {
                $payDesc = parse_ini_file('config/opayz.ini');
            } else {
                $payDesc = array();
            }

            if ($us_config['OPENPAYZ_REALID']) {
                $opayzPaymentid = zbs_PaymentIDGet($login);
            } else {
                $userdata = zbs_UserGetStargazerData($login);
                $opayzPaymentid = ip2int($userdata['IP']);
            }

            if (!empty($paySys)) {
                if (!empty($opayzPaymentid)) {
                    foreach ($paySys as $io => $eachpaysys) {
                        if (isset($payDesc[$eachpaysys])) {
                            $paysys_desc = $payDesc[$eachpaysys];
                        } else {
                            $paysys_desc = '';
                        }
                        $paymentUrl = $us_config['OPENPAYZ_URL'] . $eachpaysys . '/?customer_id=' . $opayzPaymentid;
                        $opayzArr[$eachpaysys]['name'] = $eachpaysys;
                        $opayzArr[$eachpaysys]['url'] = $paymentUrl;
                        $opayzArr[$eachpaysys]['description'] = $paysys_desc;
                    }
                }
            }

            zbs_XMLAgentRender($opayzArr, 'data', 'paysys', $outputFormat, false);
        } else {
            zbs_XMLAgentRender(array(), 'data', '', $outputFormat, false);
        }
    }

    //user data export
    $us_currency = $us_config['currency'];
    $userdata = zbs_UserGetStargazerData($login);
    $alladdress = zbs_AddressGetFulladdresslist();
    if (@$us_config['UBA_XML_ADDRESS_STRUCT']) {
        $alladdressStruct = zbs_AddressGetFulladdresslistStruct($login);
    } else {
        $alladdressStruct = array();
    }
    $allrealnames = zbs_UserGetAllRealnames();
    $contract = zbs_UserGetContract($login);
    $email = zbs_UserGetEmail($login);
    $mobile = zbs_UserGetMobile($login);
    $phone = zbs_UserGetPhone($login);
    $apiVer = '1';

    $passive = $userdata['Passive'];
    $down = $userdata['Down'];

    //payment id handling
    if ($us_config['OPENPAYZ_REALID']) {
        $paymentid = zbs_PaymentIDGet($login);
    } else {
        $paymentid = ip2int($userdata['IP']);
    }

    if ($userdata['CreditExpire'] != 0) {
        $credexpire = date("d-m-Y", $userdata['CreditExpire']);
    } else {
        $credexpire = 'No';
    }

    if ($userdata['TariffChange']) {
        $tariffNm = $userdata['TariffChange'];
    } else {
        $tariffNm = 'No';
    }
    $traffdown = 0;
    $traffup = 0;
    $traffdgb = 0;
    $traffugb = 0;

    for ($i = 0; $i <= 9; $i++) {
        $traffdown = $traffdown + $userdata['D' . $i];
        $traffup = $traffup + $userdata['U' . $i];
    }

    $traffdgb = round($traffdown / 1073741824);
    $traffugb = round($traffup / 1073741824);

    if ($traffdgb == 0) {
        $traffdgb = 1;
    }

    if ($traffugb == 0) {
        $traffugb = 1;
    }

    // pasive state check
    if ($passive) {
        $passive_state = 'frozen';
    } else {
        $passive_state = 'active';
    }

    //down state check
    if ($down) {
        $down_state = ' + disabled';
    } else {
        $down_state = '';
    }

    // START OF ONLINELEFT COUNTING <<
    if ($us_config['ONLINELEFT_COUNT'] != 0) {
        // DEFINE VARS:
        $userBalance = $userdata['Cash'];
        if ($userBalance >= 0) {
            $balanceExpire = zbs_GetOnlineLeftCount($login, $userBalance, $userdata['Tariff'], true);
        } else {
            $balanceExpire = 'debt';
        }
    } else {
        $balanceExpire = 'No';
    }
    // >> END OF ONLINELEFT COUNTING

    $reqResult = array();
    $reqResult[] = array('address' => @$alladdress[$login]);
    if (@$us_config['UBA_XML_ADDRESS_STRUCT']) {
        if (!empty($alladdressStruct)) {
            foreach ($alladdressStruct[$login] as $field => $value) {
                $reqResult[] = array($field => $value);
            }
        }
    }
    $reqResult[] = array('realname' => @$allrealnames[$login]);
    $reqResult[] = array('login' => $login);
    $reqResult[] = array('cash' => @round($userdata['Cash'], 2));
    $reqResult[] = array('ip' => @$userdata['IP']);
    $reqResult[] = array('phone' => $phone);
    $reqResult[] = array('mobile' => $mobile);
    $reqResult[] = array('email' => $email);
    $reqResult[] = array('credit' => @$userdata['Credit']);
    $reqResult[] = array('creditexpire' => $credexpire);
    $reqResult[] = array('payid' => strval($paymentid));
    $reqResult[] = array('contract' => $contract);
    $reqResult[] = array('tariff' => $userdata['Tariff']);
    $reqResult[] = array('tariffalias' => __($userdata['Tariff']));
    $reqResult[] = array('tariffnm' => $tariffNm);
    $reqResult[] = array('traffdownload' => zbs_convert_size($traffdown));
    $reqResult[] = array('traffupload' => zbs_convert_size($traffup));
    $reqResult[] = array('trafftotal' => zbs_convert_size($traffdown + $traffup));
    $reqResult[] = array('accountstate' => $passive_state . $down_state);
    $reqResult[] = array('accountexpire' => $balanceExpire);
    $reqResult[] = array('currency' => $us_currency);
    $reqResult[] = array('version' => $apiVer);

    zbs_XMLAgentRender($reqResult, 'userdata', '', $outputFormat, false);
}

/**
 * Returns user paymentID by login
 * 
 * @param string $login
 * @return int
 */
function zbs_PaymentIDGet($login) {
    $login = mysql_real_escape_string($login);
    $query = "SELECT `virtualid` from `op_customers` WHERE `realid`='" . $login . "'";
    $result = simple_query($query);
    if (!empty($result)) {
        $result = $result['virtualid'];
    } else {
        $result = '';
    }
    return ($result);
}

/**
 * Returns tariff speed
 * 
 * @param string $tariff
 * @param bool $raw
 * 
 * @return string
 */
function zbs_TariffGetSpeed($tariff, $raw = false) {
    $offset = 1024;
    $query = "SELECT * from `speeds` where `Tariff`='" . $tariff . "'";
    $speedData = simple_query($query);
    $result = '';
    if (!empty($speedData)) {
        if ($speedData['speeddown'] != 0) {
            if ($speedData['speeddown'] < $offset) {
                $result = $speedData['speeddown'] . ' ' . __('Kbit/s');
            } else {
                $result = ($speedData['speeddown'] / $offset) . ' ' . __('Mbit/s');
            }

            if ($raw) {
                $result = $speedData['speeddown'];
            }
        } else {
            $result = __('Unlimited');
        }
    } else {
        $result = __('None');
    }
    return ($result);
}

/**
 * Returns all tariff speeds
 * 
 * @param bool $rawMbitSpeeds
 * 
 * @return array
 */
function zbs_TariffGetAllSpeeds($rawMbitSpeeds = false) {
    $offset = 1024;
    $query = "SELECT * from `speeds`";
    $speedData = simple_queryall($query);
    $result = array();
    if (!empty($speedData)) {
        foreach ($speedData as $io => $each) {
            if ($each['speeddown'] != 0) {
                if (!$rawMbitSpeeds) {
                    if ($each['speeddown'] < $offset) {
                        $speed = $each['speeddown'] . ' ' . __('Kbit/s');
                    } else {
                        $speed = ($each['speeddown'] / $offset) . ' ' . __('Mbit/s');
                    }
                } else {
                    $speed = $each['speeddown'] . ' ' . __('Mbit/s');
                }
            } else {
                $speed = __('Unlimited');
            }
            $result[$each['tariff']] = $speed;
        }
    }
    return ($result);
}

/**
 * Returns speed override by login, if available
 * 
 * @param string $login
 * @return int
 */
function zbs_SpeedGetOverride($login) {
    $offset = 1024;
    $login = mysql_real_escape_string($login);
    $query = "SELECT * from `userspeeds` WHERE `login`='" . $login . "'";
    $speedData = simple_query($query);
    $result = 0;
    if (!empty($speedData)) {
        $result = $speedData['speed'];
    }
    return ($result);
}

/**
 * Returns array of all available virtual services
 * 
 * @return array
 */
function zbs_getVservicesAll() {
    $result = array();
    $query = "SELECT * from `vservices`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['tagid']] = $each['price'];
        }
    }
    return ($result);
}

/**
 * Returns array of tag names as id=>name
 * 
 * @return array
 */
function zbs_getTagNames() {
    $result = array();
    $query = "SELECT * from `tagtypes`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['id']] = $each['tagname'];
        }
    }
    return ($result);
}

/**
 * Returns array of tags associated with user
 * 
 * @param string $login
 * @return array
 */
function zbs_getUserTags($login) {
    $result = array();
    $login = mysql_real_escape_string($login);
    $query = "SELECT * from `tags` WHERE `login`='" . $login . "';";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['tagid']] = $each['id'];
        }
    }
    return ($result);
}

/**
 * Returns array of user assigned tags as login => array($dbID => $tagID)
 *
 * This function ensures the uniqueness of selected tags assigned to user
 * if user has one and the same tag assigned multiple times for some reason
 *
 * @param string $login
 * @param string $whereTagID
 *
 * @return array
 */
function zbs_UserGetAllTagsUnique($login = '', $whereTagID = '') {
    $result = array();
    $queryWhere = (empty($login)) ? '' : " WHERE `login` = '" . $login . "'";

    if (!empty($whereTagID)) {
        if (empty($queryWhere)) {
            $queryWhere = " WHERE `tagid` IN(" . $whereTagID . ")";
        } else {
            $queryWhere .= " AND `tagid` IN(" . $whereTagID . ")";
        }
    }

    $query = "SELECT * from `tags`" . $queryWhere;
    $allTags = simple_queryall($query);

    if (!empty($allTags)) {
        foreach ($allTags as $io => $each) {
            $result[$each['login']][$each['id']] = $each['tagid'];
        }
    }

    return ($result);
}

/**
 * Returns total cost of all additional services.
 *
 * @param string $login user's login
 *
 * @return float for all virtual services if such exists for user
 */
function zbs_vservicesGetUserPrice($login) {
    $price = 0;

    $tag_query = "SELECT * FROM `tags` WHERE `login` =  '" . $login . "' ";
    $alltags = simple_queryall($tag_query);

    $VS_query = "SELECT * FROM `vservices`";
    $allVS = simple_queryall($VS_query);

    if (!empty($alltags)) {
        foreach ($alltags as $io => $eachtag) {
            foreach ($allVS as $each => $eachSrv) {
                if ($eachtag['tagid'] == $eachSrv['tagid']) {
                    $price += $eachSrv['price'];
                }
            }
        }
    }

    return($price);
}

/**
 * Returns array of all available virtual services as tagid => array('price' => $price, 'period' => $period);
 *
 * @return array
 */
function zbs_vservicesGetAllPricesPeriods() {
    $result = array();
    $query = "SELECT * from `vservices`";
    $all = simple_queryall($query);

    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['tagid']] = array('price' => $each['price'], 'daysperiod' => $each['charge_period_days']);
        }
    }

    return ($result);
}

/**
 * Returns price total of all virtual services fees assigned to user, considering services fee charge periods
 * $defaultPeriod - specifies the default period to count the prices for: 'month' or 'day', if certain service has no fee charge period set
 *
 * @param string $login
 * @param string $defaultPeriod
 *
 * @return float
 */
function zbs_vservicesGetUserPricePeriod($login, $defaultPeriod = 'month') {
    $totalVsrvPrice = 0;
    $allUserVsrvs = zbs_vservicesGetUsersAll($login, true);
    $curMonthDays = date('t');

    if (!empty($allUserVsrvs)) {
        $allUserVsrvs = $allUserVsrvs[$login];

        foreach ($allUserVsrvs as $eachTagDBID => $eachSrvData) {
            $curVsrvPrice = $eachSrvData['price'];
            $curVsrvDaysPeriod = $eachSrvData['daysperiod'];
            $dailyVsrvPrice = 0;

            // getting daily vservice price
            if (!empty($curVsrvDaysPeriod)) {
                $dailyVsrvPrice = ($curVsrvDaysPeriod > 1) ? $curVsrvPrice / $curVsrvDaysPeriod : $curVsrvPrice;
            }

            // if vservice has no charge period set and $dailyVsrvPrice == 0
            // then virtual service price is considered as for global $defaultPeriod period
            if ($defaultPeriod == 'month') {
                $totalVsrvPrice += (empty($dailyVsrvPrice)) ? $curVsrvPrice : $dailyVsrvPrice * $curMonthDays;
            } else {
                $totalVsrvPrice += (empty($dailyVsrvPrice)) ? $curVsrvPrice : $dailyVsrvPrice;
            }
        }
    }

    return ($totalVsrvPrice);
}

/**
 * Returns all users with assigned virtual services as array:
 *         login => array($tagDBID => vServicePrice1)
 *
 * if $includePeriod is true returned array will look like this:
 *          login => array($tagDBID => array('price' => vServicePrice1, 'daysperiod' => vServicePeriod1))
 *
 * if $includeVSrvName is true 'vsrvname' => tagname is added to the end of the array
 *
 * @param string $login
 * @param bool $includePeriod
 * @param bool $includeVSrvName
 *
 * @return array
 */
function zbs_vservicesGetUsersAll($login = '', $includePeriod = false, $includeVSrvName = false) {
    $result = array();
    $allTagNames = array();
    $allUserTags = zbs_UserGetAllTagsUnique($login);

    if ($includeVSrvName) {
        $allTagNames = zbs_getTagNames();
    }

    //user have some tags assigned
    if (!empty($allUserTags)) {
        $vservicePrices = ($includePeriod) ? zbs_vservicesGetAllPricesPeriods() : zbs_getVservicesAll();

        foreach ($allUserTags as $eachLogin => $data) {
            $tmpArr = array();

            foreach ($data as $tagDBID => $tagID) {
                if (isset($vservicePrices[$tagID])) {
                    if ($includeVSrvName) {
                        $tmpArr[$tagDBID] = $vservicePrices[$tagID] + array('vsrvname' => $allTagNames[$tagID]);
                    } else {
                        $tmpArr[$tagDBID] = $vservicePrices[$tagID];
                    }
                }
            }

            if (!empty($tmpArr)) {
                $result[$eachLogin] = $tmpArr;
            }
        }
    }

    return ($result);
}

/**
 * Renders list of associated with user virtual services
 *
 * @param string $login
 * @param string $currency
 *
 * @return string
 */
function zbs_vservicesShow($login, $currency) {
    global $us_config;
    $result = '';
    $vservicesPeriodON = (!empty($us_config['VSERVICES_CONSIDER_PERIODS']));
    $userServices = zbs_vservicesGetUsersAll($login, true, true);

    //yep, this user have some services assigned
    if (!empty($userServices)) {
        $userServices = $userServices[$login];

        $cells = la_TableCell(__('Service'), '60%');
        $cells .= la_TableCell(__('Terms'));
        $rows = la_TableRow($cells, 'row1');

        foreach ($userServices as $eachDBID => $eachsSrvice) {
            if ($vservicesPeriodON) {
                if ($eachsSrvice['price'] >= 0) {
                    $servicePrice = __('Price') . ' ' . $eachsSrvice['price'] . ' ' . $currency . ' / ' . $eachsSrvice['daysperiod'] . ' ' . __('day');
                } else {
                    $servicePrice = __('Bonus') . ' ' . abs($eachsSrvice['price']) . ' ' . $currency . ' / ' . $eachsSrvice['daysperiod'] . ' ' . __('day');
                }
            } else {
                if ($eachsSrvice['price'] >= 0) {
                    $servicePrice = __('Price') . ' ' . $eachsSrvice['price'] . ' ' . $currency;
                } else {
                    $servicePrice = __('Bonus') . ' ' . abs($eachsSrvice['price']) . ' ' . $currency;
                }
            }

            $cells = la_TableCell($eachsSrvice['vsrvname']);
            $cells .= la_TableCell($servicePrice);
            $rows .= la_TableRow($cells, 'row3');
        }

        $result .= la_tag('br');
        $result .= la_tag('h3') . __('Additional services') . la_tag('h3', true);
        $result .= la_TableBody($rows, '100%', 0);
    }

    if ($us_config['VSERVICES_SHOW'] == 2) {
        //TODO
    }
    return ($result);
}

/**
 * Renders custom discount percent if CUD_SHOW option enabled
 * 
 * @param string $login
 * @param array $us_config
 * 
 * @return string
 */
function zbs_CUDShow($login, $us_config) {
    $result = '';
    $login = mysql_real_escape_string($login);
    if (isset($us_config['CUD_SHOW'])) {
        if ($us_config['CUD_SHOW']) {
            $query = "SELECT * from `cudiscounts` WHERE `login`='" . $login . "';";
            $data = simple_query($query);
            if (!empty($data)) {
                $discount = $data['discount'];
                $cells = la_TableCell(__('Discount'), '', 'row1');
                $cells .= la_TableCell($discount . '%');
                $result = la_TableRow($cells);
            }
        }
    }
    return ($result);
}

/**
 * Renders form for change user password
 * 
 * @param string $login
 * @return string
 */
function zbs_UserChangePassword($login) {
    $result = '';
    if (isset($login)) {
        // change password  if need
        if (la_CheckPost(array('newpassword', 'confirmnewpassword'))) {
            if ($_POST['newpassword'] == $_POST['confirmnewpassword']) {
                $current_password = zbs_UserGetStargazerData($login);
                $current_password = $current_password['Password'];
                if ($current_password == $_POST['upassword']) {
                    $password = $_POST['newpassword'];
                    $password = vf($password);
                    $password = preg_replace('#[^a-z0-9A-Z\-_\.]#Uis', '', $password);
                    $password = preg_replace('/\0/s', '', $password);
                    if (strlen($password) >= 5) {
                        billing_setpassword($login, $password);
                        log_register('CHANGE Password (' . $login . ') ON `' . $password . '` BY USER');
                        // After change pass need login again for set cookies
                        rcms_redirect("index.php");
                    } else {
                        $content = __('Password must contain 5 and more characters');
                        $result .= la_modalOpened(__('Error'), $content, 300, 200);
                    }
                } else {
                    $content = __('Incorrect current password');
                    $result .= la_modalOpened(__('Error'), $content, 300, 200);
                }
            } else {
                $content = __('Passwords do not match');
                $result .= la_modalOpened(__('Error'), $content, 300, 200);
            }
        }

// Edit form construct
        $inputs = la_tag('label') . __('Current password') . la_tag('label', true) . la_tag('br');
        $inputs .= la_PasswordInput('upassword', '', '', true) . la_tag('br');
        $inputs .= la_tag('label') . __('New password') . la_tag('label', true) . la_tag('br');
        $inputs .= la_PasswordInput('newpassword', '', '', true);
        $inputs .= la_tag('label') . __('Confirm new password') . la_tag('label', true) . la_tag('br');
        $inputs .= la_PasswordInput('confirmnewpassword', '', '', true) . la_tag('br');
        $inputs .= la_Submit(__('Change password'));

        $form = la_Form('index.php', "POST", $inputs, 'glamour');
        $result .= la_modal(__('Change password'), __('Change password'), $form, '', 300, 370);
    }
    return ($result);
}

/**
 * Renders user profile
 * 
 * @param string $login
 * @return string
 */
function zbs_UserShowProfile($login) {
    $us_config = zbs_LoadConfig();
    $us_currency = $us_config['currency'];
    $userdata = zbs_UserGetStargazerData($login);
    $alladdress = zbs_AddressGetFulladdresslist();
    $allrealnames = zbs_UserGetAllRealnames();
    $contract = zbs_UserGetContract($login);
    $email = zbs_UserGetEmail($login);
    $mobile = zbs_UserGetMobile($login);
    $phone = zbs_UserGetPhone($login);
    $passive = $userdata['Passive'];
    $down = $userdata['Down'];
    $userpassword = $userdata['Password'];
    // Allow user change password
    // With this option we hide current password user
    if (isset($us_config['PASSWORD_CHANGE']) and $us_config['PASSWORD_CHANGE'] == 1) {
        $userpassword = zbs_UserChangePassword($login);
    }
    $skinPath = zbs_GetCurrentSkinPath($us_config);
    $iconsPath = $skinPath . 'iconz/';

    //public offer mode
    if (isset($us_config['PUBLIC_OFFER'])) {
        if (!empty($us_config['PUBLIC_OFFER'])) {
            $publicOfferUrl = $us_config['PUBLIC_OFFER'];
            $publicOfferCaption = (empty($us_config['PUBLIC_OFFER_CAPTION']) ? __('Public offer') : $us_config['PUBLIC_OFFER_CAPTION']);
            $contract = la_Link($publicOfferUrl, $publicOfferCaption, false, '');
        }
    }

    // START OF ONLINELEFT COUNTING <<
    if ($us_config['ONLINELEFT_COUNT'] != 0) {
        $userBalance = $userdata['Cash'];
        $userTariff = $userdata['Tariff'];
        $balanceExpire = zbs_GetOnlineLeftCount($login, $userBalance, $userTariff, false);
    } else {
        $balanceExpire = '';
    }
    // >> END OF ONLINELEFT COUNTING

    if ($userdata['CreditExpire'] != 0) {
        $credexpire = date("d-m-Y", $userdata['CreditExpire']);
    } else {
        $credexpire = '';
    }

    // pasive state check
    if ($passive) {
        $passive_state = __('Account frozen');
    } else {
        $passive_state = __('Account active');
    }

    //down state check
    if ($down) {
        $down_state = ' + ' . __('Disabled');
    } else {
        $down_state = '';
    }

    //payment id handling
    if ($us_config['OPENPAYZ_REALID']) {
        $paymentid = zbs_PaymentIDGet($login);
    } else {
        $paymentid = ip2int($userdata['IP']);
    }

    //payment id qr dialog
    $paymentidqr = '';
    if (isset($us_config['PAYMENTID_QR'])) {
        if ($us_config['PAYMENTID_QR']) {
            $paymentidqr = la_modal(la_img($iconsPath . 'qrcode.png', 'QR-code'), __('Payment ID'), la_tag('center') . la_img('qrgen.php?data=' . $paymentid) . la_tag('center', true), '', '300', '250');
        }
    }

    //draw order link
    if ($us_config['DOCX_SUPPORT']) {
        $zdocsLink = ' ' . la_Link('?module=zdocs', __('Draw order'), false, 'printorder');
    } else {
        $zdocsLink = '';
    }

    //tariff speeds
    if ($us_config['SHOW_SPEED']) {
        $rawSpeedMbits = (@$us_config['SHOW_SPEED_MB']) ? true : false;
        $speedOffset = 1024;
        $userSpeedOverride = zbs_SpeedGetOverride($login);
        if ($userSpeedOverride == 0) {
            $showSpeed = zbs_TariffGetSpeed($userdata['Tariff'], $rawSpeedMbits);
        } else {
            if (!$rawSpeedMbits) {
                if ($userSpeedOverride < $speedOffset) {
                    $showSpeed = $userSpeedOverride . ' ' . __('Kbit/s');
                } else {
                    $showSpeed = ($userSpeedOverride / $speedOffset) . ' ' . __('Mbit/s');
                }
            } else {
                $showSpeed = $userSpeedOverride;
            }
        }

        if ($rawSpeedMbits) {
            if (is_numeric($showSpeed)) {
                $showSpeed .= ' ' . __('Mbit/s');
            }
        }

        $tariffSpeeds = la_TableRow(la_TableCell(__('Tariff speed'), '', 'row1') . la_TableCell($showSpeed));
    } else {
        $tariffSpeeds = '';
    }

    if ($us_config['ROUND_PROFILE_CASH']) {
        $Cash = web_roundValue($userdata['Cash'], 2);
    } else {
        $Cash = $userdata['Cash'];
    }

    $profile = la_tag('table', false, '', 'width="100%" border="0" cellpadding="2" cellspacing="3"');
    $profile .= la_tag('tr');
    $profile .= la_TableCell(__('Address'), '', 'row1');
    $profile .= la_TableCell(@$alladdress[$login]);
    $profile .= la_tag('tr', true);

    $profile .= la_tag('tr');
    $profile .= la_TableCell(__('Real name'), '', 'row1');
    $profile .= la_TableCell(@$allrealnames[$login]);
    $profile .= la_tag('tr', true);

    if (!@$us_config['LOGINHIDE']) {
        $profile .= la_tag('tr');
        $profile .= la_TableCell(__('Login'), '', 'row1');
        $profile .= la_TableCell($login);
        $profile .= la_tag('tr', true);
    }

    if (!@$us_config['PASSWORDSHIDE']) {
        $profile .= la_tag('tr');
        $profile .= la_TableCell(__('Password'), '', 'row1');
        $profile .= la_TableCell($userpassword);
        $profile .= la_tag('tr', true);
    }
    $profile .= la_tag('tr');
    $profile .= la_TableCell(__('IP'), '', 'row1');
    $profile .= la_TableCell($userdata['IP']);
    $profile .= la_tag('tr', true);

    $profile .= la_tag('tr');
    $profile .= la_TableCell(__('Phone'), '', 'row1');
    $profile .= la_TableCell($phone);
    $profile .= la_tag('tr', true);

    $profile .= la_tag('tr');
    $profile .= la_TableCell(__('Mobile'), '', 'row1');
    $profile .= la_TableCell($mobile);
    $profile .= la_tag('tr', true);

    if (@$us_config['SHOW_EXT_MOBILES']) {
        $mobilesExt = new UserstatsMobilesExt($login);
        $profile .= la_tag('tr');
        $profile .= la_TableCell(__('Additional mobile'), '', 'row1');
        $profile .= la_TableCell($mobilesExt->renderUserMobiles());
        $profile .= la_tag('tr', true);
    }

    $profile .= la_tag('tr');
    $profile .= la_TableCell(__('Email'), '', 'row1');
    $profile .= la_TableCell($email);
    $profile .= la_tag('tr', true);

    $profile .= la_tag('tr');
    $payIdAbbr = la_tag('abbr', false, '', 'title="' . __('Payment ID is used to make online payments using a variety of payment systems as well as the funding of accounts using the terminals') . '"');
    $payIdAbbr .= __('Payment ID');
    $payIdAbbr .= la_tag('abbr', true);

    $profile .= la_TableCell($payIdAbbr, '', 'row1');
    $profile .= la_TableCell($paymentid . ' ' . $paymentidqr);
    $profile .= la_tag('tr', true);

    $profile .= la_tag('tr');
    $profile .= la_TableCell(__('Contract'), '', 'row1');
    $profile .= la_TableCell($contract);
    $profile .= la_tag('tr', true);

    $profile .= la_tag('tr');
    $profile .= la_TableCell(__('Balance'), '', 'row1');
    $profile .= la_TableCell($Cash . ' ' . $us_currency . $balanceExpire . $zdocsLink);
    $profile .= la_tag('tr', true);

    $profile .= la_tag('tr');
    $profile .= la_TableCell(__('Credit'), '', 'row1');
    $profile .= la_TableCell($userdata['Credit'] . ' ' . $us_currency);
    $profile .= la_tag('tr', true);

    $profile .= la_tag('tr');
    $profile .= la_TableCell(__('Credit Expire'), '', 'row1');
    $profile .= la_TableCell($credexpire);
    $profile .= la_tag('tr', true);

    $profile .= la_tag('tr');
    $profile .= la_TableCell(__('Tariff'), '', 'row1');
    $profile .= la_TableCell(__($userdata['Tariff']));
    $profile .= la_tag('tr', true);

    //Power tariffs suport enabled?
    if (@$us_config['POWERTARIFFS_ENABLED']) {
        $powerTariffs = new NyanORM('pt_tariffs');
        $allPowerTariffs = $powerTariffs->getAll('tariff');
        $feeDay = 0;
        if (isset($allPowerTariffs[$userdata['Tariff']])) {
            //custom tariff price
            $tariffPrice = $allPowerTariffs[$userdata['Tariff']]['fee'];
            //getting custom fee date for this user
            $powerUsers = new NyanORM('pt_users');
            $powerUsers->where('login', '=', $login);
            $powerUserData = $powerUsers->getAll('login');
            if (!empty($powerUserData)) {
                $feeDay = $powerUserData[$login]['day'];
            }
        } else {
            //this is just normal tariff
            @$tariffPrice = zbs_UserGetTariffPrice($userdata['Tariff']);
        }
    } else {
        @$tariffPrice = zbs_UserGetTariffPrice($userdata['Tariff']);
    }

    //tariff price here
    $profile .= la_tag('tr');
    $profile .= la_TableCell(__('Tariff price'), '', 'row1');
    $profile .= la_TableCell($tariffPrice . ' ' . $us_currency);
    $profile .= la_tag('tr', true);

    //render custom fee day
    if (@$us_config['POWERTARIFFS_ENABLED']) {
        if ($feeDay != 0) {
            $profile .= la_tag('tr');
            $profile .= la_TableCell(__('Day of the next tariff fee'), '', 'row1');
            $profile .= la_TableCell($feeDay);
            $profile .= la_tag('tr', true);
        }
    }

    $profile .= $tariffSpeeds;

    $profile .= la_tag('tr');
    $profile .= la_TableCell(__('Tariff change'), '', 'row1');
    $profile .= la_TableCell(__($userdata['TariffChange']));
    $profile .= la_tag('tr', true);

    $profile .= la_tag('tr');
    $profile .= la_TableCell(__('Account state'), '', 'row1');
    $profile .= la_TableCell($passive_state . $down_state);
    $profile .= la_tag('tr', true);

    if (@$us_config['TG_BOTNAME']) {
        $tgBotName = $us_config['TG_BOTNAME'];
        $tgBotUrl = 'https://t.me/' . $tgBotName . '/?start=' . $login . '-' . md5($userdata['Password']);
        $profile .= la_tag('tr');
        $profile .= la_TableCell(__('Telegram bot'), '', 'row1');
        $profile .= la_TableCell(la_Link($tgBotUrl, __('Connect to bot') . '!'));
        $profile .= la_tag('tr', true);
    }

    $profile .= zbs_CUDShow($login, $us_config);

    $profile .= la_tag('table', true);

    //show assigned virtual services if available
    if (isset($us_config['VSERVICES_SHOW'])) {
        if ($us_config['VSERVICES_SHOW']) {
            $profile .= zbs_vservicesShow($login, $us_currency);
        }
    }

    return($profile);
}

/**
 * Returns payments array for some user
 * 
 * @param string $login
 * @param bool $onlyPositive
 * 
 * @return array
 */
function zbs_CashGetUserPayments($login, $onlyPositive = false) {
    $login = vf($login);
    $additionalFilters = '';
    if ($onlyPositive) {
        $additionalFilters .= " AND `summ`>'0' ";
    }
    $query = "SELECT * from `payments` WHERE `login`='" . $login . "' " . $additionalFilters . " ORDER BY `id` DESC";
    $allpayments = simple_queryall($query);
    return($allpayments);
}

/**
 * Renders user traffic stats report
 * 
 * @param string $login
 * @return string
 */
function zbs_UserTraffStats($login) {
    global $us_config;
    $login = vf($login);
    $alldirs = zbs_DirectionsGetAll();
    $monthnames = zbs_months_array_wz();
    $ishimuraOption = 'ISHIMURA_ENABLED';
    $ishimuraTable = 'mlg_ishimura';
    /*
     * Current month traffic stats
     */
    $result = la_tag('h3') . __('Current month traffic stats') . la_tag('h3', true);

    $cells = la_TableCell(__('Traffic classes'));
    $cells .= la_TableCell(__('Downloaded'));
    $cells .= la_TableCell(__('Uploaded'));
    $cells .= la_TableCell(__('Total'));
    $rows = la_TableRow($cells, 'row1');


    if (!empty($alldirs)) {
        foreach ($alldirs as $io => $eachdir) {
            $query_downup = "SELECT `D" . $eachdir['rulenumber'] . "`,`U" . $eachdir['rulenumber'] . "` from `users` WHERE `login`='" . $login . "'";
            $downup = simple_query($query_downup);
            //yeah, no classes at all
            if ($eachdir['rulenumber'] == 0) {

                if ($us_config[$ishimuraOption]) {
                    $query_hideki = "SELECT `D0`,`U0` from `" . $ishimuraTable . "` WHERE `login`='" . $login . "' AND `month`='" . date("n") . "' AND `year`='" . date("Y") . "'";
                    $dataHideki = simple_query($query_hideki);
                    if (isset($downup['D0'])) {
                        $downup['D0'] += $dataHideki['D0'];
                        $downup['U0'] += $dataHideki['U0'];
                    } else {
                        $downup['D0'] = $dataHideki['D0'];
                        $downup['U0'] = $dataHideki['U0'];
                    }
                }
            }
            $cells = la_TableCell($eachdir['rulename']);
            $cells .= la_TableCell(zbs_convert_size($downup['D' . $eachdir['rulenumber']]));
            $cells .= la_TableCell(zbs_convert_size($downup['U' . $eachdir['rulenumber']]));
            $cells .= la_TableCell(zbs_convert_size(($downup['U' . $eachdir['rulenumber']] + $downup['D' . $eachdir['rulenumber']])));
            $rows .= la_TableRow($cells, 'row3');
        }
    }

    $result .= la_TableBody($rows, '100%', 0, '');
    $result .= la_delimiter();

    /*
     * traffic stats by previous months
     */
    $prevStatsTmp = array();
    $result .= la_tag('h3') . __('Previous month traffic stats') . la_tag('h3', true);


    $cells = la_TableCell(__('Year'));
    $cells .= la_TableCell(__('Month'));
    $cells .= la_TableCell(__('Traffic classes'));
    $cells .= la_TableCell(__('Downloaded'));
    $cells .= la_TableCell(__('Uploaded'));
    $cells .= la_TableCell(__('Total'));
    $cells .= la_TableCell(__('Cash'));
    $rows = la_TableRow($cells, 'row1');

    if (!empty($alldirs)) {
        foreach ($alldirs as $io => $eachdir) {
            $query_prev = "SELECT `D" . $eachdir['rulenumber'] . "`,`U" . $eachdir['rulenumber'] . "`,`month`,`year`,`cash` from `stat` WHERE `login`='" . $login . "' ORDER BY `year`,`month`";
            $allprevmonth = simple_queryall($query_prev);
            //and again no classes
            if ($eachdir['rulenumber'] == 0) {
                if ($us_config[$ishimuraOption]) {
                    $query_hideki = "SELECT `D0`,`U0`,`month`,`year`,`cash` from `" . $ishimuraTable . "` WHERE `login`='" . $login . "' ORDER BY `year`,`month`;";
                    $dataHideki = simple_queryall($query_hideki);
                    if (!empty($dataHideki)) {
                        foreach ($dataHideki as $io => $each) {
                            foreach ($allprevmonth as $ia => $stgEach) {
                                if ($stgEach['year'] == $each['year'] AND $stgEach['month'] == $each['month']) {
                                    $allprevmonth[$ia]['D0'] += $each['D0'];
                                    $allprevmonth[$ia]['U0'] += $each['U0'];
                                    $allprevmonth[$ia]['cash'] += $each['cash'];
                                }
                            }
                        }
                    }
                }
            }
            if (!empty($allprevmonth)) {
                $allprevmonth = array_reverse($allprevmonth);
            }

            if (!empty($allprevmonth)) {
                foreach ($allprevmonth as $io2 => $eachprevmonth) {
                    $cells = la_TableCell($eachprevmonth['year']);
                    $cells .= la_TableCell(__($monthnames[$eachprevmonth['month']]));
                    $cells .= la_TableCell($eachdir['rulename']);
                    $cells .= la_TableCell(zbs_convert_size($eachprevmonth['D' . $eachdir['rulenumber']]));
                    $cells .= la_TableCell(zbs_convert_size($eachprevmonth['U' . $eachdir['rulenumber']]));
                    $cells .= la_TableCell(zbs_convert_size(($eachprevmonth['U' . $eachdir['rulenumber']] + $eachprevmonth['D' . $eachdir['rulenumber']])));
                    $cells .= la_TableCell(round($eachprevmonth['cash'], 2));
                    $rows .= la_TableRow($cells, 'row3');
                }
            }
        }
    }
    $result .= la_TableBody($rows, '100%', 0, '');

    return($result);
}

/**
 * Returns array of available traffic directions aka classes
 * 
 * @return array
 */
function zbs_DirectionsGetAll() {
    $query = "SELECT * from `directions`";
    $allrules = simple_queryall($query);
    return ($allrules);
}

/**
 * Converts bytes values into readable traffic counters
 * 
 * @param int $fs
 * @return string
 */
function zbs_convert_size($fs) {
    if ($fs >= 1073741824)
        $fs = round($fs / 1073741824 * 100) / 100 . " Gb";
    elseif ($fs >= 1048576)
        $fs = round($fs / 1048576 * 100) / 100 . " Mb";
    elseif ($fs >= 1024)
        $fs = round($fs / 1024 * 100) / 100 . " Kb";
    else
        $fs = $fs . " b";
    return ($fs);
}

/**
 * Renders navigation menu bar
 * 
 * @param bool $icons
 * @return string
 */
function zbs_ModulesMenuShow($icons = false) {
    $globconf = zbs_LoadConfig();
    $maxnoicon = $globconf['MENUNOICONMAX'];

    $mod_path = "config/modules.d/";
    $skinPath = zbs_GetCurrentSkinPath($globconf);
    $iconsPath = $skinPath . 'iconz/';
    $all_modules = rcms_scandir($mod_path);
    $currentModule = 'index';
    if (isset($_GET['module'])) {
        if (!empty($_GET['module'])) {
            $currentModule = $_GET['module'];
        }
    }

    $count = 1;
    $result = '';
    //default home link
    if ($icons) {
        $homeClass = ($currentModule == 'index') ? 'active' : 'menublock';
        $result .= '<li class="' . $homeClass . '"><a href="index.php"><img src="' . $skinPath . 'iconz/home.gif"> ' . __('Home') . '</a></li>';
    } else {
        $result .= '<li><a href="index.php"> ' . __('Home') . '</a></li>';
    }
    if (!empty($all_modules)) {
        foreach ($all_modules as $eachmodule) {
            $linkClass = ($currentModule == $eachmodule) ? 'active' : 'menublock';
            if ($icons == true) {
                if (file_exists($iconsPath . $eachmodule . ".gif")) {
                    $iconlink = ' <img src="' . $iconsPath . $eachmodule . '.gif" class="menuicon"> ';
                } else {
                    if (file_exists($iconsPath . $eachmodule . ".png")) {
                        $iconlink = ' <img src="' . $iconsPath . $eachmodule . '.png" class="menuicon"> ';
                    } else {
                        $iconlink = '';
                    }
                }
            } else {
                $iconlink = '';
            }
            if (!$icons) {
                if ($count < $maxnoicon) {
                    $mod_data = parse_ini_file($mod_path . $eachmodule);
                    $mod_name = __($mod_data['NAME']);
                    $mod_need = isset($mod_data['NEED']) ? $mod_data['NEED'] : '';

                    if ((@$globconf[$mod_need]) OR ( empty($mod_need))) {
                        $result .= '<li><a  href="?module=' . $eachmodule . '" >' . $iconlink . '' . $mod_name . '</a></li>';
                        $count++;
                    }
                }
            } else {
                $mod_data = parse_ini_file($mod_path . $eachmodule);
                $mod_name = __($mod_data['NAME']);
                $mod_need = isset($mod_data['NEED']) ? $mod_data['NEED'] : '';
                if ((@$globconf[$mod_need]) OR ( empty($mod_need))) {
                    $result .= '<li class="' . $linkClass . '"><a  href="?module=' . $eachmodule . '">' . $iconlink . __($mod_name) . '</a></li>';
                    $count++;
                }
            }
        }
    }

    if ($globconf['auth'] == 'login') {
        if (isset($globconf['INTRO_MODE'])) {
            if ($globconf['INTRO_MODE'] == '2') {
                if ((!isset($_COOKIE['upassword'])) OR ( @$_COOKIE['upassword'] == 'nopassword')) {
                    $result = zbs_IntroLoadText();
                }
            }
        }
    }


    return($result);
}

/**
 * Renders copyright data
 * 
 * @return string
 */
function zbs_CopyrightsShow() {
    $usConf = zbs_LoadConfig();
    $baseFooter = 'Powered by <a href="https://ubilling.net.ua">Ubilling</a>';
    if ((isset($usConf['ISP_NAME'])) AND ( isset($usConf['ISP_URL']))) {
        if ((!empty($usConf['ISP_NAME'])) AND ( !empty($usConf['ISP_URL']))) {
            $rawUrl = strtolower($usConf['ISP_URL']);
            if (stripos($rawUrl, 'http') === false) {
                $rawUrl = 'http://' . $rawUrl;
            } else {
                $rawUrl = $rawUrl;
            }
            $addFooter = '<a href="' . $rawUrl . '">' . $usConf['ISP_NAME'] . '</a> | ';
        } else {
            $addFooter = '';
        }
    } else {
        $addFooter = '';
    }
    $result = $addFooter . $baseFooter;
    return ($result);
}

/**
 * Pushes payment log data for finance report/cash flows
 * 
 * @param string $login
 * @param float $summ
 * @param int $cashtypeid
 * @param string $note
 */
function zbs_PaymentLog($login, $summ, $cashtypeid, $note) {
    $cashtypeid = vf($cashtypeid);
    $ctime = curdatetime();
    $userdata = zbs_UserGetStargazerData($login);
    $balance = $userdata['Cash'];
    $note = mysql_real_escape_string($note);
    $query = "INSERT INTO `payments` (`id` , `login` , `date` , `admin` , `balance` , `summ` , `cashtypeid` , `note` )
              VALUES (NULL , '" . $login . "', '" . $ctime . "', 'external', '" . $balance . "', '" . $summ . "', '" . $cashtypeid . "', '" . $note . "'); ";
    nr_query($query);
}

/**
 * Runs sgconfig in system shell
 * 
 * @param string $command
 * @param bool $debug
 * 
 * @return void
 */
function executor($command, $debug = false) {
    $globconf = zbs_LoadConfig();
    $SGCONF = $globconf['SGCONF'];
    $STG_HOST = $globconf['STG_HOST'];
    $STG_PORT = $globconf['STG_PORT'];
    $STG_LOGIN = $globconf['STG_LOGIN'];
    $STG_PASSWD = $globconf['STG_PASSWD'];
    $configurator = $SGCONF . ' set -s ' . $STG_HOST . ' -p ' . $STG_PORT . ' -a' . $STG_LOGIN . ' -w' . $STG_PASSWD . ' ' . $command;
    if ($debug) {
        print($configurator . "\n");
        print(shell_exec($configurator));
    } else {
        shell_exec($configurator);
    }
}

/**
 * Adds some funds to user account
 * 
 * @param string $login
 * @param float $cash
 * 
 * @return void
 */
function billing_addcash($login, $cash) {
    executor('-u' . $login . ' -c ' . $cash);
}

/**
 * Sets user credit
 * 
 * @param string $login
 * @param float $credit
 * 
 * @return void
 */
function billing_setcredit($login, $credit) {
    executor('-u' . $login . ' -r ' . $credit);
}

/**
 * Sets credit expire date
 * 
 * @param string $login
 * @param string $creditexpire
 * 
 * @return void
 */
function billing_setcreditexpire($login, $creditexpire) {
    executor('-u' . $login . ' -E ' . $creditexpire);
}

/**
 * Sets user account balance for some concrete value
 * 
 * @param string $login
 * @param float $cash
 * 
 * @return void
 */
function billing_setcash($login, $cash) {
    executor('-u' . $login . ' -v ' . $cash);
}

/**
 * Changes user current tariff
 * 
 * @param string $login
 * @param string $tariff
 * 
 * @return void
 */
function billing_settariff($login, $tariff) {
    executor('-u' . $login . ' -t ' . $tariff);
}

/**
 * Sets user tariff change from next month
 * 
 * @param string $login
 * @param string $tariff
 * 
 * @return void
 */
function billing_settariffnm($login, $tariff) {
    executor('-u' . $login . ' -t ' . $tariff . ':delayed');
}

/**
 * Freezes user by its login
 * 
 * @param string $login
 * 
 * @return void
 */
function billing_freeze($login) {
    executor('-u' . $login . ' -i 1');
}

/**
 * Set password to stargazer user
 * @param string $login stargazer user login
 * @param string $password  password string
 * 
 * @return void
 */
function billing_setpassword($login, $password) {
    executor('-u' . $login . ' -o ' . $password);
    ;
}

/**
 * Dummy replacement for external logging purposes
 * 
 * @return string
 */
function whoami() {
    $mylogin = 'external';
    return($mylogin);
}

/**
 * Writes some data into system event log
 * 
 * @param string $event
 * 
 * @return void
 */
function log_register($event) {
    $admin_login = whoami();
    $ip = $_SERVER['REMOTE_ADDR'];
    $current_time = curdatetime();
    $event = mysql_real_escape_string($event);
    $query = "INSERT INTO `weblogs` (`id`,`date`,`admin`,`ip`,`event`) VALUES(NULL,'" . $current_time . "','" . $admin_login . "','" . $ip . "','" . $event . "')";
    nr_query($query);
}

/**
 * Returns user account balance
 * 
 * @param string $login
 * @return float
 */
function zbs_CashGetUserBalance($login) {
    $login = vf($login);
    $query = "SELECT `Cash` from `users` WHERE `login`='" . $login . "'";
    $cash = simple_query($query);
    return($cash['Cash']);
}

/**
 * Returns user account credit limit
 * 
 * @param string $login
 * @return float
 */
function zbs_CashGetUserCredit($login) {
    $login = vf($login);
    $query = "SELECT `Credit` from `users` WHERE `login`='" . $login . "'";
    $cash = simple_query($query);
    return($cash['Credit']);
}

/**
 * Returns user credit limit expire date as timestamp
 * 
 * @param string $login
 * @return int
 */
function zbs_CashGetUserCreditExpire($login) {
    $login = vf($login);
    $query = "SELECT `CreditExpire` from `users` WHERE `login`='" . $login . "'";
    $cash = simple_query($query);
    return($cash['CreditExpire']);
}

/**
 * Returns user current tariff
 * 
 * @param string $login
 * @return string
 */
function zbs_UserGetTariff($login) {
    $login = mysql_real_escape_string($login);
    $query = "SELECT `Tariff` from `users` WHERE `login`='" . $login . "'";
    $res = simple_query($query);
    return($res['Tariff']);
}

/**
 * Returns user current tariff price
 * 
 * @param string $login
 * @return float
 */
function zbs_UserGetTariffPrice($tariff) {
    $login = mysql_real_escape_string($tariff);
    $query = "SELECT `Fee` from `tariffs` WHERE `name`='" . $tariff . "'";
    $res = simple_query($query);
    return($res['Fee']);
}

/**
 * Returns user current tariff data array
 * 
 * @param string $login
 * @return array
 */
function zbs_UserGetTariffData($tariff) {
    $login = mysql_real_escape_string($tariff);
    $query = "SELECT * from `tariffs` WHERE `name`='" . $tariff . "'";
    $res = simple_query($query);
    return($res);
}

/**
 * Adds some money to user account
 * 
 * @param string $login
 * @param float $cash
 * @param string $note
 * 
 * @return void
 */
function zbs_CashAdd($login, $cash, $note) {
    $login = vf($login);
    $cash = mysql_real_escape_string($cash);
    $cashtype = 0;
    $note = mysql_real_escape_string($note);
    $date = curdatetime();
    $balance = zbs_CashGetUserBalance($login);
    billing_addcash($login, $cash);
    $query = "INSERT INTO `payments` ( `id` , `login` , `date` , `balance` , `summ` , `cashtypeid` , `note` )
              VALUES (NULL , '" . $login . "', '" . $date . "', '" . $balance . "', '" . $cash . "', '" . $cashtype . "', '" . $note . "');";
    nr_query($query);
    log_register("BALANCEADD (" . $login . ') ON ' . $cash);
}

/**
 * Retuns all months with names in two digit notation
 * 
 * @return array
 */
function zbs_months_array() {
    $months = array(
        '01' => 'January',
        '02' => 'February',
        '03' => 'March',
        '04' => 'April',
        '05' => 'May',
        '06' => 'June',
        '07' => 'July',
        '08' => 'August',
        '09' => 'September',
        '10' => 'October',
        '11' => 'November',
        '12' => 'December');
    return($months);
}

/**
 * Retuns all months with names without leading zeros
 * 
 * @return array
 */
function zbs_months_array_wz() {
    $months = array(
        '1' => 'January',
        '2' => 'February',
        '3' => 'March',
        '4' => 'April',
        '5' => 'May',
        '6' => 'June',
        '7' => 'July',
        '8' => 'August',
        '9' => 'September',
        '10' => 'October',
        '11' => 'November',
        '12' => 'December');
    return($months);
}

/**
 * Extracts value by key from UBstorage
 * 
 * @param string $key
 * @return string
 */
function zbs_StorageGet($key) {
    $key = mysql_real_escape_string($key);
    $query = "SELECT `value` from `ubstorage` WHERE `key`='" . $key . "'";
    $fetchdata = simple_query($query);
    if (!empty($fetchdata)) {
        $result = $fetchdata['value'];
    } else {
        $result = '';
    }
    return ($result);
}

/**
 * Returns array of all users with blocked userstats access
 * 
 * @return array
 */
function zbs_GetUserStatsDeniedAll() {
    $access_raw = zbs_StorageGet('ZBS_DENIED');
    $result = array();
    if (!empty($access_raw)) {
        $access_raw = base64_decode($access_raw);
        $access_raw = unserialize($access_raw);
        $result = $access_raw;
    }
    return ($result);
}

/**
 * Returns array of all users with blocked helpdesk access
 * 
 * @return array
 */
function zbs_GetHelpdeskDeniedAll() {
    $access_raw = zbs_StorageGet('ZBS_HELP_DENIED');
    $result = array();
    if (!empty($access_raw)) {
        $access_raw = base64_decode($access_raw);
        $access_raw = unserialize($access_raw);
        $result = $access_raw;
    }
    return ($result);
}

/**
 * Rounds $value to $precision digits
 * 
 * @param $value digit to round
 * @param $precision amount of digits after point
 * 
 * @return string
 */
function web_roundValue($value, $precision = 0) {
    if ($precision < 0)
        $precision = 0;
    elseif ($precision > 4)
        $precision = 4;
    $multiplier = pow(10, $precision);
    return ($value >= 0 ? ceil($value * $multiplier) : floor($value * $multiplier)) / $multiplier;
}

/**
 * Returns array of all available tariff prices
 * 
 * @return array
 */
function zbs_TariffGetAllPrices() {
    $query = "SELECT `name`,`Fee` from `tariffs`";
    $alltariffs = simple_queryall($query);
    $result = array();
    if (!empty($alltariffs)) {
        foreach ($alltariffs as $io => $eachtariff) {
            $result[$eachtariff['name']] = $eachtariff['Fee'];
        }
    }
    return ($result);
}

/**
 * Returns ISP logo image code
 * 
 * @return string
 */
function zbs_IspLogoShow() {
    $usConf = zbs_LoadConfig();
    $result = '';
    if (isset($usConf['ISP_LOGO'])) {
        if ((!empty($usConf['ISP_NAME'])) AND ( !empty($usConf['ISP_URL'])) AND ( (!empty($usConf['ISP_LOGO'])))) {
            $rawUrl = strtolower($usConf['ISP_URL']);
            if (stripos($rawUrl, 'http') === false) {
                $rawUrl = 'http://' . $rawUrl;
            } else {
                $rawUrl = $rawUrl;
            }
            $result = '<a href="' . $rawUrl . '" target="_BLANK"><img src="' . $usConf['ISP_LOGO'] . '" title="' . $usConf['ISP_NAME'] . '"></a>';
        }
    }
    return ($result);
}

/**
 * Returns custom style background code
 * 
 * @return string
 */
function zbs_CustomBackground() {
    $usConf = zbs_LoadConfig();
    $skinPath = zbs_GetCurrentSkinPath($usConf);
    $tilesPath = $skinPath . 'tiles/';
    $result = '';
    if (isset($usConf['BACKGROUND'])) {
        if (($usConf['BACKGROUND'] != 'DEFAULT') AND ( !empty($usConf['BACKGROUND']))) {
            $customBackground = $usConf['BACKGROUND'];
            $availTiles = rcms_scandir($tilesPath);
            $availTiles = array_flip($availTiles);

            if ($customBackground == 'RANDOM') {
                $customBackground = array_rand($availTiles);
            }

            if (isset($availTiles[$customBackground])) {
                $result = '<style> body { background: #080808 url(' . $tilesPath . $customBackground . ') repeat; } </style> ';
            } else {
                $result = '<!-- Custom background tile file not found -->';
            }
        }
    }

    return ($result);
}

/**
 * Checks is some new/unread announcements available?
 * 
 * @return bool
 */
function zbs_AnnouncementsAvailable($login) {
    global $us_config;
    $login = mysql_real_escape_string($login);
    $query = "SELECT `zbsannouncements`.*, `zbh`.`annid` from `zbsannouncements` LEFT JOIN (SELECT `annid` FROM `zbsannhist` WHERE `login` = '" . $login . "') as zbh ON ( `zbsannouncements`.`id`=`zbh`.`annid`) WHERE `public`='1' AND `annid` IS NULL ORDER BY `zbsannouncements`.`id` DESC LIMIT 1";
    $data = simple_queryall($query);
    if (!empty($data)) {
        if (isset($us_config['AN_MODAL']) AND ! empty($us_config['AN_MODAL'])) {
            $inputs = '';
            $inputs .= la_tag('br');
            $inputs .= la_HiddenInput('anmarkasread', $data[0]['id']);

            if ($data[0]['type'] == 'text') {
                $eachtext = strip_tags($data[0]['text']);
                $inputs .= nl2br($eachtext);
            }

            if ($data[0]['type'] == 'html') {
                $inputs .= $data[0]['text'];
            }
            $inputs .= la_tag('br');
            $inputs .= la_tag('br');
            $inputs .= la_Submit('Mark as read');
            $form = la_Form('?module=announcements', "POST", $inputs, 'glamour');

            $result = la_modalOpened($data[0]['title'], $form);
        } else {
            $result = TRUE;
        }
    } else {
        $result = FALSE;
    }
    return ($result);
}

/**
 * Renders new/unread announcements notification
 * 
 * @return void
 */
function zbs_AnnouncementsNotice($login) {
    $result = '';
    $skinPath = zbs_GetCurrentSkinPath();
    $iconsPath = $skinPath . 'iconz/';
    $availableAnnouncements = zbs_AnnouncementsAvailable($login);
    if ($availableAnnouncements) {
        if ($availableAnnouncements !== TRUE) {
            $result .= $availableAnnouncements;
        }
        $cells = la_TableCell(la_Link('?module=announcements', la_img($iconsPath . 'alert.gif'), true, 'announcementslink'));
        $cells .= la_TableCell(la_Link('?module=announcements', __('Some announcements are available'), true, 'announcementslink'));
        $rows = la_TableRow($cells);
        $result .= la_TableBody($rows, '100%', 0, 'announcementstable');
        show_window('', $result);
    }
}

/**
 * Loads current userstats intro text
 * 
 * @return string
 */
function zbs_IntroLoadText() {
    $result = zbs_StorageGet('ZBS_INTRO');
    return ($result);
}

/**
 * Loads current freeze days charge data for user
 *
 * @param $login
 *
 * @return array
 */
function zbs_getFreezeDaysChargeData($login) {
    $FrozenAllQuery = "SELECT * FROM `frozen_charge_days` WHERE `login` = '" . $login . "';";
    $FrozenAll = simple_queryall($FrozenAllQuery);

    return $FrozenAll;
}

/**
 * Performs RemoteAPI request to preconfigured billing instance
 * 
 * @param string $requestUrl RemoteAPI request URL for example: "&action=someaction"
 * 
 * @return string
 */
function zbs_remoteApiRequest($requestUrl) {
    $usConfig = zbs_LoadConfig();
    $result = '';
    if (isset($usConfig['API_URL']) AND isset($usConfig['API_KEY'])) {
        if (!empty($usConfig['API_URL']) AND ! empty($usConfig['API_KEY'])) {
            $apiBase = $usConfig['API_URL'] . '/?module=remoteapi&key=' . $usConfig['API_KEY'];
            @$result .= file_get_contents($apiBase . $requestUrl);
        } else {
            die('ERROR: API_KEY/API_URL is empty!');
        }
    } else {
        die('ERROR: API_KEY/API_URL not set!');
    }
    return($result);
}

/**
 * Renders aerial alerts notification
 * 
 * @global array $us_config
 * 
 * @return void
 */
function zbs_AerialAlertNotification() {
    global $us_config;
    $rawData = zbs_remoteApiRequest('&action=aerialalerts');
    $skinPath = zbs_GetCurrentSkinPath($us_config);
    $iconsPath = $skinPath . 'iconz/';
    if (!empty($rawData)) {
        $alertsData = @json_decode($rawData, true);
        if (is_array($alertsData)) {
            if (!empty($alertsData)) {
                if (isset($alertsData['region']) AND isset($alertsData['alert']) AND isset($alertsData['changed'])) {
                    if ($alertsData['alert']) {
                        $alertStyle = la_tag('style');
                        $alertStyle .= '
                              .alert_aerial {
                                display: block;
                                width: 95%;
                                margin: 20px 3% 0 3%;
                                margin-top: 2px;
                                -webkit-border-radius: 5px;
                                -moz-border-radius: 5px;
                                border-radius: 5px;
                                background: #F5F3BA;
                                border: 1px solid #C7A20D;
                                color: #796616;
                                padding: 10px 0;
                                text-indent: 40px;
                                font-size: 24px;
                                } 
                            ';
                        $alertStyle .= la_tag('style', true);
                        $alertText = $alertStyle;
                        $alertText .= la_tag('div', false, 'alert_aerial');
                        $alertText .= la_img($iconsPath . 'nuclear_bomb.png', __('Aerial alert')) . ' ' . $alertsData['region'] . ' ' . __('since') . ' ' . $alertsData['changed'];
                        $alertText .= la_tag('div', true);
                        show_window(__('Aerial alert') . '!', $alertText);
                    }
                }
            } else {
                show_window(__('Error'), __('Empty reply received') . ': ' . $rawData);
            }
        } else {
            show_window(__('Error'), __('Wrong reply received') . ': ' . $rawData);
        }
    }
}
