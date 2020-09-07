<?php

/**
 * Returns all tickets list
 * 
 * @return array
 */
function zb_TicketsGetAll() {
    $query = "SELECT * from `ticketing` WHERE `to` IS NULL AND `replyid` IS NULL ORDER BY `date` DESC";
    $result = simple_queryall($query);
    return ($result);
}

/**
 * Returns available tickets count
 * 
 * @return int
 */
function zb_TicketsGetCount() {
    $query = "SELECT COUNT(`id`) from `ticketing` WHERE `to` IS NULL AND `replyid` IS NULL";
    $result = simple_query($query);
    $result = $result['COUNT(`id`)'];
    return ($result);
}

/**
 * Returns tickets limited from-to
 * 
 * @param int $from
 * @param int $to
 * @return array
 */
function zb_TicketsGetLimited($from, $to) {
    $query = "SELECT * from `ticketing` WHERE `to` IS NULL AND `replyid` IS NULL  ORDER BY `date` DESC LIMIT " . $from . "," . $to . ";";
    $result = simple_queryall($query);
    return ($result);
}

/**
 * Returns count of new opened tickets
 * 
 * @return int
 */
function zb_TicketsGetAllNewCount() {
    $query = "SELECT COUNT(`id`) from `ticketing` WHERE `to` IS NULL AND `replyid` IS NULL AND `status`='0' ORDER BY `date` DESC";
    $result = simple_query($query);
    $result = $result['COUNT(`id`)'];
    return ($result);
}

/**
 * Returns all tickets by some existing usrname
 * 
 * @param string $login
 * @return array
 */
function zb_TicketsGetAllByUser($login) {
    $login = vf($login);
    $query = "SELECT `id`,`date`,`status` from `ticketing` WHERE `to` IS NULL AND `replyid` IS NULL AND `from`='" . $login . "' ORDER BY `date` DESC";
    $result = simple_queryall($query);
    return ($result);
}

/**
 * Returns tickets data by its ID
 * 
 * @param int $ticketid
 * @return array
 */
function zb_TicketGetData($ticketid) {
    $ticketid = vf($ticketid, 3);
    $query = "SELECT * from `ticketing` WHERE `id`='" . $ticketid . "'";
    $result = simple_query($query);
    return ($result);
}

/**
 * Returns array of replies by some existing ticket
 * 
 * @param int $ticketid
 * @return array
 */
function zb_TicketGetReplies($ticketid) {
    $ticketid = vf($ticketid, 3);
    $query = "SELECT * from `ticketing` WHERE `replyid`='" . $ticketid . "' ORDER by `id` ASC";
    $result = simple_queryall($query);
    return ($result);
}

/**
 * Deletes ticket from database
 * 
 * @param int $ticketid
 * 
 * @return void
 */
function zb_TicketDelete($ticketid) {
    $ticketid = vf($ticketid, 3);
    $query = "DELETE FROM `ticketing` WHERE `id`='" . $ticketid . "'";
    nr_query($query);
    log_register("TICKET DELETE [" . $ticketid . "]");
}

/**
 * Deletes all of ticket replies followed by some ticket
 * 
 * @param int $ticketid
 * 
 * @return void
 */
function zb_TicketDeleteReplies($ticketid) {
    $ticketid = vf($ticketid, 3);
    $query = "DELETE FROM `ticketing` WHERE `replyid`='" . $ticketid . "'";
    nr_query($query);
    log_register("TICKET REPLIES DELETE [" . $ticketid . "]");
}

/**
 * Deletes ticket reply from database
 * 
 * @param int $replyid
 * 
 * @return void
 */
function zb_TicketDeleteReply($replyid) {
    $replyid = vf($replyid, 3);
    $query = "DELETE FROM `ticketing` WHERE `id`='" . $replyid . "'";
    nr_query($query);
    log_register("TICKET REPLY DELETE [" . $replyid . "]");
}

/**
 * Updates reply text by its ID
 * 
 * @param int $replyid
 * @param string $newtext
 * 
 * @return void
 */
