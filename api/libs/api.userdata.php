<?php

// userdata getters/setters

/**
 * Creates user realname database record
 * 
 * @param string $login existing user login
 * @param string $realname user realname to create
 */
function zb_UserCreateRealName($login, $realname) {
    $login = vf($login);
    $realname = mysql_real_escape_string($realname);
    $query = "INSERT INTO `realname`  (`id`,`login`,`realname`) VALUES   (NULL, '" . $login . "','" . $realname . "'); ";
    nr_query($query);
    log_register('CREATE UserRealName (' . $login . ')  `' . $realname . '`');
    zb_UserGetAllDataCacheClean();
}

/**
 * Deletes user realname from database
 * 
 * @param string $login existing user login
 */
function zb_UserDeleteRealName($login) {
    $login = vf($login);
    $query = "DELETE from `realname` WHERE `login` = '" . $login . "';";
    nr_query($query);
    log_register('DELETE UserRealName (' . $login . ')');
    zb_UserGetAllDataCacheClean();
}

/**
 * Returns realname field by users login
 * 
 * @param string $login existing user login
 * @return string
 */
function zb_UserGetRealName($login) {
    $login = vf($login);
    $query = "SELECT `realname` from `realname` WHERE `login`='" . $login . "'";
    $realname_arr = simple_query($query);
    return($realname_arr['realname']);
}

/**
 * Changes realname database record
 * 
 * @param string $login existing user login
 * @param string $realname user realname to set
 */
function zb_UserChangeRealName($login, $realname) {
    $login = vf($login);
    $realname = str_replace("'", '`', $realname);
    $realname = str_replace('"', '``', $realname);
    $realname = str_replace('\\', '', $realname);
    $realname = mysql_real_escape_string($realname);

    $query = "UPDATE `realname` SET `realname` = '" . $realname . "' WHERE `login`= '" . $login . "' ;";
    nr_query($query);
    log_register('CHANGE UserRealName (' . $login . ')   `' . $realname . '`');
    zb_UserGetAllDataCacheClean();
}

/**
 * Returns all of users realnames records as login=>realname array
 * 
 * @return array
 */
function zb_UserGetAllRealnames() {
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
 * Selects all users' IP addresses from database as login=>ip array
 * 
 * @return  array
 */
function zb_UserGetAllIPs() {
    $query = "SELECT `login`,`IP` from `users`";
    $result = simple_queryall($query);
    $ips = array();
    if (!empty($result)) {
        foreach ($result as $ip) {
            $ips[$ip['login']] = $ip['IP'];
        }
    }
    return $ips;
}

/**
 * Returns all of IP=>MAC nethosts bindings from database as array ip=>mac
 * 
 * @return array
 */
function zb_UserGetAllIpMACs() {
    $query = "SELECT `ip`,`mac` from `nethosts`";
    $all = simple_queryall($query);
    $result = array();
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['ip']] = $each['mac'];
        }
    }
    return($result);
}

/**
 * Flushes USER_ALL_DATA cache
 * 
 * @return void
 */
function zb_UserGetAllDataCacheClean() {
    $cache = new UbillingCache();
    $cache->delete('USER_ALL_DATA');
}

/**
 * Returns cache for functionzb_UserGetAllData
 * Use this function only when not set $login
 * 
 * @param string $login existing user login
 * @return array
 */
function zb_UserGetAllDataCache() {
    $result = '';
    $cache = new UbillingCache();
    $cacheTime = 86400;
    $result = $cache->getCallback('USER_ALL_DATA', function () {
        return (zb_UserGetAllData());
    }, $cacheTime);

    return ($result);
}

/**
 * Returns all information about User by login
 * 
 * @param string $login existing user login
 * @return array['login']=>array(login,realname,Passive,AlwaysOnline,Tariff,Credit,Cash,ip,mac,cityname,streetname,buildnum,entrance,floor,apt,geo,fulladress,phone,mobile,contract)
 * Crazy Pautina
 */
