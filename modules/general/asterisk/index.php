<?php

$altcfg = $ubillingConfig->getAlter();
$mysqlcfg = rcms_parse_ini_file(CONFIG_PATH . "mysql.ini");
if ($altcfg['ASTERISK_ENABLED']) {
    $asterisk = new Asterisk();
    $allrealnames = zb_UserGetAllRealnames();
    $alladdress = zb_AddressGetFulladdresslist();

    if (isset($_GET['username'])) {
        $user_login = vf($_GET['username']);
        // Profile:
        $profile = new UserProfile($user_login);
        show_window(__('User profile'), $profile->render());
        if ($altcfg['ADCOMMENTS_ENABLED'] and isset($_GET['addComments'])) {
            $adcomments = new ADcomments('ASTERISK');
            show_window(__('Additional comments'), $adcomments->renderComments($_GET['addComments']));
        }
    } elseif (isset($_GET['AsteriskWindow']) and ! wf_CheckPost(array('datefrom', 'dateto'))) {
        if ($altcfg['ADCOMMENTS_ENABLED'] and isset($_GET['addComments'])) {
            $adcomments = new ADcomments('ASTERISK');
            show_window(__('Additional comments'), $adcomments->renderComments($_GET['addComments']));
        }
    }

    /**
     * Get numbers aliases from database, or set default empty array
     * 
     * @return array
     */
    function zb_AsteriskGetNumAliases() {
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
     * Gets Asterisk config from DB, or sets default values
     * 
     * @return array
     */
    function zb_AsteriskGetConf() {
        $result = array();
        $emptyArray = array();
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
     * Converts per second time values to human-readable format
     * 
     * @param int $seconds - time interval in seconds
     * 
     * @return string
     */
    function zb_AsteriskFormatTime($seconds) {
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
     * Returns human readable alias from phone book by phone number
     * 
     * @param string $number - phone number
     * 
     * @return string
     */
    function zb_AsteriskGetNumAlias($number) {
        global $numAliases;

        if (!empty($numAliases)) {
            if (isset($numAliases[$number])) {
                return($number . ' - ' . $numAliases[$number]);
            } else {
                return ($number);
            }
        } else {
            return ($number);
        }
    }

    /**
     * Checks first digit in some number by some prefix
     * 
     * @param string $prefix - search prefix
     * @param string $callerid - phone number
     * 
     * @return bool
     */
    function zb_AsteriskCheckPrefix($prefix, $callerid) {
        if (substr($callerid, 0, 1) == $prefix) {
            return (true);
        } else {
            return (false);
        }
    }

    /**
     * Gets Login by caller number from DB
     * 
     * @return array
     */
    function zb_LoginByNumberQuery() {
        global $mysqlcfg, $user_login, $result_a;
        if (!isset($result_a) and empty($result_a)) {
            $loginDB = new mysqli($mysqlcfg['server'], $mysqlcfg['username'], $mysqlcfg['password'], $mysqlcfg['db']);
            if ($loginDB->connect_error) {
                die('Ошибка подключения (' . $loginDB->connect_errno . ') '
                        . $loginDB->connect_error);
            }
            if (isset($user_login)) {
                $query = "SELECT `phones`.`login`,`phone`,`mobile`,`content` FROM `phones` LEFT JOIN `cfitems` ON `phones`.`login`=`cfitems`.`login` WHERE `phones`.`login`='" . $user_login . "'";
            } else {
                $query = "SELECT `phones`.`login`,`phone`,`mobile`,`content` FROM `phones` LEFT JOIN `cfitems` ON `phones`.`login`=`cfitems`.`login`";
            }
            $result = $loginDB->query($query);
            $result_a = array();
            while ($row = $result->fetch_assoc()) {
                $result_a[$row['login']] = array(substr($row['phone'], -10), substr($row['mobile'], -10), substr($row['content'], -10));
            }
            mysqli_free_result($result);
            $loginDB->close();
        }
        return ($result_a);
    }

    /**
     * Gets Ubilling user login by number mobile
     * 
     * @param string $number - number
     * 
     * @return string
     */
    function zb_AsteriskGetLoginByNumber($number) {
        global $allrealnames, $alladdress;
        if (strlen($number) == 13 or strlen(substr($number, -10)) == 10) {
            $number_cut = substr($number, -10);
            $LoginByNumberQueryArray = zb_LoginByNumberQuery();
            foreach ($LoginByNumberQueryArray as $num => $loginArray) {
                if (in_array($number_cut, $loginArray)) {
                    $user_by_number = $num;
                    break;
                }
            }
            $result = array();
            if (!empty($user_by_number)) {
                $result['link'] = wf_Link('?module=userprofile&username=' . $user_by_number, $number, false);
                $result['login'] = $user_by_number;
                $result['name'] = @$allrealnames[$user_by_number];
                $result['adres'] = @$alladdress[$user_by_number];
                return ($result);
            } else {
                $result['link'] = $number;
                $result['login'] = '';
                $result['name'] = '';
                $result['adres'] = '';
                return ($result);
            }
        } else {
            $result['link'] = zb_AsteriskGetNumAlias($number);
            $result['login'] = '';
            $result['name'] = '';
            $result['adres'] = '';
            return ($result);
        }
    }

    /**
     * Function add by Pautina - teper tochno zazhivem :)
     * Looks like it gets some additional comments for something
     *
     * @return string
     */
    function zb_CheckCommentsForUser($scope, $idComments) {
        global $mysqlcfg;
        $loginDB = new mysqli($mysqlcfg['server'], $mysqlcfg['username'], $mysqlcfg['password'], $mysqlcfg['db']);
        $loginDB->set_charset("utf8");
        if ($loginDB->connect_error) {
            die('Ошибка подключения (' . $loginDB->connect_errno . ') '
                    . $loginDB->connect_error);
        }
        if (isset($scope) and isset($idComments)) {
            $query = "SELECT `text` from `adcomments` WHERE `scope`='" . $scope . "' AND `item`='" . $idComments . "' ORDER BY `date` ASC LIMIT 1;";

            $result = $loginDB->query($query);
            //$result_a = array();
            while ($row = $result->fetch_assoc()) {
                $comments = $row["text"];
            }
            mysqli_free_result($result);
            $loginDB->close();
            return ($comments);
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
    function zb_AsteriskParseCDR($data) {
        global $altcfg;
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
                $AsteriskGetLoginByNumberAraySrc = array(zb_AsteriskGetLoginByNumber($each['src']));
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

                $AsteriskGetLoginByNumberArayDst = array(zb_AsteriskGetLoginByNumber($each['dst']));
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
                        $cells.= wf_TableCell(wf_Link('?module=asterisk&addComments=' . $itemId . '&username=' . $login . '#profileending', $link_text, false));
                    } else {
                        $cells.= wf_TableCell(wf_Link('?module=asterisk&addComments=' . $itemId . '&AsteriskWindow=1', $link_text, false));
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

    if (cfr('ASTERISK')) {

//loading asterisk config
        $numAliases = zb_AsteriskGetNumAliases();

//showing configuration form
        if (wf_CheckGet(array('config'))) {
            //changing settings
            if (wf_CheckPost(array('newhost', 'newdb', 'newtable', 'newlogin', 'newpassword'))) {
                $asterisk->AsteriskUpdateConfig($_POST['newhost'],  $_POST['newdb'], $_POST['newtable'], $_POST['newlogin'], $_POST['newpassword'], $_POST['newcachetime']);
            }

            //aliases creation
            if (wf_CheckPost(array('newaliasnum', 'newaliasname'))) {
                $asterisk->AsteriskCreateAlias($_POST['newaliasnum'],  $_POST['newaliasnum']);
            }

            //alias deletion
            if (wf_CheckPost(array('deletealias'))) {
                $newStoreAliases = $numAliases;
                $deleteAliasNum = mysql_real_escape_string($_POST['deletealias']);
                if (isset($newStoreAliases[$deleteAliasNum])) {
                    unset($newStoreAliases[$deleteAliasNum]);
                    $newStoreAliases = serialize($newStoreAliases);
                    $newStoreAliases = base64_encode($newStoreAliases);
                    zb_StorageSet('ASTERISK_NUMALIAS', $newStoreAliases);
                    log_register("ASTERISK ALIAS DELETE `" . $deleteAliasNum . "`");
                    rcms_redirect("?module=asterisk&config=true");
                }
            }

            show_window(__('Settings'), $asterisk->AsteriskConfigForm());
            show_window(__('Phone book'), $asterisk->AsteriskAliasesForm());
        } else {
            //showing call history form
            show_window(__('Calls history'),$asterisk->panel());

            //and parse some calls history if this needed
            if (wf_CheckPost(array('datefrom', 'dateto'))) {
                $asterisk->AsteriskLoadCDR($_POST['datefrom'], $_POST['dateto']);
            } elseif (isset($user_login) and ! wf_CheckPost(array('datefrom', 'dateto'))) {
                $asterisk->AsteriskLoadCDR('2000', curdate());
            }
        }
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('Asterisk PBX integration now disabled'));
}
?>