function zb_TicketUpdateReply($replyid, $newtext) {
    $replyid = vf($replyid, 3);
    $newtext = strip_tags($newtext);
    simple_update_field('ticketing', 'text', $newtext, "WHERE `id`='" . $replyid . "'");
    log_register("TICKET REPLY EDIT [" . $replyid . "]");
}

/**
 * Creates new ticket into database
 * 
 * @param string $from
 * @param string $to
 * @param string $text
 * @param int $replyto
 * @param string $admin
 * 
 * @return void
 */
function zb_TicketCreate($from, $to, $text, $replyto = 'NULL', $admin = '') {
    $from = mysql_real_escape_string($from);
    $to = mysql_real_escape_string($to);
    $admin = mysql_real_escape_string($admin);
    $text = mysql_real_escape_string(strip_tags($text));
    $date = curdatetime();
    $replyto = vf($replyto);
    $query = "INSERT INTO `ticketing` (`id` , `date` , `replyid` , `status` ,`from` , `to` , `text`, `admin`) "
            . "VALUES (NULL , '" . $date . "', " . $replyto . ", '0', '" . $from . "', '" . $to . "', '" . $text . "', '" . $admin . "');";
    nr_query($query);

    $logreplyto = (empty($replyto)) ? '' : 'reply to [' . $replyto . ']';
    log_register("TICKET CREATE (" . $to . ") " . $logreplyto);
}

/**
 * Marks ticket as closed in database
 * 
 * @param int $ticketid
 * 
 * @return void
 */
function zb_TicketSetDone($ticketid) {
    $ticketid = vf($ticketid, 3);
    simple_update_field('ticketing', 'status', '1', "WHERE `id`='" . $ticketid . "'");
    log_register("TICKET CLOSE [" . $ticketid . "]");
}

/**
 * Marks ticket as unreviewed in database
 * 
 * @param int $ticketid
 * 
 * @return void
 */
function zb_TicketSetUnDone($ticketid) {
    $ticketid = vf($ticketid, 3);
    simple_update_field('ticketing', 'status', '0', "WHERE `id`='" . $ticketid . "'");
    log_register("TICKET OPEN [" . $ticketid . "]");
}

/**
 * Renders available tickets list with controls
 * 
 * @global object $ubillingConfig
 * @return string
 */