function zb_UserGetAllData($login = '') {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $result = array();
    $query_wh = (!empty($login)) ? "WHERE `users`.`login` = '" . vf($login) . "'" : "";
    $query = "
            SELECT `users`.`login`, `realname`.`realname`, `Passive`, `AlwaysOnline`, `Tariff`, `Credit`, `Cash`,
                    `ip`, `mac`, `cityname`, `streetname`, `buildnum`, `entrance`, `floor`, `apt`, `geo`,";
    if ($altCfg['ZERO_TOLERANCE'] and $altCfg['CITY_DISPLAY']) {
        $query .="concat(`cityname`, ' ', `streetname`, ' ', `buildnum`, IF(`apt`, concat('/',`apt`), '')) AS `fulladress`,";
    } elseif ($altCfg['ZERO_TOLERANCE'] and ! $altCfg['CITY_DISPLAY']) {
        $query .="concat(`streetname`, ' ', `buildnum`, IF(`apt`, concat('/',`apt`), '')) AS `fulladress`,";
    } elseif (!$altCfg['ZERO_TOLERANCE'] and $altCfg['CITY_DISPLAY']) {
        $query .="concat(`cityname`, ' ', `streetname`, ' ', `buildnum`, '/', `apt`) AS `fulladress`,";
    } else {
        $query .="concat(`streetname`, ' ', `buildnum`, '/', `apt`) AS `fulladress`,";
    }
    $query .="
                    `phones`.`phone`,`mobile`,`contract`,`emails`.`email`
                    FROM `users` LEFT JOIN `nethosts` USING (`ip`)
                    LEFT JOIN `realname` ON (`users`.`login`=`realname`.`login`)
                    LEFT JOIN `address` ON (`users`.`login`=`address`.`login`)
                    LEFT JOIN `apt` ON (`address`.`aptid`=`apt`.`id`)
                    LEFT JOIN `build` ON (`apt`.`buildid`=`build`.`id`)
                    LEFT JOIN `street` ON (`build`.`streetid`=`street`.`id`)
                    LEFT JOIN `city` ON (`street`.`cityid`=`city`.`id`)
                    LEFT JOIN `phones` ON (`users`.`login`=`phones`.`login`)
                    LEFT JOIN `contracts` ON (`users`.`login`=`contracts`.`login`)
                    LEFT JOIN `emails` ON (`users`.`login`=`emails`.`login`)
                    " . $query_wh;
    $Alldata = (!empty($login)) ? simple_query($query) : simple_queryall($query);
    if (empty($login) and ! empty($Alldata)) {
        foreach ($Alldata as $data) {
            $result[$data['login']] = $data;
        }
    } else {
        $result[$login] = $Alldata;
    }
    return($result);
}

/**
 * Returns all of used by users MAC bindings from database as array login=>mac
 * 
 * @return array
 */
function zb_UserGetAllMACs() {
    $alluserips = zb_UserGetAllIPs();
    $alluserips = array_flip($alluserips);
    $allmac = zb_UserGetAllIpMACs();

    $result = array();

    //filling mac array
    if (!empty($allmac)) {
        foreach ($allmac as $eachip => $eachmac) {

            if (isset($alluserips[$eachip])) {
                $result[$alluserips[$eachip]] = $eachmac;
            }
        }
    }

    return ($result);
}

/**
 * Creates phone data database field
 * 
 * @param string $login existing user login
 * @param string $phone phone number to set
 * @param string $mobile mobile number to set
 */
function zb_UserCreatePhone($login, $phone, $mobile) {
    $login = vf($login);
    $phone = mysql_real_escape_string($phone);
    $mobile = mysql_real_escape_string($mobile);
    $query = "INSERT INTO `phones`  (`id`,`login`,`phone`,`mobile`)  VALUES  (NULL, '" . $login . "','" . $phone . "','" . $mobile . "');";
    nr_query($query);
    log_register('CREATE UserPhone (' . $login . ') `' . $phone . '` `' . $mobile . '`');
    zb_UserGetAllDataCacheClean();
}

/**
 * Deletes phonedata record from database
 * 
 * @param string $login existing user login
 */
