<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();
$us_helpdenied = zbs_GetHelpdeskDeniedAll();

if ($us_config['TICKETING_ENABLED']) {

    /**
     * anti spam bots dirty magic inputs ;)
     * 
     * @return string
     */
    function zbs_spambotsTrap() {
        $result = la_tag('input', false, 'somemagic', 'type="text" name="surname"');
        $result.= la_tag('input', false, '', 'type="text" name="lastname" style="display:none;"');
        $result.= la_tag('input', false, 'somemagic', 'type="text" name="seenoevil"');
        $result.= la_tag('input', false, 'somemagic', 'type="text" name="mobile"');
        return ($result);
    }

    /**
     * checks spam fields availability
     * 
     * @return bool 
     */
    function zbs_spamCheck() {
        $spamTraps = array('surname', 'lastname', 'seenoevil', 'mobile');
        $result = true;
        if (!empty($spamTraps)) {
            foreach ($spamTraps as $eachTrap) {
                if (la_CheckPost(array($eachTrap))) {
                    return (false);
                }
            }
        }
        return ($result);
    }

    /**
     * Returns array of all tickets by some login
     * 
     * @param string $mylogin
     * @return array
     */
    function zbs_TicketsGetAllMy($mylogin) {
        $query = "SELECT * from `ticketing` WHERE `from`= '" . $mylogin . "' AND `replyid` IS NULL ORDER BY `date` DESC";
        $result = simple_queryall($query);
        return ($result);
    }

    /**
     * Returns array of all available direct messages
     * 
     * @param string $mylogin
     * @return array
     */
    function zbs_MessagesGetAllMy($mylogin) {
        $query = "SELECT * from `ticketing` WHERE `to`= '" . $mylogin . "' AND `from`='NULL' AND `status`='1' ORDER BY `date` DESC";
        $result = simple_queryall($query);
        return ($result);
    }

    /**
     * Returns array of some ticket properties
     * 
     * @param int $ticketid
     * @return array
     */
    function zbs_TicketGetData($ticketid) {
        $ticketid = vf($ticketid, 3);
        $query = "SELECT * from `ticketing` WHERE `id`='" . $ticketid . "'";
        $result = simple_query($query);
        return ($result);
    }

    /**
     * Returns array of all replies available for ticket
     * 
     * @param int $ticketid
     * @return array
     */
    function zbs_TicketGetReplies($ticketid) {
        $ticketid = vf($ticketid, 3);
        $query = "SELECT * from `ticketing` WHERE `replyid`='" . $ticketid . "' ORDER by `id` ASC";
        $result = simple_queryall($query);
        return ($result);
    }

    /**
     * Checks is some ticket accessible by login
     * 
     * @param int $ticketid
     * @param string $login
     * @return bool
     */
    function zbs_TicketIsMy($ticketid, $login) {
        $ticketid = vf($ticketid, 3);
        $login = loginDB_real_escape_string($login);
        $query = "SELECT `id` from `ticketing` WHERE `id`='" . $ticketid . "' AND `from`='" . $login . "'";
        $result = simple_query($query);
        if (!empty($result)) {
            return(true);
        } else {
            return(false);
        }
    }

    /**
     * Creates new ticket in database
     * 
     * @param string $from
     * @param string $to
     * @param string $text
     * @param string $replyto
     */
    function zbs_TicketCreate($from, $to, $text, $replyto = 'NULL') {
        $from = loginDB_real_escape_string($from);
        $to = loginDB_real_escape_string($to);
        $text = loginDB_real_escape_string(strip_tags($text));
        $date = curdatetime();
        $replyto = vf($replyto);
        $query = "INSERT INTO `ticketing` (`id` ,`date` ,`replyid` , `status` ,`from` ,`to` ,`text`)
    VALUES ( NULL ,'" . $date . "', " . $replyto . ", '0','" . $from . "', " . $to . ",'" . $text . "');";
        nr_query($query);
    }

    /**
     * Returns new ticket creation form
     * 
     * @return string
     */
    function zbs_TicketCreateForm() {
        $inputs = zbs_spambotsTrap();
        $inputs.= la_TextArea('newticket', '', '', true, '60x10');
        $inputs.= la_Submit(__('Post'));

        $result = la_Form('', 'POST', $inputs, '');
        return ($result);
    }

    /**
     * Returns ticket reply form if ticket state is open
     * 
     * @param int $ticketid
     * @return string
     */
    function zbs_TicketReplyForm($ticketid) {
        $ticketid = vf($ticketid);
        $ticketdata = zbs_TicketGetData($ticketid);
        if ($ticketdata['status'] == 0) {
            $inputs = zbs_spambotsTrap();
            $inputs.= la_TextArea('replyticket', '', '', true, '60x10');
            $inputs.= la_Submit(__('Post'));
            $result = la_Form('', 'POST', $inputs, '');
        } else {
            $result = __('Closed');
        }
        return ($result);
    }

    /**
     * Returns available tickets list
     * 
     * @global string $user_login
     * @return string
     */
    function zbs_TicketsShowMy() {
        global $user_login;
        $skinPath = zbs_GetCurrentSkinPath();
        $iconsPath = $skinPath . 'iconz/';
        $allmytickets = zbs_TicketsGetAllMy($user_login);

        $cells = la_TableCell(__('ID'));
        $cells.= la_TableCell(__('Date'));
        $cells.= la_TableCell(__('Status'));
        $cells.= la_TableCell(__('Actions'));
        $rows = la_TableRow($cells, 'row1');

        if (!empty($allmytickets)) {
            foreach ($allmytickets as $io => $eachticket) {
                if ($eachticket['status']) {
                    $ticketstatus = la_img($iconsPath . 'anread.gif') . ' ' . __('Closed');
                } else {
                    $ticketstatus = la_img($iconsPath . 'anunread.gif') . ' ' . __('Open');
                }
                $cells = la_TableCell($eachticket['id']);
                $cells.= la_TableCell($eachticket['date']);
                $cells.= la_TableCell($ticketstatus);
                $cells.= la_TableCell(la_Link('?module=ticketing&showticket=' . $eachticket['id'], __('View')));
                $rows.= la_TableRow($cells, 'row2');
            }
        }
        $result = la_TableBody($rows, '100%', 0);
        return ($result);
    }

    /**
     * Returns ticket with all replies
     * 
     * @param int $ticketid
     * @return string
     */
    function zbs_TicketShowWithReplies($ticketid) {
        global $us_config;
        $curSkinPath = zbs_GetCurrentSkinPath($us_config);
        $iconzPath = $curSkinPath . 'iconz/';
        $ticketid = vf($ticketid, 3);
        $ticketdata = zbs_TicketGetData($ticketid);
        $ticketreplies = zbs_TicketGetReplies($ticketid);


        if (!empty($ticketdata)) {
            $ticketAva = la_img($iconzPath . 'userava.png');

            $cells = la_TableCell(__('User'));
            $cells.= la_TableCell($ticketdata['date']);
            $rows = la_TableRow($cells, 'row1');
            $cells = la_TableCell($ticketAva, '', '', 'valign="top"');
            $cells.= la_TableCell(nl2br($ticketdata['text']));
            $rows.= la_TableRow($cells, 'row2');
        }
        if (!empty($ticketreplies)) {
            foreach ($ticketreplies as $io => $eachreply) {

                if ($eachreply['from'] == 'NULL') {
                    $ticketAva = la_img($iconzPath . 'admava.png');
                    $ticketFrom = __('Support');
                } else {
                    $ticketAva = la_img($iconzPath . 'userava.png');
                    $ticketFrom = __('User');
                }

                $cells = la_TableCell($ticketFrom);
                $cells.= la_TableCell($eachreply['date']);
                $rows.= la_TableRow($cells, 'row1');
                $cells = la_TableCell($ticketAva, '', '', 'valign="top"');
                $cells.= la_TableCell(nl2br($eachreply['text']));
                $rows.= la_TableRow($cells, 'row3');
            }
        }

        $result = la_TableBody($rows, '100%', 0);
        return ($result);
    }

    /**
     * Returns list of available direct messages
     * 
     * @global string $user_login
     * @return string
     */
    function zbs_MessagesShowMy() {
        global $user_login;
        $allmymessages = zbs_MessagesGetAllMy($user_login);

        $cells = la_TableCell(__('Date'));
        $cells.= la_TableCell(__('Message'));
        $rows = la_TableRow($cells, 'row1');

        if (!empty($allmymessages)) {
            foreach ($allmymessages as $io => $eachmessage) {

                $cells = la_TableCell($eachmessage['date']);
                $cells.= la_TableCell($eachmessage['text']);
                $rows.= la_TableRow($cells, 'row2');
            }
        }
        $result = la_TableBody($rows, '100%', 0);
        return ($result);
    }

    //////////////////////

    if (!isset($_GET['showticket'])) {
        //mb post new ticket?
        if (isset($_POST['newticket'])) {
            $newtickettext = strip_tags($_POST['newticket']);
            if (!empty($newtickettext)) {
                if (!isset($us_helpdenied[$user_login])) {
                    if (zbs_spamCheck()) {
                        zbs_TicketCreate($user_login, 'NULL', $newtickettext);
                    }
                }
                rcms_redirect("?module=ticketing");
            }
        }
        //show previous tickets
        if (!isset($us_helpdenied[$user_login])) {
            show_window(__('Create new help request'), zbs_TicketCreateForm());
        }

        show_window(__('Previous help requests'), zbs_TicketsShowMy());
        show_window(__('Messages from administration'), zbs_MessagesShowMy());
    } else {
        $ticketid = vf($_GET['showticket'], 3);
        if (!empty($ticketid)) {
            //ok thats my ticket
            if (zbs_TicketIsMy($ticketid, $user_login)) {
                //mb post reply?
                if (isset($_POST['replyticket'])) {
                    $replytickettext = strip_tags($_POST['replyticket']);
                    if (!empty($replytickettext)) {
                        if (zbs_spamCheck()) {
                            zbs_TicketCreate($user_login, 'NULL', $replytickettext, $ticketid);
                        }
                        rcms_redirect("?module=ticketing&showticket=" . $ticketid);
                    }
                }


                //let view it
                show_window(__('Help request') . ': ' . $ticketid, zbs_TicketShowWithReplies($ticketid));
                show_window(__('Reply'), zbs_TicketReplyForm($ticketid));
            } else {
                show_window(__('Error'), __('No such ticket'));
            }
        }
    }
} else {
    show_window(__('Sorry'), __('Unfortunately helpdesk is now disabled'));
}
?>