function web_TicketsShow() {
    global $ubillingConfig;
    $alterconf = $ubillingConfig->getAlter();
    //pagination section
    $totalcount = zb_TicketsGetCount();
    $perpage = $alterconf['TICKETS_PERPAGE'];

    if (!isset($_GET['page'])) {
        $current_page = 1;
    } else {
        $current_page = vf($_GET['page'], 3);
    }

    if ($totalcount > $perpage) {
        $paginator = wf_pagination($totalcount, $perpage, $current_page, "?module=ticketing", 'ubButton');
        $alltickets = zb_TicketsGetLimited($perpage * ($current_page - 1), $perpage);
    } else {
        $paginator = '';
        $alltickets = zb_TicketsGetAll();
    }


    $tablecells = wf_TableCell(__('ID'));
    $tablecells .= wf_TableCell(__('Date'));
    $tablecells .= wf_TableCell(__('From'));
    $tablecells .= wf_TableCell(__('Real Name'));
    $tablecells .= wf_TableCell(__('Full address'));
    $tablecells .= wf_TableCell(__('IP'));
    $tablecells .= wf_TableCell(__('Tariff'));
    $tablecells .= wf_TableCell(__('Balance'));
    $tablecells .= wf_TableCell(__('Credit'));
    $tablecells .= wf_TableCell(__('Processed'));
    $tablecells .= wf_TableCell(__('Actions'));
    $tablerows = wf_TableRow($tablecells, 'row1');


    if (!empty($alltickets)) {
        $allrealnames = zb_UserGetAllRealnames();
        $alladdress = zb_AddressGetFulladdresslist();
        $alltariffs = zb_TariffsGetAllUsers();
        $allcash = zb_CashGetAllUsers();
        $allcredits = zb_CreditGetAllUsers();
        $alluserips = zb_UserGetAllIPs();

        foreach ($alltickets as $io => $eachticket) {

            $tablecells = wf_TableCell($eachticket['id']);
            $tablecells .= wf_TableCell($eachticket['date']);
            $fromlink = wf_Link('?module=userprofile&username=' . $eachticket['from'], web_profile_icon() . ' ' . $eachticket['from']);
            $tablecells .= wf_TableCell($fromlink);
            $tablecells .= wf_TableCell(@$allrealnames[$eachticket['from']]);
            $tablecells .= wf_TableCell(@$alladdress[$eachticket['from']]);
            $tablecells .= wf_TableCell(@$alluserips[$eachticket['from']]);
            $tablecells .= wf_TableCell(@$alltariffs[$eachticket['from']]);
            $tablecells .= wf_TableCell(@$allcash[$eachticket['from']]);
            $tablecells .= wf_TableCell(@$allcredits[$eachticket['from']]);
            $tablecells .= wf_TableCell(web_bool_led($eachticket['status']), '', '', 'sorttable_customkey="' . $eachticket['status'] . '"');
            $actionlink = wf_Link('?module=ticketing&showticket=' . $eachticket['id'], wf_img_sized('skins/icon_search_small.gif', '', '12') . ' ' . __('Show'), false, 'ubButton');
            $tablecells .= wf_TableCell($actionlink);
            $tablerows .= wf_TableRow($tablecells, 'row3');
        }
    }
    $result = wf_TableBody($tablerows, '100%', '0', 'sortable');
    $result .= $paginator;


    return ($result);
}

/**
 * Returns typical answer preset creation form
 * 
 * @return string
 */
function web_TicketsTAPAddForm() {
    $inputs = wf_HiddenInput('createnewtap', 'true');
    $inputs .= wf_TextArea('newtaptext', '', '', true, '60x10');
    $inputs .= wf_Submit(__('Create'));
    $result = wf_Form('', "POST", $inputs, 'glamour');
    return ($result);
}

/**
 * Returns typical answer preset edit form
 * 
 * @param string $keyname
 * @param string $text
 * 
 * @return string
 */
function web_TicketsTAPEditForm($keyname, $text) {
    $inputs = wf_HiddenInput('edittapkey', $keyname);
    $inputs .= wf_TextArea('edittaptext', '', $text, true, '60x10');
    $inputs .= wf_Submit(__('Save'));
    $result = wf_Form('', 'POST', $inputs, 'glamour');
    return ($result);
}

/**
 * Creates new typical answer preset in database
 * 
 * @param string $taptext
 * 
 * @return void
 */
function zb_TicketsTAPCreate($taptext) {
    $keyName = 'HELPDESKTAP_' . zb_rand_string(8);
    $storeData = base64_encode($taptext);
    zb_StorageSet($keyName, $storeData);
    log_register('TICKET TAP CREATE `' . $keyName . '`');
}

/**
 * Deletes existing typical answer preset from database
 * 
 * @param string $keyname
 * 
 * @return void
 */
function zb_TicketsTAPDelete($keyname) {
    $keyname = mysql_real_escape_string($keyname);
    $query = "DELETE from `ubstorage` WHERE `key`='" . $keyname . "'";
    nr_query($query);
    log_register('TICKET TAP DELETE `' . $keyname . '`');
}

/**
 * Changes existing typical answer preset data in database
 * 
 * @param string $key
 * @param string $text
 * 
 * @return void
 */
function zb_TicketsTAPEdit($key, $text) {
    $storeData = base64_encode($text);
    zb_StorageSet($key, $storeData);
    log_register('TICKET TAP CHANGE `' . $key . '`');
}

/**
 * Returns all available typical answer presets array
 * 
 * @return array
 */
