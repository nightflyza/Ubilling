<?php

/**
 * Returns user IP by password auth
 * 
 * @param string $login
 * @param string $password
 * @return string
 */
function zbs_UserCheckLoginAuth($login, $password) {
    $login = vf($login);
    $password = vf($password);
    $query = "SELECT `IP` from `users` WHERE `login`='" . $login . "' AND MD5(`password`)='" . $password . "'";
    $data = simple_query($query);
    if (!empty($data)) {
        $result = $data['IP'];
    } else {
        $result = '';
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
    if ($debug) {
        //$ip='172.30.0.2'; 
    }

    return($ip);
}

/**
 * Returns user login by its IP address
 * 
 * @param string $ip
 * @return string
 */
function zbs_UserGetLoginByIp($ip) {
    $glob_conf = zbs_LoadConfig();
    $query = "SELECT `login` from `users` where `IP`='" . $ip . "'";
    $result = simple_query($query);
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
    $tariffFee = $tariffData['Fee'];
    $tariffPeriod = isset($tariffData['period']) ? $tariffData['period'] : 'month';

    $daysOnLine = 0;
    $balanceExpire = '';

    if ($userBalance >= 0) {
        if ($tariffFee > 0) {
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
            default:
                $balanceExpire = NULL;
                break;
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
    $inputs.= la_TextInput('ulogin', '', '', true);
    $inputs.= la_tag('label') . __('Password') . la_tag('label', true) . la_tag('br');
    $inputs.= la_PasswordInput('upassword', '', '', true);

    $inputs.= la_Submit(__('Enter'));
    $form = la_Form('', 'POST', $inputs, 'loginform');

    $cells = la_TableCell($form, '', '', 'align="center"');
    $rows = la_TableRow($cells);
    $result = la_TableBody($rows, '100%', 0);

    show_window(__('Login with your account'), $result);
}

/**
 * Renders user logout form (only for login auth)
 * 
 * @return void
 */
function zbs_LogoutForm() {
    $inputs = la_HiddenInput('ulogout', 'true');
    $inputs.= la_Submit(__('Logout'));
    $form = la_Form('', 'POST', $inputs);
    show_window('', $form);
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
            $inputs.= la_tag('option', false, '', 'value="-"') . __('Language') . la_tag('option', true);
            foreach ($allangs as $eachlang) {
                $eachlangid = file_get_contents("languages/" . $eachlang . "/langid.txt");
                $inputs.= la_tag('option', false, '', 'value="' . $eachlang . '"') . $eachlangid . la_tag('option', true);
            }
            $inputs.=la_tag('select', true);
            $inputs.=' ';
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
    $apts = array();
    $builds = array();
    $city_q = "SELECT * from `city`";
    $adrz_q = "SELECT * from `address`";
    $apt_q = "SELECT * from `apt`";
    $build_q = "SELECT * from build";
    $streets_q = "SELECT * from `street`";
    $alladdrz = simple_queryall($adrz_q);
    $allapt = simple_queryall($apt_q);
    $allbuilds = simple_queryall($build_q);
    $allstreets = simple_queryall($streets_q);
    if (!empty($alladdrz)) {
        $cities = zbs_AddressGetFullCityNames();

        foreach ($alladdrz as $io1 => $eachaddress) {
            $address[$eachaddress['id']] = array('login' => $eachaddress['login'], 'aptid' => $eachaddress['aptid']);
        }
        foreach ($allapt as $io2 => $eachapt) {
            $apts[$eachapt['id']] = array('apt' => $eachapt['apt'], 'buildid' => $eachapt['buildid']);
        }
        foreach ($allbuilds as $io3 => $eachbuild) {
            $builds[$eachbuild['id']] = array('buildnum' => $eachbuild['buildnum'], 'streetid' => $eachbuild['streetid']);
        }
        foreach ($allstreets as $io4 => $eachstreet) {
            $streets[$eachstreet['id']] = array('streetname' => $eachstreet['streetname'], 'cityid' => $eachstreet['cityid']);
        }

        foreach ($address as $io5 => $eachaddress) {
            $apartment = $apts[$eachaddress['aptid']]['apt'];
            $building = $builds[$apts[$eachaddress['aptid']]['buildid']]['buildnum'];
            $streetname = $streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['streetname'];
            $cityid = $streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['cityid'];
            // zero apt handle
            if ($alterconf['ZERO_TOLERANCE']) {
                if ($apartment == 0) {
                    $apartment_filtered = '';
                } else {
                    $apartment_filtered = '/' . $apartment;
                }
            } else {
                $apartment_filtered = '/' . $apartment;
            }

            if (!$alterconf['CITY_DISPLAY']) {
                $result[$eachaddress['login']] = $streetname . ' ' . $building . $apartment_filtered;
            } else {
                $result[$eachaddress['login']] = $cities[$cityid] . ', ' . $streetname . ' ' . $building . $apartment_filtered;
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
                $payments.='<cell' . $cn . ' row="' . $i . '" text="' . $eachpayment['date'] . '" column="1"/>' . "\n";
                $cn++;
                $payments.='<cell' . $cn . ' row="' . $i . '" text="' . $eachpayment['summ'] . '" column="2"/>' . "\n";
                $cn++;
                $payments.='<cell' . $cn . ' row="' . $i . '" text="' . $eachpayment['balance'] . '" column="3"/>' . "\n";
                $i++;
            }
        }
        $payments.='</cells>
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
                $payments.=$eachpayment['date'] . ' ' . $eachpayment['summ'] . ' ' . $eachpayment['balance'] . "\n";
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
                $msg_result.=$eachmessage['date'] . "\r\n";
                $msg_result.=$eachmessage['text'] . "\r\n";
                $msg_result.="\n";
                $msg_result.="\n";
                $msg_result.="\n";
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
    $result.='fulladdress=' . @$alladdress[$login] . "\n";
    $result.='realname=' . @$allrealnames[$login] . "\n";
    $result.='login=' . $login . "\n";
    $result.='password=' . @$userdata['Password'] . "\n";
    $result.='cash=' . @round($userdata['Cash'], 2) . "\n";
    $result.='login=' . $login . "\n";
    $result.='password=' . @$userdata['Password'] . "\n";
    $result.='ip=' . @$userdata['IP'] . "\n";
    $result.='phone=' . $phone . "\n";
    $result.='mobile=' . $mobile . "\n";
    $result.='email=' . $email . "\n";
    $result.='credit=' . @$userdata['Credit'] . "\n";
    $result.='creditexpire=' . $credexpire . "\n";
    $result.='payid=' . ip2int($userdata['IP']) . "\n";
    $result.='contract=' . $contract . "\n";
    $result.='tariff=' . $userdata['Tariff'] . "\n";
    $result.='tariffnm=' . $userdata['TariffChange'] . "\n";
    $result.='traffd=' . $traffdgb . "\n";
    $result.='traffu=' . $traffugb . "\n";
    $result.='traffd_conv=' . zbs_convert_size($traffdown) . "\n";
    $result.='traffu_conv=' . zbs_convert_size($traffup) . "\n";
    $result.='trafftotal_conv=' . zbs_convert_size($traffdown + $traffup) . "\n";



    $result.="\n";
    $result.='[CONF]' . "\n";
    $result.='currency=' . $us_currency;
    print($result);
    die();
}

/**
 * Returns XML-agent user data
 * 
 * @param string $login
 */
function zbs_UserShowXmlAgentData($login) {
    $us_config = zbs_LoadConfig();
    if (isset($_GET['payments'])) {
        if ($us_config['PAYMENTS_ENABLED']) {
            $allpayments = zbs_CashGetUserPayments($login);

            $payments = '<?xml version="1.0" encoding="utf-8"?>
<data>' . "\n";
            if (!empty($allpayments)) {
                foreach ($allpayments as $io => $eachpayment) {
                    $payments.='<payment>' . "\n";
                    $payments.="\t" . '<date>' . $eachpayment['date'] . '</date>' . "\n";
                    $payments.="\t" . '<summ>' . $eachpayment['summ'] . '</summ>' . "\n";
                    $payments.="\t" . '<balance>' . $eachpayment['balance'] . '</balance>' . "\n";
                    $payments.='</payment>' . "\n";
                }
            }
            $payments.='</data>' . "\n";
        } else {
            $payments = '<?xml version="1.0" encoding="utf-8"?>' . "\n" . '<data>' . "\n" . '</data>' . "\n";
        }
        header('Last-Modified: ' . gmdate('r'));
        header('Content-Type: text/html; charset=utf-8');
        header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
        header("Pragma: no-cache");
        header('Access-Control-Allow-Origin: *');
        die($payments);
    }


    if (isset($_GET['announcements'])) {
        if ($us_config['AN_ENABLED']) {
            $announcements_query = "SELECT * from `zbsannouncements` WHERE `public`='1' ORDER by `id` DESC";
            $allAnnouncements = simple_queryall($announcements_query);

            $announcements = '<?xml version="1.0" encoding="utf-8"?>
<data>' . "\n";
            if (!empty($allAnnouncements)) {
                foreach ($allAnnouncements as $ian => $eachAnnouncement) {
                    $annText = strip_tags($eachAnnouncement['text']);
                    $allTitle = strip_tags($eachAnnouncement['title']);
                    $announcements.='<message unic="' . $eachAnnouncement['id'] . '" title="' . $allTitle . '">' . $annText . '</message>' . "\n";
                }
            }
            $announcements.='</data>' . "\n";
        } else {
            $announcements = '<?xml version="1.0" encoding="utf-8"?>' . "\n" . '<data>' . "\n" . '</data>' . "\n";
        }
        header('Last-Modified: ' . gmdate('r'));
        header('Content-Type: text/html; charset=utf-8');
        header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
        header("Pragma: no-cache");
        header('Access-Control-Allow-Origin: *');
        die($announcements);
    }

    $us_currency = $us_config['currency'];
    $userdata = zbs_UserGetStargazerData($login);
    $alladdress = zbs_AddressGetFulladdresslist();
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


    $result = '<?xml version="1.0" encoding="utf-8"?>
<userdata>' . "\n";
    $result.="\t" . '<address>' . @$alladdress[$login] . '</address>' . "\n";
    $result.="\t" . '<realname>' . @$allrealnames[$login] . '</realname>' . "\n";
    $result.="\t" . '<login>' . $login . '</login>' . "\n";
    $result.="\t" . '<cash>' . @round($userdata['Cash'], 2) . '</cash>' . "\n";
    $result.="\t" . '<ip>' . @$userdata['IP'] . '</ip>' . "\n";
    $result.="\t" . '<phone>' . $phone . '</phone>' . "\n";
    $result.="\t" . '<mobile>' . $mobile . '</mobile>' . "\n";
    $result.="\t" . '<email>' . $email . '</email>' . "\n";
    $result.="\t" . '<credit>' . @$userdata['Credit'] . '</credit>' . "\n";
    $result.="\t" . '<creditexpire>' . $credexpire . '</creditexpire>' . "\n";
    $result.="\t" . '<payid>' . $paymentid . '</payid>' . "\n";
    $result.="\t" . '<contract>' . $contract . '</contract>' . "\n";
    $result.="\t" . '<tariff>' . $userdata['Tariff'] . '</tariff>' . "\n";
    $result.="\t" . '<tariffnm>' . $tariffNm . '</tariffnm>' . "\n";
    $result.="\t" . '<traffdownload>' . zbs_convert_size($traffdown) . '</traffdownload>' . "\n";
    $result.="\t" . '<traffupload>' . zbs_convert_size($traffup) . '</traffupload>' . "\n";
    $result.="\t" . '<trafftotal>' . zbs_convert_size($traffdown + $traffup) . '</trafftotal>' . "\n";
    $result.="\t" . '<accountstate>' . $passive_state . $down_state . '</accountstate>' . "\n";
    $result.="\t" . '<accountexpire>' . $balanceExpire . '</accountexpire>' . "\n";
    $result.="\t" . '<currency>' . $us_currency . '</currency>' . "\n";
    $result.="\t" . '<version>' . $apiVer . '</version>' . "\n";


    $result.='</userdata>' . "\n";

    header('Last-Modified: ' . gmdate('r'));
    header('Content-Type: text/html; charset=utf-8');
    header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
    header("Pragma: no-cache");
    header('Access-Control-Allow-Origin: *');

    die($result);
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
 * @return string
 */
function zbs_TariffGetSpeed($tariff) {
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
        } else {
            $result = __('Unlimited');
        }
    } else {
        $result = __('None');
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
 * Renders list of associated with user virtual services 
 * 
 * @param string $login
 * @param string $currency
 * @return string
 */
function zbs_vservicesShow($login, $currency) {
    $result = '';
    $userservices = array();
    $allservices = zbs_getVservicesAll(); // tagid => price
    if (!empty($allservices)) {
        $usertags = zbs_getUserTags($login); // tagid=>dbid
        if (!empty($usertags)) {
            foreach ($usertags as $eachtagid => $dbid) {
                //is associated tags services?
                if (isset($allservices[$eachtagid])) {
                    $userservices[$eachtagid] = $dbid;
                }
            }

            //yep, this user have some services assigned
            if (!empty($userservices)) {
                $tagnames = zbs_getTagNames(); //tagid => name

                $cells = la_TableCell(__('Service'));
                $cells.= la_TableCell(__('Price'));
                $rows = la_TableRow($cells, 'row1');

                foreach ($userservices as $eachservicetagid => $dbid) {
                    $cells = la_TableCell(@$tagnames[$eachservicetagid]);
                    $cells.= la_TableCell(@$allservices[$eachservicetagid] . ' ' . $currency);
                    $rows.= la_TableRow($cells, 'row3');
                }

                $result.= la_tag('br');
                $result.= la_tag('h3') . __('Additional services') . la_tag('h3', true);
                $result.= la_TableBody($rows, '100%', 0);
            }
        }
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
                $cells.= la_TableCell($discount . '%');
                $result = la_TableRow($cells);
            }
        }
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

    //public offer mode
    if (isset($us_config['PUBLIC_OFFER'])) {
        if (!empty($us_config['PUBLIC_OFFER'])) {
            $publicOfferUrl = $us_config['PUBLIC_OFFER'];
            $contract = la_Link($publicOfferUrl, __('Public offer'), false, '');
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

    //hiding passwords
    if ($us_config['PASSWORDSHIDE']) {
        $userpassword = str_repeat('*', 8);
    } else {
        $userpassword = $userdata['Password'];
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
            $paymentidqr = la_modal(la_img('iconz/qrcode.png', 'QR-code'), __('Payment ID'), la_tag('center') . la_img('qrgen.php?data=' . $paymentid) . la_tag('center', true), '', '300', '250');
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
        $speedOffset = 1024;
        $userSpeedOverride = zbs_SpeedGetOverride($login);
        if ($userSpeedOverride == 0) {
            $showSpeed = zbs_TariffGetSpeed($userdata['Tariff']);
        } else {
            if ($userSpeedOverride < $speedOffset) {
                $showSpeed = $userSpeedOverride . ' ' . __('Kbit/s');
            } else {
                $showSpeed = ($userSpeedOverride / $speedOffset) . ' ' . __('Mbit/s');
            }
        }

        $tariffSpeeds = la_TableRow(la_TableCell(__('Tariff speed'), '', 'row1') . la_TableCell($showSpeed));
    } else {
        $tariffSpeeds = '';
    }


    if ($us_config['ROUND_PROFILE_CASH']) {
        $Cash = web_roundValue($userdata['Cash'], 2);
    } else
        $Cash = $userdata['Cash'];

    $profile = la_tag('table', false, '', 'width="100%" border="0" cellpadding="2" cellspacing="3"');
    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('Address'), '', 'row1');
    $profile.= la_TableCell(@$alladdress[$login]);
    $profile.= la_tag('tr', true);

    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('Real name'), '', 'row1');
    $profile.= la_TableCell(@$allrealnames[$login]);
    $profile.= la_tag('tr', true);

    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('Login'), '', 'row1');
    $profile.= la_TableCell($login);
    $profile.= la_tag('tr', true);

    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('Password'), '', 'row1');
    $profile.= la_TableCell($userpassword);
    $profile.= la_tag('tr', true);

    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('IP'), '', 'row1');
    $profile.= la_TableCell($userdata['IP']);
    $profile.= la_tag('tr', true);

    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('Phone'), '', 'row1');
    $profile.= la_TableCell($phone);
    $profile.= la_tag('tr', true);

    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('Mobile'), '', 'row1');
    $profile.= la_TableCell($mobile);
    $profile.= la_tag('tr', true);

    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('Email'), '', 'row1');
    $profile.= la_TableCell($email);
    $profile.= la_tag('tr', true);

    $profile.= la_tag('tr');
    $payIdAbbr = la_tag('abbr', false, '', 'title="' . __('Payment ID is used to make online payments using a variety of payment systems as well as the funding of accounts using the terminals') . '"');
    $payIdAbbr.=__('Payment ID');
    $payIdAbbr.=la_tag('abbr', true);

    $profile.= la_TableCell($payIdAbbr, '', 'row1');
    $profile.= la_TableCell($paymentid . ' ' . $paymentidqr);
    $profile.= la_tag('tr', true);

    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('Contract'), '', 'row1');
    $profile.= la_TableCell($contract);
    $profile.= la_tag('tr', true);

    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('Balance'), '', 'row1');
    $profile.= la_TableCell($Cash . ' ' . $us_currency . $balanceExpire . $zdocsLink);
    $profile.= la_tag('tr', true);

    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('Credit'), '', 'row1');
    $profile.= la_TableCell($userdata['Credit'] . ' ' . $us_currency);
    $profile.= la_tag('tr', true);

    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('Credit Expire'), '', 'row1');
    $profile.= la_TableCell($credexpire);
    $profile.= la_tag('tr', true);

    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('Tariff'), '', 'row1');
    $profile.= la_TableCell(__($userdata['Tariff']));
    $profile.= la_tag('tr', true);

    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('Tariff price'), '', 'row1');
    $profile.= la_TableCell(@zbs_UserGetTariffPrice($userdata['Tariff']) . ' ' . $us_currency);
    $profile.= la_tag('tr', true);

    $profile.= $tariffSpeeds;

    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('Tariff change'), '', 'row1');
    $profile.= la_TableCell(__($userdata['TariffChange']));
    $profile.= la_tag('tr', true);

    $profile.= la_tag('tr');
    $profile.= la_TableCell(__('Account state'), '', 'row1');
    $profile.= la_TableCell($passive_state . $down_state);
    $profile.= la_tag('tr', true);

    $profile.=zbs_CUDShow($login, $us_config);

    $profile.=la_tag('table', true);

    //show assigned virtual services if available
    if (isset($us_config['VSERVICES_SHOW'])) {
        if ($us_config['VSERVICES_SHOW']) {
            $profile.=zbs_vservicesShow($login, $us_currency);
        }
    }

    return($profile);
}

