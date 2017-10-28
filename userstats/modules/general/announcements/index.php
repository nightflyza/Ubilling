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
        $date = curdatetime();
        $query = "INSERT INTO `zbsannhist` (`id`,`date`,`annid`,`login`) VALUES "
                . "(NULL,'" . $date . "','" . $annid . "','" . $user_login . "');";
        nr_query($query);
    }
}

/**
 * Loads list of all announcements displayed for user earlier
 * 
 * @param string $user_login
 * 
 * @return array
 */
function zbs_AnnouncementsReadHistory($user_login) {
    $user_login = mysql_real_escape_string($user_login);
    $result = array();
    if (!empty($user_login)) {
        $query = "SELECT * from `zbsannhist` WHERE `login`='" . $user_login . "';";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $result[$each['annid']] = $each['date'];
            }
        }
    }
    return ($result);
}

/**
 * Renders all active announcements
 * 
 * @global string $user_login
 * 
 * @return void
 */
function zbs_AnnouncementsShow() {
    global $user_login;
    $skinPath = zbs_GetCurrentSkinPath();
    $iconsPath = $skinPath . 'iconz/';
    $query = "SELECT * from `zbsannouncements` WHERE `public`='1' ORDER by `id` DESC";
    $all = simple_queryall($query);
    $result = '';
    if (!empty($all)) {
        $annHistory = zbs_AnnouncementsReadHistory($user_login);
        foreach ($all as $io => $each) {
            if (!isset($_COOKIE['zbsanread_' . $each['id']])) {
                $readControl = la_Link('?module=announcements&anmarkasread=' . $each['id'], la_img($iconsPath . 'anunread.gif', __('Mark as read'))) . ' ';
            } else {
                $readControl = la_Link('?module=announcements&anmarkasunread=' . $each['id'], la_img($iconsPath . 'anread.gif', __('Mark as unread'))) . ' ';
            }
            $result.=la_tag('h3', false, 'row1', '') . $readControl . $each['title'] . '&nbsp;' . la_tag('h3', true);
            $result.=la_delimiter();
            if ($each['type'] == 'text') {
                $eachtext = strip_tags($each['text']);
                $result.= nl2br($eachtext);
            }

            if ($each['type'] == 'html') {
                $result.=$each['text'];
            }
            //display logging 
            if (!isset($annHistory[$each['id']])) {
                zbs_AnnouncementsLogPush($user_login, $each['id']);
            }

            $result.=la_delimiter();
        }
    } else {
        show_window(__('Sorry'), __('There are not any announcements.'));
    }

    show_window('', $result);
}

if ($us_config['AN_ENABLED']) {
    zbs_AnnouncementsShow();
} else {
    show_window(__('Sorry'), __('This module is disabled'));
}
?>