function zb_TicketsTAPgetAll() {
    $result = array();
    $query = "SELECT * from `ubstorage` WHERE `key` LIKE 'HELPDESKTAP_%' ORDER BY `id` ASC";
    $all = simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io => $each) {
            @$tmpData = base64_decode($each['value']);
            @$tmpData = str_replace("'", '`', $tmpData);
            $result[$each['key']] = $tmpData;
        }
    }
    return ($result);
}

/**
 * Renders available typical answer presets list with controls
 * 
 * @return string
 */
function web_TicketsTapShowAvailable() {
    $all = zb_TicketsTAPgetAll();

    $cells = wf_TableCell(__('Text'), '90%');
    $cells .= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($all)) {
        foreach ($all as $io => $each) {
            $cells = wf_TableCell($each);
            $actlinks = wf_JSAlert('?module=ticketing&settings=true&deletetap=' . $io, web_delete_icon(), __('Removing this may lead to irreparable results'));
            $actlinks .= wf_modalAuto(web_edit_icon(), __('Edit'), web_TicketsTAPEditForm($io, $each), '');
            $cells .= wf_TableCell($actlinks);
            $rows .= wf_TableRow($cells, 'row3');
        }
    }

    $result = wf_TableBody($rows, '100%', '0', 'sortable');
    return ($result);
}

/**
 * Returns typical answer preset insertion form
 * 
 * @return string
 */
function web_TicketsTAPLister() {
    $result = '';
    $maxLen = 50;
    $allReplies = zb_TicketsTAPgetAll();
    if (!empty($allReplies)) {
        $result .= wf_delimiter() . wf_tag('h3') . __('Typical answers presets') . wf_tag('h3', true);
        $result .= wf_tag('ul', false);
        foreach ($allReplies as $io => $each) {
            $randId = wf_InputId();
            $rawText = trim($each);
            $result .= wf_tag('script', false, '', 'language="javascript" type="text/javascript"');
            $encodedReply = json_encode($rawText);
            $encodedReply = rtrim($encodedReply, '"');
            $encodedReply = ltrim($encodedReply, '"');
            $result .= '
                    function jsAddReplyText_' . $randId . '() {
                         var replytext=\'' . $encodedReply . '\';
                         $("#ticketreplyarea").val(replytext);
                    }
                    ';
            $result .= wf_tag('script', true);

            $linkText = htmlspecialchars($rawText);
            if (mb_strlen($linkText, 'UTF-8') > $maxLen) {
                $linkText = mb_substr($rawText, 0, $maxLen, 'UTF-8') . '..';
            } else {
                $linkText = $rawText;
            }

            $result .= wf_tag('li') . wf_tag('a', false, '', 'href="#" onClick="jsAddReplyText_' . $randId . '();"') . $linkText . wf_tag('a', true) . wf_tag('li', true);
        }
        $result .= wf_tag('ul', true);
    }
    return ($result);
}

/**
 * Returns ticket reply form with typical answer presets if its available
 * 
 * @param int $ticketid
 * 
 * @return string
 */
function web_TicketReplyForm($ticketid) {
    $ticketid = vf($ticketid, 3);
    $ticketdata = zb_TicketGetData($ticketid);
    $ticketstate = $ticketdata['status'];
    if (!$ticketstate) {
        $replyinputs = wf_HiddenInput('postreply', $ticketid);
        $replyinputs .= wf_tag('textarea', false, '', 'name="replytext" cols="60" rows="10"  id="ticketreplyarea"') . wf_tag('textarea', true) . wf_tag('br');
        ;
        $replyinputs .= wf_Submit('Reply');
        $replyform = wf_Form('', 'POST', $replyinputs, 'glamour');
        $replyform .= web_TicketsTAPLister();
    } else {
        $replyform = __('Ticket is closed');
    }

    //ajax background render
    if (wf_CheckGet(array('ajevents'))) {
        $currentTicketEvents = wf_tag('h3') . __('Events for ticket') . '  ' . $ticketid . wf_tag('h3', true);
        $currentTicketEvents .= getTicketEvents($ticketid, true);
        die($currentTicketEvents);
    }

    //previous ticket events
    $replyform .= wf_AjaxLoader();
    $replyform .= wf_delimiter();
    $replyform .= wf_AjaxLink('?module=ticketing&showticket=' . $ticketid . '&ajevents=true', wf_img('skins/log_icon_small.png') . ' ' . __('Show ticket events'), 'ajticketevents', false, 'ubButton');
    $replyform .= wf_AjaxContainer('ajticketevents', '', '');


    return ($replyform);
}