/**
 * Returns payments array for some user
 * 
 * @param string $login
 * @return array
 */
function zbs_CashGetUserPayments($login) {
    $login = vf($login);
    $query = "SELECT * from `payments` WHERE `login`='" . $login . "' ORDER BY `id` DESC";
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
    $login = vf($login);
    $alldirs = zbs_DirectionsGetAll();
    $monthnames = zbs_months_array_wz();

    /*
     * Current month traffic stats
     */
    $result = la_tag('h3') . __('Current month traffic stats') . la_tag('h3', true);

    $cells = la_TableCell(__('Traffic classes'));
    $cells.= la_TableCell(__('Downloaded'));
    $cells.= la_TableCell(__('Uploaded'));
    $cells.= la_TableCell(__('Total'));
    $rows = la_TableRow($cells, 'row1');


    if (!empty($alldirs)) {
        foreach ($alldirs as $io => $eachdir) {
            $query_downup = "SELECT `D" . $eachdir['rulenumber'] . "`,`U" . $eachdir['rulenumber'] . "` from `users` WHERE `login`='" . $login . "'";
            $downup = simple_query($query_downup);
            $cells = la_TableCell($eachdir['rulename']);
            $cells.= la_TableCell(zbs_convert_size($downup['D' . $eachdir['rulenumber']]));
            $cells.= la_TableCell(zbs_convert_size($downup['U' . $eachdir['rulenumber']]));
            $cells.= la_TableCell(zbs_convert_size(($downup['U' . $eachdir['rulenumber']] + $downup['D' . $eachdir['rulenumber']])));
            $rows.= la_TableRow($cells, 'row3');
        }
    }

    $result.= la_TableBody($rows, '100%', 0, '');
    $result.= la_delimiter();


    /*
     * traffic stats by previous months
     */
    $result.=la_tag('h3') . __('Previous month traffic stats') . la_tag('h3', true);


    $cells = la_TableCell(__('Year'));
    $cells.= la_TableCell(__('Month'));
    $cells.= la_TableCell(__('Traffic classes'));
    $cells.= la_TableCell(__('Downloaded'));
    $cells.= la_TableCell(__('Uploaded'));
    $cells.= la_TableCell(__('Total'));
    $cells.= la_TableCell(__('Cash'));
    $rows = la_TableRow($cells, 'row1');


    if (!empty($alldirs)) {
        foreach ($alldirs as $io => $eachdir) {
            $query_prev = "SELECT `D" . $eachdir['rulenumber'] . "`,`U" . $eachdir['rulenumber'] . "`,`month`,`year`,`cash` from `stat` WHERE `login`='" . $login . "'  ORDER BY `year`,`month`";
            $allprevmonth = simple_queryall($query_prev);
            if (!empty($allprevmonth)) {
                foreach ($allprevmonth as $io2 => $eachprevmonth) {
                    $cells = la_TableCell($eachprevmonth['year']);
                    $cells.= la_TableCell(__($monthnames[$eachprevmonth['month']]));
                    $cells.= la_TableCell($eachdir['rulename']);
                    $cells.= la_TableCell(zbs_convert_size($eachprevmonth['D' . $eachdir['rulenumber']]));
                    $cells.= la_TableCell(zbs_convert_size($eachprevmonth['U' . $eachdir['rulenumber']]));
                    $cells.= la_TableCell(zbs_convert_size(($eachprevmonth['U' . $eachdir['rulenumber']] + $eachprevmonth['D' . $eachdir['rulenumber']])));
                    $cells.= la_TableCell(round($eachprevmonth['cash'], 2));
                    $rows.= la_TableRow($cells, 'row3');
                }
            }
        }
    }
    $result.=la_TableBody($rows, '100%', 0, '');

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
    $all_modules = rcms_scandir($mod_path);
    $count = 1;
    $result = '';
    if (!empty($all_modules)) {
        foreach ($all_modules as $eachmodule) {
            if ($icons == true) {
                if (file_exists("iconz/" . $eachmodule . ".gif")) {
                    $iconlink = ' <img src="iconz/' . $eachmodule . '.gif" class="menuicon"> ';
                } else {
                    $iconlink = '';
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
                        $result.='<li><a  href="?module=' . $eachmodule . '">' . $iconlink . '' . $mod_name . '</a></li>';
                        $count++;
                    }
                }
            } else {
                $mod_data = parse_ini_file($mod_path . $eachmodule);
                $mod_name = __($mod_data['NAME']);
                $mod_need = isset($mod_data['NEED']) ? $mod_data['NEED'] : '';
                if ((@$globconf[$mod_need]) OR ( empty($mod_need))) {
                    $result.='<li class="menublock"><a  href="?module=' . $eachmodule . '">' . $iconlink . '' . __($mod_name) . '</a></li>';
                    $count++;
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
    $baseFooter = 'Powered by <a href="http://ubilling.net.ua">Ubilling</a>';
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
    $balance = zb_CashGetUserBalance($login);
    billing_addcash($login, $cash);
    $query = "INSERT INTO `payments` ( `id` , `login` , `date` , `balance` , `summ` , `cashtypeid` , `note` )
                VALUES (NULL , '" . $login . "', '" . $date . "', '" . $balance . "', '" . $cash . "', '" . $cashtype . "', '" . $note . ");";

    nr_query($query);
    log_register("BALANCECHANGE (" . $login . ') ON ' . $cash);
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
    $tilesPath = 'tiles/';
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
                $result = '<style> body { background: #080808 url(' . $tilesPath . '/' . $customBackground . ') repeat; } </style> ';
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
function zbs_AnnouncementsAvailable() {
    $query = "SELECT `id` from `zbsannouncements` WHERE `public`='1';";
    $data = simple_queryall($query);
    $result = false;
    if (!empty($data)) {
        foreach ($data as $io => $each) {
            if (!isset($_COOKIE['zbsanread_' . $each['id']])) {
                $result = true;
                break;
            }
        }
    } else {
        $result = false;
    }
    return ($result);
}

/**
 * Renders new/unread announcements notification
 * 
 * @return void
 */
function zbs_AnnouncementsNotice() {
    $result = '';
    if (zbs_AnnouncementsAvailable()) {
        $cells = la_TableCell(la_Link('?module=announcements', la_img('iconz/alert.gif'), true, ''));
        $cells.= la_TableCell(la_Link('?module=announcements', __('Some announcements are available'), true, ''));
        $rows = la_TableRow($cells);
        $result.=la_TableBody($rows, '70%', 0, '');
        show_window('', $result);
    }
}

?>
