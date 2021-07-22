<?php

//reads uhw config file
function uhw_LoadConfig() {
    $path = "config/uhw.ini";
    $result = parse_ini_file($path);
    return ($result);
}

/**
 * Checks for substring in string
 * 
 * @param string $string
 * @param string $search
 * @return bool
 */
function ispos($string, $search) {
    if (strpos($string, $search) === false) {
        return(false);
    } else {
        return(true);
    }
}

//parse mac from a string
function uhw_MacParse($string) {
    preg_match('/[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}/i', $string, $macdetect);
    if (!empty($macdetect)) {
        return ($macdetect[0]);
    } else {
        return (false);
    }
}

// butify mac that will be displayed
function uhw_MacDisplay($mac) {
    $uconf = uhw_LoadConfig();
    if ($uconf['HIDE_DOTS']) {
        $mac = str_replace(':', '', $mac);
    }
    if ($uconf['SHOW_MAC_SIZE']) {
        $mac = substr($mac, '-' . $uconf['SHOW_MAC_SIZE']);
    }
    print('<font color="#FF0000">' . $mac . '</font>');
}

//isp site redirect
function uhw_redirect($url) {
    $redirect = '<script type="text/javascript">
        <!--
        window.location = "' . $url . '"
        //-->
        </script>
         ';
    die($redirect);
}

function uhw_IsAllPasswordsUnique() {
    $query_u = "SELECT COUNT(`login`) from `users`";
    $userdata = simple_query($query_u);
    $usercount = $userdata['COUNT(`login`)'];
    $query_p = "SELECT DISTINCT `Password` from `users`";
    $passwdata = simple_queryall($query_p);
    $passwordcount = sizeof($passwdata);
    if ($usercount == $passwordcount) {
        return (true);
    } else {
        return (false);
    }
}

//find mac for current user ip by mask
function uhw_FindMac($ip) {
    $uconf = uhw_LoadConfig();
    /*
      $sudo_path = $uconf['SUDO_PATH'];
      $cat_path = $uconf['CAT_PATH'];
      $logpath = $uconf['LOG_PATH'];
      $tail_path = $uconf['TAIL_PATH'];
      $grep_path = $uconf['GREP_PATH'];
      $unknown_mask = $uconf['UNKNOWN_MASK'];
      $unknown_lease = $uconf['UNKNOWN_LEASE'];
     * 
     */
    $macField = $uconf['MAC_FIELD'];
    $query = 'SELECT `framedipaddress`,`' . $macField . '` FROM `mlg_acct` WHERE `framedipaddress`="' . $ip . '" ORDER BY `radacctid` DESC LIMIT 1';
    $raw = simple_query($query);

    //$raw = shell_exec($sudo_path . ' ' . $cat_path . ' ' . $logpath . ' | ' . $grep_path . ' "' . $unknown_lease . $ip . ' " | ' . $tail_path . ' -n1');
    if (!empty($raw)) {
        $mac_detect = uhw_MacParse(preg_replace('/([a-f0-9]{2})(?![\s\]\/])([\.\:\-]?)/', '\1:', $raw[$macField]));
        if ($mac_detect) {
            return ($mac_detect);
        }
    }
    return(false);
}

function uhw_modal($link, $title, $content, $linkclass = '', $width = '', $height = '') {

    $wid = rand(0, 99999);

//setting link class
    if ($linkclass != '') {
        $link_class = 'class="' . $linkclass . '"';
    } else {
        $link_class = '';
    }

//setting auto width if not specified
    if ($width == '') {
        $width = '600';
    }

//setting auto width if not specified
    if ($height == '') {
        $height = '400';
    }

    $dialog = '
<script type="text/javascript">
$(function() {
        $( "#dialog-modal_' . $wid . '" ).dialog({
            autoOpen: false,
            width: ' . $width . ',
                        height: ' . $height . ',
            modal: true,
            show: "drop",
            hide: "fold"
        });

        $( "#opener_' . $wid . '" ).click(function() {
            $( "#dialog-modal_' . $wid . '" ).dialog( "open" );
                        return false;
        });
    });
</script>

<div id="dialog-modal_' . $wid . '" title="' . $title . '" style="display:none; width:1px; height:1px;">
    <p>
        ' . $content . '
        </p>
</div>

<a href="#" id="opener_' . $wid . '" ' . $link_class . '>' . $link . '</a>
';

    return($dialog);
}

