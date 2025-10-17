<?php

/**
 * Logs announcements display for users into database
 * 
 * @param string $user_login
 * @param int $annid
 *
 * @return void
 */
function zbs_AnnouncementsLogPush($user_login, $annid) {
    $annid = ubRouting::filters($annid, 'int');
    $user_login = ubRouting::filters($user_login, 'mres');
    if ((!empty($user_login)) and (!empty($annid))) {
        $historyDb = new NyanORM('zbsannhist');
        $historyDb->where('annid', '=', $annid);
        $historyDb->where('login', '=', $user_login);
        $uniqueCheck = $historyDb->getAll();
        
        if (empty($uniqueCheck)) {
            $date = curdatetime();
            $historyDb->data('date', $date);
            $historyDb->data('annid', $annid);
            $historyDb->data('login', $user_login);
            $historyDb->create();
        }
    }
}

/**
 * Delete logs announcements display for users from database
 * 
 * @param string $user_login
 * @param int $annid
 *
 * @return void
 */
function zbs_AnnouncementsLogDel($user_login, $annid) {
    $annid = ubRouting::filters($annid, 'int');
    $user_login = ubRouting::filters($user_login, 'mres');
    if ((!empty($user_login)) and (!empty($annid))) {
        $historyDb = new NyanORM('zbsannhist');
        $historyDb->where('login', '=', $user_login);
        $historyDb->where('annid', '=', $annid);
        $historyDb->delete();
    }
}

/**
 * Gets all active announcements for user
 * 
 * @param string $user_login
 *
 * @return array
 */
function zbs_AnnouncementsGetAll($user_login) {
    $user_login = ubRouting::filters($user_login, 'mres');
    
    $announcementsDb = new NyanORM('zbsannouncements');
    
    $joinQuery = "(SELECT `annid` FROM `zbsannhist` WHERE `login` = '" . $user_login . "') as zbh";
    $joinCondition = "`zbsannouncements`.`id`=`zbh`.`annid`";
    
    $announcementsDb->joinOn('LEFT', $joinQuery, $joinCondition, true);
    $announcementsDb->where('public', '=', '1');
    $announcementsDb->orderBy('id', 'DESC');
    $result = $announcementsDb->getAll();
    return ($result);
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
    $skinPath = zbs_GetCurrentSkinPath();
    $iconsPath = $skinPath . 'iconz/';
    
    $all = zbs_AnnouncementsGetAll($user_login);
    
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