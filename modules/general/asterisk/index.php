<?php

$altcfg = $ubillingConfig->getAlter();
$mysqlcfg = rcms_parse_ini_file(CONFIG_PATH . "mysql.ini");
if ($altcfg['ASTERISK_ENABLED']) {
    $asterisk = new Asterisk();
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
                $asterisk->AsteriskGetCDR($_POST['datefrom'], $_POST['dateto'], $user_login);
            } elseif (isset($user_login) and ! wf_CheckPost(array('datefrom', 'dateto'))) {
                $asterisk->AsteriskGetCDR('2000', curdate(), $user_login);
            }
        }
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('Asterisk PBX integration now disabled'));
}
?>