function uhw_modal_open($title, $content, $width = '', $height = '') {

    $wid = rand(0, 99999);
//setting auto width if not specified
    if ($width == '') {
        $width = '600';
    }

//setting auto width if not specified
    if ($height == '') {
        $height = '400';
    }

    $dialog = '
<script type="text/javascript">
$(function() {
        $( "#dialog-modal_' . $wid . '" ).dialog({
            autoOpen: true,
            width: ' . $width . ',
                        height: ' . $height . ',
            modal: true,
            show: "drop",
            hide: "fold"
        });

        $( "#opener_' . $wid . '" ).click(function() {
            $( "#dialog-modal_' . $wid . '" ).dialog( "open" );
                        return false;
        });
    });
</script>

<div id="dialog-modal_' . $wid . '" title="' . $title . '" style="display:none; width:1px; height:1px;">
    <p>
        ' . $content . '
        </p>
</div>


';

    return($dialog);
}

function uhw_PasswordForm($uconf) {
    $form = '<form action="" method="POST" class="glamour">';
    if ($uconf['USE_LOGIN']) {
        $form .= '<label for="loginfield">' . $uconf['SUP_LOGIN'] . '</label> <input type="text" name="login" id="loginfield" size="16" style="margin-left: 12px;"><br /><br />';
    }
    $form .= '<label for="passfield">' . $uconf['SUP_PASS'] . '</label> <input type="' . $uconf['SELFACT_FIELDTYPE'] . '" name="password" id="passfield" size="16">
       <br>
       <br>
        <input type="submit" value="' . $uconf['SUP_ACTIVATE_QUERY'] . '">
        </form>
        
        <div style="clear:both;"></div>
        <br><br>
         ' . $uconf['SUP_PASSNOTICE'] . '
        ';

    $result = '<br><br><br>';
    $result .= uhw_modal($uconf['SUP_SELFACT'], $uconf['SUP_SELFACT'], $form, 'ubButton', '600', '400');
    print($result);
}

function uhw_IsMacUnique($mac) {
    $mac = vf($mac);
    $mac = strtolower($mac);
    $query = "SELECT `id` from `nethosts` WHERE `mac`='" . $mac . "'";
    $data = simple_query($query);

    if ($mac == '00:00:00:00:00:00') {
        return (false);
    }

    if (empty($data)) {
        return (true);
    } else {
        return (false);
    }
}

function uhw_FindUserByPassword($password, $login = '') {
    global $uconf;
    $result = '';
    $password = mysql_real_escape_string($password);
    if ($uconf['USE_LOGIN'] and ! empty($login)) {
        $login = mysql_real_escape_string($login);
        $query = "SELECT `login` from `users` WHERE `Password`='" . $password . "'";
        $query .= " AND `login` = '" . $login . "'";
        $result = simple_query($query);
    } else {
        $query = "SELECT `login` from `users` WHERE `Password`='" . $password . "'";
        $result = simple_query($query);
    }
    if (!empty($result)) {
        return ($result['login']);
    } else {
        return(false);
    }
}

function uhw_UserGetIp($login) {
    $query = "SELECT `IP` from `users` WHERE `login`='" . $login . "'";
    $result = simple_query($query);
    if (!empty($result)) {
        return ($result['IP']);
    } else {
        return (false);
    }
}

function uhw_NethostGetID($ip) {
    $query = "SELECT `id` from `nethosts` WHERE `ip`='" . $ip . "'";
    $result = simple_query($query);
    if (!empty($result)) {
        return ($result['id']);
    } else {
        return (false);
    }
}

function uhw_NethostGetMac($nethostid) {
    $query = "SELECT `mac` from `nethosts` WHERE `id`='" . $nethostid . "'";
    $result = simple_query($query);
    if (!empty($result)) {
        return ($result['mac']);
    } else {
        return (false);
    }
}

