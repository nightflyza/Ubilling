<?php

$user_ip = zbs_UserDetectIp('debug');
$user_login = zbs_UserGetLoginByIp($user_ip);
$us_config = zbs_LoadConfig();
$us_helpdenied = zbs_GetHelpdeskDeniedAll();

if ($us_config['TICKETING_ENABLED']) {

    

    if (!ubRouting::checkGet('showticket')) {
        //mb post new ticket?
        if (ubRouting::checkPost('newticket')) {
            $newtickettext = ubRouting::post('newticket', 'raw');
            $newtickettext = strip_tags($newtickettext);
            if (!empty($newtickettext)) {
                if (!isset($us_helpdenied[$user_login])) {
                    if (zbs_spamCheck()) {
                        zbs_TicketCreate($user_login, 'NULL', $newtickettext);
                    }
                }
                ubRouting::nav("?module=ticketing");
            }
        }
        //show previous tickets
        if (!isset($us_helpdenied[$user_login])) {
            show_window(__('Create new help request'), zbs_TicketCreateForm());
        }

        show_window(__('Previous help requests'), zbs_TicketsShowMy());
        zbs_MessagesShowMy();
    } else {
        $ticketid = ubRouting::get('showticket', 'int');
        if (!empty($ticketid)) {
            //ok thats my ticket
            if (zbs_TicketIsMy($ticketid, $user_login)) {
                $ticketdata = zbs_TicketGetData($ticketid);
                //preventing access to reply tickets
                if (empty($ticketdata['replyid'])) {
                //mb post reply?
                if (ubRouting::checkPost('replyticket')) {
                    //preventing posting reply to reply tickets
                    if ($ticketdata['status']==0) {
                    $replytickettext = ubRouting::post('replyticket', 'raw');
                    if (!empty($replytickettext)) {
                        if (zbs_spamCheck()) {
                            zbs_TicketCreate($user_login, 'NULL', $replytickettext, $ticketid);
                        }
                        ubRouting::nav("?module=ticketing&showticket=" . $ticketid);
                    }
                    
                    } else {
                        show_window(__('Error'), __('This ticket is already closed'));
                    }
                }

                //let view it
                show_window(__('Help request') . ': ' . $ticketid, zbs_TicketShowWithReplies($ticketid));
                show_window(__('Reply'), zbs_TicketReplyForm($ticketid));
             } else {
                show_window(__('Error'), __('No such ticket'));
             }
            } else {
                show_window(__('Error'), __('No such ticket'));
            }
        }
    }
} else {
    show_window(__('Sorry'), __('Unfortunately helpdesk is now disabled'));
}

