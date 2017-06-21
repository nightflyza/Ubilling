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

    if (cfr('ASTERISK')) {
//showing configuration form
        if (wf_CheckGet(array('config'))) {
            //changing settings
            if (wf_CheckPost(array('newhost', 'newdb', 'newtable', 'newlogin', 'newpassword'))) {
                $asterisk->AsteriskUpdateConfig($_POST['newhost'],  $_POST['newdb'], $_POST['newtable'], $_POST['newlogin'], $_POST['newpassword'], $_POST['newcachetime']);
            }

            //aliases creation
            if (wf_CheckPost(array('newaliasnum', 'newaliasname'))) {
                $asterisk->AsteriskCreateAlias($_POST['newaliasnum'],  $_POST['newaliasname']);
            }

            //alias deletion
            if (wf_CheckPost(array('deletealias'))) {
                $asterisk->AsteriskDeleteAlias($_POST['deletealias']);
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
