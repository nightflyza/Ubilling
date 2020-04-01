<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();

/**
 * Logs announcements display for users into database
 * 
 * @param int $id
 * 
 * @return void
 */
function zbs_AnnouncementsLogPush($user_login, $annid) {
    $annid = vf($annid, 3);
    $user_login = mysql_real_escape_string($user_login);
    if ((!empty($user_login)) AND ( !empty($annid))) {
        $uniqueCheck_q = "SELECT * from `zbsannhist` WHERE `annid`='" . $annid . "' AND `login`='" . $user_login . "';";
        $uniqueCheck = simple_query($uniqueCheck_q);
        if (empty($uniqueCheck)) {
            $date = curdatetime();
            $query = "INSERT INTO `zbsannhist` (`id`,`date`,`annid`,`login`) VALUES "
                    . "(NULL,'" . $date . "','" . $annid . "','" . $user_login . "');";
            nr_query($query);
        }
    }
}

/**
 * Delete logs announcements display for users from database
 * 
 * @param int $id
 * 
 * @return void
 */
function zbs_AnnouncementsLogDel($user_login, $annid) {
    $annid = vf($annid, 3);
    $user_login = mysql_real_escape_string($user_login);
    if ((!empty($user_login)) AND ( !empty($annid))) {
        $query = "DELETE FROM `zbsannhist` WHERE `zbsannhist`.`login` = '" . $user_login . "' AND `annid` = '" . $annid . "'";
        nr_query($query);
    }
}

/**
 * Renders all active announcements
 * 
 * @global string $user_login
 * @global array $us_config
 * 
 * @return void
 */
function zbs_AnnouncementsShow() {
    global $user_login;
    global $us_config;
    $user_login = mysql_real_escape_string($user_login);
    $skinPath = zbs_GetCurrentSkinPath();
    $iconsPath = $skinPath . 'iconz/';
    $query = "SELECT * from `zbsannouncements` LEFT JOIN (SELECT `annid` FROM `zbsannhist` WHERE `login` = '" . $user_login . "') as zbh ON ( `zbsannouncements`.`id`=`zbh`.`annid`) WHERE `public`='1' ORDER by `id` DESC";

    $all = simple_queryall($query);
    $result = '';
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            if (empty($each['annid'])) {
                $readControl = la_Link('?module=announcements&anmarkasread=' . $each['id'], la_img($iconsPath . 'anunread.gif', __('Mark as read'))) . ' ';
                $readButton = la_Link('?module=announcements&anmarkasread=' . $each['id'], __('Mark as read'), false, 'anunreadbutton');
            } else {
                $readControl = la_Link('?module=announcements&anmarkasunread=' . $each['id'], la_img($iconsPath . 'anread.gif', __('Mark as unread'))) . ' ';
                $readButton = la_Link('?module=announcements&anmarkasunread=' . $each['id'], __('Mark as unread'), false, 'anreadbutton');
            }
            $result .= la_tag('h3', false, 'row1', '') . $readControl . $each['title'] . '&nbsp;' . la_tag('h3', true);

            if ($each['type'] == 'text') {
                $eachtext = strip_tags($each['text']);
                $result .= nl2br($eachtext);
            }

            if ($each['type'] == 'html') {
                $result .= $each['text'];
            }

            //additional read/unread buttons
            if (@$us_config['AN_BUTTONS']) {
                $result .= la_tag('br') . $readButton;
            }

            $result .= la_delimiter();
        }
    } else {
        show_window(__('Sorry'), __('There are not any announcements.'));
    }

    show_window('', $result);
}

if ($us_config['AN_ENABLED']) {
    // set logging 
    if (isset($_GET['anmarkasread'])) {
        $anReadId = vf($_GET['anmarkasread'], 3);
        zbs_AnnouncementsLogPush($user_login, $anReadId);
    }
    if (isset($_POST['anmarkasread'])) {
        $anReadId = vf($_POST['anmarkasread'], 3);
        zbs_AnnouncementsLogPush($user_login, $anReadId);
    }

    // delete logging 
    if (isset($_GET['anmarkasunread'])) {
        $anReadId = vf($_GET['anmarkasunread'], 3);
        zbs_AnnouncementsLogDel($user_login, $anReadId);
    }

    zbs_AnnouncementsShow();
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}
?>