/**
 * Returns reply edit form
 * 
 * @param int $replyid
 * 
 * @return string
 */
function web_TicketReplyEditForm($replyid) {
    $replyid = vf($replyid, 3);
    $ticketdata = zb_TicketGetData($replyid);
    $replytext = $ticketdata['text'];

    $inputs = wf_HiddenInput('editreply', $replyid);
    $inputs .= wf_TextArea('editreplytext', '', $replytext, true, '60x10');
    $inputs .= wf_Submit('Save');
    $form = wf_Form('', 'POST', $inputs, 'glamour');

    return ($form);
}

/**
 * Renders ticket, all of replies and all needed controls/forms for they
 * 
 * @param int $ticketid
 * 
 * @return string
 */
function web_TicketDialogue($ticketid) {
    $ticketid = vf($ticketid, 3);
    $ticketdata = zb_TicketGetData($ticketid);
    $ticketreplies = zb_TicketGetReplies($ticketid);
    @$employeeNames = unserialize(ts_GetAllEmployeeLoginsCached());

    $result = wf_tag('p', false, '', 'align="right"') . wf_BackLink('?module=ticketing', 'Back to tickets list', true) . wf_tag('p', true);
    if (!empty($ticketdata)) {
        $userLogin = $ticketdata['from'];
        $userAddress = zb_UserGetFullAddress($userLogin);
        //this data not used cache, to be 100% actual
        $userRealName = zb_UserGetRealName($userLogin);
        $userData = zb_UserGetStargazerData($userLogin);
        $userIp = $userData['IP'];
        $userCredit = $userData['Credit'];
        $userCash = $userData['Cash'];
        $userTariff = $userData['Tariff'];


        if ($ticketdata['status']) {
            $actionlink = wf_Link('?module=ticketing&openticket=' . $ticketdata['id'], wf_img('skins/icon_unlock.png') . ' ' . __('Open'), false, 'ubButton');
        } else {
            $actionlink = wf_Link('?module=ticketing&closeticket=' . $ticketdata['id'], wf_img('skins/icon_lock.png') . ' ' . __('Close'), false, 'ubButton');
        }


        $tablecells = wf_TableCell(__('ID'));
        $tablecells .= wf_TableCell(__('Date'));
        $tablecells .= wf_TableCell(__('Login'));
        $tablecells .= wf_TableCell(__('Real Name'));
        $tablecells .= wf_TableCell(__('Full address'));
        $tablecells .= wf_TableCell(__('IP'));
        $tablecells .= wf_TableCell(__('Tariff'));
        $tablecells .= wf_TableCell(__('Balance'));
        $tablecells .= wf_TableCell(__('Credit'));
        $tablecells .= wf_TableCell(__('Processed'));
        $tablerows = wf_TableRow($tablecells, 'row1');

        $tablecells = wf_TableCell($ticketdata['id']);
        $tablecells .= wf_TableCell($ticketdata['date']);
        $profilelink = wf_Link('?module=userprofile&username=' . $ticketdata['from'], web_profile_icon() . ' ' . $ticketdata['from']);
        $tablecells .= wf_TableCell($profilelink);
        $tablecells .= wf_TableCell($userRealName);
        $tablecells .= wf_TableCell($userAddress);
        $tablecells .= wf_TableCell($userIp);
        $tablecells .= wf_TableCell($userTariff);
        $tablecells .= wf_TableCell($userCash);
        $tablecells .= wf_TableCell($userCredit);
        $tablecells .= wf_TableCell(web_bool_led($ticketdata['status']));
        $tablerows .= wf_TableRow($tablecells, 'row3');
        $result .= wf_TableBody($tablerows, '100%', '0');

        //ticket body

        $tickettext = strip_tags($ticketdata['text']);
        $tickettext = nl2br($tickettext);
        $tablecells = wf_TableCell('', '20%');
        $tablecells .= wf_TableCell($ticketdata['date']);
        $tablerows = wf_TableRow($tablecells, 'row2');

        $ticketauthor = wf_tag('center') . wf_tag('b') . @$allrealnames[$ticketdata['from']] . wf_tag('b', true) . wf_tag('center', true);
        $ticketavatar = wf_tag('center') . wf_img('skins/userava.png') . wf_tag('center', true);
        $ticketpanel = $ticketauthor . wf_tag('br') . $ticketavatar;

        $tablecells = wf_TableCell($ticketpanel);
        $tablecells .= wf_TableCell($tickettext);
        $tablerows .= wf_TableRow($tablecells, 'row3');

        $result .= wf_TableBody($tablerows, '100%', '0', 'glamour');
        $result .= $actionlink;
    }


    if (!empty($ticketreplies)) {
        $result .= wf_tag('h2') . __('Replies') . wf_tag('h2', true);
        $result .= wf_CleanDiv();
        foreach ($ticketreplies as $io => $eachreply) {
            //reply
            if ($eachreply['admin']) {
                $adminRealName = (isset($employeeNames[$eachreply['admin']])) ? $employeeNames[$eachreply['admin']] : $eachreply['admin'];
                $replyauthor = wf_tag('center') . wf_tag('b') . $adminRealName . wf_tag('b', true) . wf_tag('center', true);
                $replyavatar = wf_tag('center') . gravatar_ShowAdminAvatar($eachreply['admin'], '64') . wf_tag('center', true);
            } else {
                $replyauthor = wf_tag('center') . wf_tag('b') . @$allrealnames[$eachreply['from']] . wf_tag('b', true) . wf_tag('center', true);
                $replyavatar = wf_tag('center') . wf_img('skins/userava.png') . wf_tag('center', true);
            }

            $replyactions = wf_tag('center');
            $replyactions .= wf_JSAlert('?module=ticketing&showticket=' . $ticketdata['id'] . '&deletereply=' . $eachreply['id'], web_delete_icon(), 'Removing this may lead to irreparable results') . ' ';
            $replyactions .= wf_JSAlert('?module=ticketing&showticket=' . $ticketdata['id'] . '&editreply=' . $eachreply['id'], web_edit_icon(), 'Are you serious');
            $replyactions .= wf_tag('center', true);

            // reply body 

            if (isset($_GET['editreply'])) {

                if ($_GET['editreply'] == $eachreply['id']) {
                    //is this reply editing?
                    $replytext = web_TicketReplyEditForm($eachreply['id']);
                } else {
                    //not this ticket edit
                    $replytext = strip_tags($eachreply['text']);
                }
            } else {
                //normal text by default
                $replytext = strip_tags($eachreply['text']);
                $replytext = nl2br($replytext);
            }

            $replypanel = $replyauthor . wf_tag('br') . $replyavatar . wf_tag('br') . $replyactions;




            $tablecells = wf_TableCell('', '20%');
            $tablecells .= wf_TableCell($eachreply['date']);
            $tablerows = wf_TableRow($tablecells, 'row2');

            $tablecells = wf_TableCell($replypanel);
            $tablecells .= wf_TableCell($replytext);
            $tablerows .= wf_TableRow($tablecells, 'row3');

            $result .= wf_TableBody($tablerows, '100%', '0', 'glamour');
            $result .= wf_CleanDiv();
        }
    }



    //reply form and previous tickets
    $allprevious = zb_TicketsGetAllByUser($ticketdata['from']);
    $previoustickets = '';
    if (!empty($allprevious)) {
        $previoustickets = wf_tag('h2') . __('All tickets by this user') . wf_tag('h2', true);
        foreach ($allprevious as $io => $eachprevious) {
            $tablecells = wf_TableCell($eachprevious['date']);
            $tablecells .= wf_TableCell(web_bool_led($eachprevious['status']));
            $prevaction = wf_Link('?module=ticketing&showticket=' . $eachprevious['id'], wf_img_sized('skins/icon_search_small.gif', '', '12') . ' ' . __('Show'), false, 'ubButton');
            $tablecells .= wf_TableCell($prevaction);
            $tablerows = wf_TableRow($tablecells, 'row3');
            $previoustickets .= wf_TableBody($tablerows, '100%', '0');
        }
    }

    $tablecells = wf_TableCell(web_TicketReplyForm($ticketid), '50%', '', 'valign="top"');
    $tablecells .= wf_TableCell($previoustickets, '50%', '', 'valign="top"');
    $tablerows = wf_TableRow($tablecells);

    $result .= wf_TableBody($tablerows, '100%', '0', 'glamour');
    $result .= wf_CleanDiv();
    return ($result);
}