function zb_UserDeletePhone($login) {
    $login = vf($login);
    $query = "DELETE from `phones` WHERE `login` = '" . $login . "';";
    nr_query($query);
    log_register('DELETE UserPhone (' . $login . ')');
    zb_UserGetAllDataCacheClean();
}

/**
 * Returns phone number from database for some login
 * 
 * @param string $login existing user login
 * @return string
 */
function zb_UserGetPhone($login) {
    $query = "SELECT `phone` from `phones` WHERE `login`='" . $login . "'";
    $phone_arr = simple_query($query);
    return($phone_arr['phone']);
}

/**
 * Returns mobile number from database for some login
 * 
 * @param string $login existing user login
 * @return string
 */
function zb_UserGetMobile($login) {
    $query = "SELECT `mobile` from `phones` WHERE `login`='" . $login . "'";
    $phone_arr = simple_query($query);
    return($phone_arr['mobile']);
}

/**
 * Changes user phone in database
 * 
 * @param string $login existing user login
 * @param string $phone phone number to set
 */
function zb_UserChangePhone($login, $phone) {
    $login = vf($login);
    $phone = mysql_real_escape_string($phone);
    $query = "UPDATE `phones` SET `phone` = '" . $phone . "' WHERE `login`= '" . $login . "' ;";
    nr_query($query);
    log_register('CHANGE UserPhone (' . $login . ') `' . $phone . '`');
    zb_UserGetAllDataCacheClean();
}

/**
 * Changes mobile number in database
 * 
 * @param string $login existing user login
 * @param string $mobile mobile number to set
 */
function zb_UserChangeMobile($login, $mobile) {
    $login = vf($login);
    $mobile = mysql_real_escape_string($mobile);
    $query = "UPDATE `phones` SET `mobile` = '" . $mobile . "' WHERE `login`= '" . $login . "' ;";
    nr_query($query);
    log_register('CHANGE UserMobile (' . $login . ') `' . $mobile . '`');
    zb_UserGetAllDataCacheClean();
}

/**
 * Returns all users phone data as array login=>phonedata array(phone+mobile)
 * 
 * @return array
 */
function zb_UserGetAllPhoneData() {
    $query = "SELECT `login`, `phone`,`mobile` FROM `phones`";
    $result = simple_queryall($query);
    $phones = array();
    if (!empty($result)) {
        foreach ($result as $phone) {
            $phones[$phone['login']]['phone'] = $phone['phone'];
            $phones[$phone['login']]['mobile'] = $phone['mobile'];
        }
    }
    return ($phones);
}

/**
 * Creates user email database record
 * 
 * @param string $login existing user login
 * @param string $email user email to set
 */
function zb_UserCreateEmail($login, $email) {
    $login = vf($login);
    $email = mysql_real_escape_string($email);
    $query = "INSERT INTO `emails`  (`id`,`login`,`email`) VALUES  (NULL, '" . $login . "','" . $email . "');";
    nr_query($query);
    log_register('CREATE UserEmail (' . $login . ') `' . $email . '`');
    zb_UserGetAllDataCacheClean();
}

/**
 * Deletes user email record from database
 * 
 * @param string $login existing user login
 */
function zb_UserDeleteEmail($login) {
    $login = vf($login);
    $query = "DELETE from `emails` WHERE `login` = '" . $login . "';";
    nr_query($query);
    log_register('DELETE UserEmail (' . $login . ')');
    zb_UserGetAllDataCacheClean();
}

/**
 * Returns user email from database
 * 
 * @param string $login existing user login
 * @return string
 */
function zb_UserGetEmail($login) {
    $login = vf($login);
    $query = "SELECT `email` from `emails` WHERE `login`='" . $login . "'";
    $email_arr = simple_query($query);
    return($email_arr['email']);
}

/**
 * Changes user email record in database
 * 
 * @param string $login existing user login
 * @param string $email user email to set
 */
function zb_UserChangeEmail($login, $email) {
    $login = vf($login);
    $email = mysql_real_escape_string($email);
    $query = "UPDATE `emails` SET `email` = '" . $email . "' WHERE `login`= '" . $login . "' ;";
    nr_query($query);
    log_register('CHANGE UserEmail (' . $login . ') ' . $email);
    zb_UserGetAllDataCacheClean();
}