function uhw_ub_log_register($event) {
    $admin_login = 'external';
    $ip = '127.0.0.1';
    $current_time = date("Y-m-d H:i:s");
    $event = mysql_real_escape_string($event);
    $query = "INSERT INTO `weblogs` (`id`,`date`,`admin`,`ip`,`event`) VALUES(NULL,'" . $current_time . "','" . $admin_login . "','" . $ip . "','" . $event . "')";
    nr_query($query);
}

function uhw_LogSelfact($trypassword, $login, $tryip, $nethostid, $oldmac, $newmac) {
    $date = date("Y-m-d H:i:s");
    $query = "INSERT INTO `uhw_log` (
`id` ,
`date` ,
`password` ,
`login` ,
`ip` ,
`nhid` ,
`oldmac` ,
`newmac`
)
VALUES (
NULL , '" . $date . "', '" . $trypassword . "', '" . $login . "', '" . $tryip . "', '" . $nethostid . "', '" . $oldmac . "', '" . $newmac . "'
);";
    nr_query($query);
    //put ubilling log entry
    uhw_ub_log_register("UHW CHANGE (" . $login . ") MAC FROM " . $oldmac . " ON " . $newmac);
}

function uhw_GetBrute($mac) {
    $query = "SELECT COUNT(`id`) from `uhw_brute` WHERE `mac`='" . $mac . "'";
    $data = simple_query($query);
    return ($data['COUNT(`id`)']);
}

function uhw_LogBrute($password, $mac, $login = '') {
    $password = mysql_real_escape_string($password);
    $login = mysql_real_escape_string($login);
    $date = date("Y-m-d H:i:s");
    $query = "INSERT INTO `uhw_brute` (
            `id` ,
            `date` ,
            `password` ,
            `mac` ,
            `login`
            )
            VALUES (
            NULL , '" . $date . "', '" . $password . "', '" . $mac . "', '" . $login . "'
            );";
    nr_query($query);
}

function uhw_ChangeMac($nethost_id, $newmac, $oldmac) {
    $uconf = uhw_LoadConfig();
    $newmac = strtolower($newmac);
    $oldmac = strtolower($oldmac);
    switch ($uconf['MAC_FORMAT']) {
        case 'MAC':
            $mlg_mac = $newmac;
            $mlg_old_mac = $oldmac;
            break;
        case 'MACFDL':
            $mlg_mac = transformMacDotted($newmac);
            $mlg_old_mac = transformMacDotted($oldmac);
            break;
        case 'MACFML':
            $mlg_mac = str_replace('.', '-', $this->transformMacDotted($newmac));
            $mlg_old_mac = str_replace('.', '-', $this->transformMacDotted($oldmac));
            break;
        case 'MACTMU':
            $mlg_mac = transformMacMinused($newmac, true);
            $mlg_old_mac = transformMacMinused($oldmac, true);
            break;
        case 'MACTML':
            $mlg_mac = transformMacMinused($newmac, false);
            $mlg_old_mac = transformMacMinused($oldmac, false);
            break;
        default :
            $mlg_mac = $newmac;
            $mlg_old_mac = $oldmac;
            break;
    }
    simple_update_field('mlg_check', 'username', $mlg_mac, 'WHERE `username`="' . $mlg_old_mac . '"');
    simple_update_field('mlg_reply', 'username', $mlg_mac, 'WHERE `username`="' . $mlg_old_mac . '"');
    simple_update_field('mlg_groupreply', 'username', $mlg_mac, 'WHERE `username`="' . $mlg_old_mac . '"');
    simple_update_field('nethosts', 'mac', $newmac, "WHERE `id`='" . $nethost_id . "'");
}

function uhw_RemoteApiPush($url, $serial, $action, $param = '') {
    $getdata = http_build_query(
            array(
                'module' => 'remoteapi',
                'key' => $serial,
                'action' => $action,
                'param' => $param
            )
    );


    $opts = array('http' =>
        array(
            'method' => 'GET',
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $getdata
        )
    );

    $context = stream_context_create($opts);

    @$result = file_get_contents($url . '?' . $getdata, false, $context);
    return ($result);
}

function transformMacDotted($mac) {
    $result = implode(".", str_split(str_replace(":", "", $mac), 4));
    return ($result);
}

function transformMacMinused($mac, $caps = false) {
    $result = str_replace(':', '-', $mac);
    if ($caps) {
        $result = strtoupper($result);
    }
    return ($result);
}

?>