/**
 * Renders tickets calendar view widget
 * 
 * @return string
 */
function web_TicketsCalendar() {
    $query = "SELECT * from `ticketing` WHERE `to` IS NULL AND `replyid` IS NULL ORDER BY `date` ASC";
    $all = simple_queryall($query);
    $allAddress = zb_AddressGetFulladdresslistCached();
    $result = '';
    $calendarData = '';
    if (!empty($all)) {

        foreach ($all as $io => $each) {
            $timestamp = strtotime($each['date']);
            $date = date("Y, n-1, j", $timestamp);
            $rawTime = date("H:i:s", $timestamp);
            if ($each['status'] == 0) {
                $coloring = "className : 'undone',";
            } else {
                $coloring = '';
            }
            $calendarData .= "
                      {
                        title: '" . $rawTime . ' ' . @$allAddress[$each['from']] . "',
                        url: '?module=ticketing&showticket=" . $each['id'] . "',
                        start: new Date(" . $date . "),
                        end: new Date(" . $date . "),
                       " . $coloring . "     
                   },
                    ";
        }
    }
    $result = wf_FullCalendar($calendarData);
    return ($result);
}

/**
 * Returns previous ticket events parsed from log.
 * 
 * @param int $TicketID
 * @param bool $ReturnHTML
 * 
 * @return array/string
 */