/**
 * Creates user contract database record
 * 
 * @param string $login existing user login
 * @param string $contract contract field to set
 */
function zb_UserCreateContract($login, $contract) {
    $login = vf($login);
    $contract = mysql_real_escape_string($contract);
    $query = "INSERT INTO `contracts` (`id`,`login`,`contract`)  VALUES  (NULL, '" . $login . "','" . $contract . "');";
    nr_query($query);
    log_register('CREATE UserContract (' . $login . ') `' . $contract . '`');
    zb_UserGetAllDataCacheClean();
}

/**
 * Deletes user contract record from database
 * 
 * @param string $login existing user login
 */
function zb_UserDeleteContract($login) {
    $login = vf($login);
    $query = "DELETE from `contracts` WHERE `login` = '" . $login . "';";
    nr_query($query);
    log_register('DELETE UserContract (' . $login . ')');
    zb_UserGetAllDataCacheClean();
}

/**
 * Returns user contract from database
 * 
 * @param string $login existing user login
 * @return string
 */
function zb_UserGetContract($login) {
    $login = vf($login);
    $query = "SELECT `contract` from `contracts` WHERE `login`='" . $login . "'";
    $contract_arr = simple_query($query);
    return($contract_arr['contract']);
}

/**
 * Changes new contract for some user in database
 * 
 * @param string $login existing user login
 * @param string $contract user contract to set
 */
function zb_UserChangeContract($login, $contract) {
    $login = vf($login);
    $contract = mysql_real_escape_string($contract);
    $query = "UPDATE `contracts` SET `contract` = '" . $contract . "' WHERE `login`= '" . $login . "' ;";
    nr_query($query);
    log_register('CHANGE UserContract (' . $login . ') `' . $contract . '`');
    zb_UserGetAllDataCacheClean();
}

/**
 * Returns all contracts as array contract=>login
 * 
 * @return array
 */
function zb_UserGetAllContracts() {
    $result = array();
    $query = "SELECT * from `contracts`";
    $allcontracts = simple_queryall($query);
    if (!empty($allcontracts)) {
        foreach ($allcontracts as $io => $eachcontract) {
            $result[$eachcontract['contract']] = $eachcontract['login'];
        }
    }
    return ($result);
}

/**
 * Returns all of available emails records as login=>email array
 * 
 * @return array
 */
function zb_UserGetAllEmails() {
    $result = array();
    $query = "SELECT * from `emails`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['login']] = $each['email'];
        }
    }
    return ($result);
}

/**
 * Returns stargazer user data array
 * 
 * @param string $login existing user login
 * @return array
 */
function zb_UserGetStargazerData($login) {
    $login = vf($login);
    $query = "SELECT * from `users` where `login`='" . $login . "'";
    $userdata = simple_query($query);
    return($userdata);
}

/**
 * Returns array of all available stargazer users data
 * 
 * @return array
 */
function zb_UserGetAllStargazerData() {
    $query = "SELECT * from `users`";
    $userdata = simple_queryall($query);
    return($userdata);
}

/**
 * Returns array of all available stargazer users data as login=>data array
 * 
 * @return array
 */
function zb_UserGetAllStargazerDataAssoc() {
    $query = "SELECT * from `users`";
    $userdata = simple_queryall($query);
    $result = array();
    if (!empty($userdata)) {
        foreach ($userdata as $io => $each) {
            $result[$each['login']] = $each;
        }
    }
    return ($result);
}

/**
 * Returns array of all available stargazer user logins
 * 
 * @return array
 */
function zb_UserGetAllStargazerLogins() {
    $result = array();
    $query = "SELECT `login` from `users`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[] = $each['login'];
        }
    }
    return ($result);
}

/**
 * Returns all users actual balance from database as array login=>cash
 * 
 * @return array
 */
function zb_UserGetAllBalance() {
    $result = array();
    $query = "SELECT `login`,`Cash` from `users`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['login']] = $each['Cash'];
        }
    }
    return($result);
}

/**
 * Returns user speed override database field for some login
 * 
 * @param string $login existing user login
 * @return int
 */
function zb_UserGetSpeedOverride($login) {
    $login = vf($login);
    $query = "SELECT `speed` from `userspeeds` where `login`='" . $login . "'";
    $speed = simple_query($query);
    if (!empty($speed['speed'])) {
        $speed = $speed['speed'];
    } else {
        $speed = 0;
    }
    return($speed);
}

/**
 * Creates speed override database record for some user
 * 
 * @param string $login existing user login
 * @param int $speed speed override to set
 */
function zb_UserCreateSpeedOverride($login, $speed) {
    $login = vf($login);
    $speed = vf($speed, 3);
    $query = "INSERT INTO `userspeeds` (`id` ,`login` ,`speed`) VALUES (NULL , '" . $login . "', '" . $speed . "');";
    nr_query($query);
    log_register('CREATE UserSpeedOverride (' . $login . ') `' . $speed . '`');
}

/**
 * Deletes speed override database record for some user
 * 
 * @param string $login existing user login
 */
function zb_UserDeleteSpeedOverride($login) {
    $login = vf($login);
    $query = "DELETE from `userspeeds` WHERE `login`='" . $login . "'";
    nr_query($query);
    log_register('DELETE UserSpeedOverride (' . $login . ')');
}

/**
 * Creates user notes database record for some login
 * 
 * @param string $login existing user login
 * @param string $notes user notes to set
 */
function zb_UserCreateNotes($login, $notes) {
    $login = vf($login);
    $notes = mysql_real_escape_string($notes);
    $query = "INSERT INTO `notes` (`id` , `login` ,`note`) VALUES (NULL , '" . $login . "', '" . $notes . "');";
    nr_query($query);
    log_register('CREATE UserNote (' . $login . ') `' . $notes . '`');
}

/**
 * Deletes user notes database record for some login
 * 
 * @param string  $login existing user login
 */
function zb_UserDeleteNotes($login) {
    $login = vf($login);
    $query = "DELETE FROM `notes` WHERE `login`='" . $login . "'";
    nr_query($query);
    log_register('DELETE UserNote (' . $login . ')');
}

/**
 * Returns user notes database field for some user
 * 
 * @param string $login existing user login
 * @return string
 */
function zb_UserGetNotes($login) {
    $login = vf($login);
    $query = "SELECT `note` from `notes` WHERE `login`='" . $login . "'";
    $result = simple_query($query);
    $result = $result['note'];
    return($result);
}

/**
 * Returns all of user tariffs as login=>tariff array
 * 
 * @return array
 */
function zb_TariffsGetAllUsers() {
    $query = "SELECT `login`,`Tariff` from `users`";
    $result = array();
    $alltariffuserspairs = simple_queryall($query);
    if (!empty($alltariffuserspairs)) {
        foreach ($alltariffuserspairs as $io => $eachuser) {
            $result[$eachuser['login']] = $eachuser['Tariff'];
        }
    }
    return ($result);
}

/**
 * Retunrs all of user LastActivityTimes as login=>LAT array
 * 
 * @return array
 */
function zb_LatGetAllUsers() {
    $query = "SELECT `login`,`LastActivityTime` from `users`";
    $result = array();
    $allpairs = simple_queryall($query);
    if (!empty($allpairs)) {
        foreach ($allpairs as $io => $eachuser) {
            $result[$eachuser['login']] = $eachuser['LastActivityTime'];
        }
    }
    return ($result);
}

/**
 * Returns array of all user cash data as array login=>cash
 * 
 * @return array
 */
function zb_CashGetAllUsers() {
    $query = "SELECT `login`,`Cash` from `users`";
    $result = array();
    $allcashuserspairs = simple_queryall($query);
    if (!empty($allcashuserspairs)) {
        foreach ($allcashuserspairs as $io => $eachuser) {
            $result[$eachuser['login']] = $eachuser['Cash'];
        }
    }
    return ($result);
}