function getTicketEvents($TicketID, $ReturnHTML = false) {
    $QResult = array();
    $HTMLStr = '';

    $tQuery = "SELECT * FROM `weblogs` WHERE `event` LIKE 'TICKET%[" . $TicketID . "]'  ORDER BY `date` DESC";
    $QResult = simple_queryall($tQuery);

    if ($ReturnHTML and ! empty($QResult)) {
        $TableCells = wf_TableCell(__('ID'));
        $TableCells .= wf_TableCell(__('Date'));
        $TableCells .= wf_TableCell(__('Admin'));
        $TableCells .= wf_TableCell(__('IP'));
        $TableCells .= wf_TableCell(__('Event'));
        $TableRows = wf_TableRow($TableCells, 'row1');

        foreach ($QResult as $Rec) {
            $Event = htmlspecialchars($Rec['event']);

            $TableCells = wf_TableCell($Rec['id']);
            $TableCells .= wf_TableCell($Rec['date']);
            $TableCells .= wf_TableCell($Rec['admin']);
            $TableCells .= wf_TableCell($Rec['ip']);
            $TableCells .= wf_TableCell($Event);
            $TableRows .= wf_TableRow($TableCells, 'row3');
        }

        $HTMLStr .= wf_TableBody($TableRows, '100%', 0, 'sortable');
    }

    return ( ($ReturnHTML) ? $HTMLStr : $QResult );
}

?>