/**
 * returns all of user cash and logins pairs as login=>credit
 * 
 * @return array
 */
function zb_CreditGetAllUsers() {
    $query = "SELECT `login`,`Credit` from `users`";
    $result = array();
    $alldata = simple_queryall($query);
    if (!empty($alldata)) {
        foreach ($alldata as $io => $eachuser) {
            $result[$eachuser['login']] = $eachuser['Credit'];
        }
    }
    return ($result);
}

/**
 * Returns price of tariff by its name
 * 
 * @param string $tariff
 * @return float
 */
function zb_TariffGetPrice($tariff) {
    $tariff = mysql_real_escape_string($tariff);
    $query = "SELECT `Fee` from `tariffs` WHERE `name`='" . $tariff . "'";
    $res = simple_query($query);
    return($res['Fee']);
}

/**
 * Returns full data of tariff by its name
 * 
 * @param string $tariff
 * @return array
 */
function zb_TariffGetData($tariff) {
    $tariff = mysql_real_escape_string($tariff);
    $query = "SELECT * from `tariffs` WHERE `name`='" . $tariff . "'";
    $result = simple_query($query);
    return ($result);
}

/**
 * Returns full data of all tariffs as name=>data
 * 
 * @return array
 */
function zb_TariffGetAllData() {
    $result = array();
    $query = "SELECT * from `tariffs`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['name']] = $each;
        }
    }
    return ($result);
}

/**
 * Returns list of all available tariffs with its prices as array tariff=>fee
 * 
 * @return array
 */
function zb_TariffGetPricesAll() {
    $query = "SELECT `name`,`Fee` from `tariffs`";
    $allprices = simple_queryall($query);
    $result = array();

    if (!empty($allprices)) {
        foreach ($allprices as $io => $eachtariff) {
            $result[$eachtariff['name']] = $eachtariff['Fee'];
        }
    }

    return ($result);
}

/**
 * Returns array of all available user notes as login=>note
 * 
 * @return array
 */
function zb_UserGetAllNotes() {
    $result = array();
    $query = "SELECT * from `notes`";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $result[$each['login']] = $each['note'];
        }
    }
    return ($result);
}

/**
 * Temporary resurrects user if he is inactive by some reasons
 * 
 * @param string $login
 * 
 * @return void
 */
function zb_UserResurrect($login) {
    global $billing;
    $userData = zb_UserGetStargazerData($login);
    $resurrectFlag = false;
    $resurrectType = array();
    if (!empty($userData)) {
        //user manually disabled?
        if ($userData['Down'] == 1) {
            $resurrectFlag = true;
            $resurrectType['DOWN'] = 'DOWN';
            $billing->setdown($login, 0);
        }

        //user frozen at this moment
        if ($userData['Passive'] == 1) {
            $resurrectFlag = true;
            $resurrectType['PASSIVE'] = 'PASSIVE';
            $billing->setpassive($login, 0);
        }

        //user AlwaysOnline flag disabled
        if ($userData['AlwaysOnline'] == 0) {
            $resurrectFlag = true;
            $resurrectType['AO'] = 'AO';
            $billing->setao($login, 1);
        }

        if ($userData['Cash'] < '-' . $userData['Credit']) {
            $resurrectFlag = true;
            $resurrectType['CASH'] = 'CASH';
            $currentCreditValue = $userData['Credit'];
            $tmpCreditValue = abs($userData['Cash']);
            $billing->setcredit($login, $tmpCreditValue);
        }

        //back user data to original state
        if ($resurrectFlag) {
            if (isset($resurrectType['DOWN'])) {
                $billing->setdown($login, 1);
            }
            if (isset($resurrectType['PASSIVE'])) {
                $billing->setpassive($login, 1);
            }
            if (isset($resurrectType['AO'])) {
                $billing->setao($login, 0);
            }
            if (isset($resurrectType['CASH'])) {
                $billing->setcredit($login, $currentCreditValue);
            }

            log_register('RESURRECT (' . $login . ') ' . implode(',', $resurrectType));
        }
    }
}

?>
