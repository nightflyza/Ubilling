<?php
if (cfr('TICKETING')) {

    // close ticket
    if (ubRouting::checkGet('closeticket', false)) {
        zb_TicketSetDone(ubRouting::get('closeticket', 'int'));
        //update notification area
        $darkVoid = new DarkVoid();
        $darkVoid->flushCache();
        ubRouting::nav("?module=ticketing");
    }

    //open ticket
    if (ubRouting::checkGet('openticket', false)) {
        zb_TicketSetUnDone(ubRouting::get('openticket', 'int'));
        ubRouting::nav("?module=ticketing&showticket=" . ubRouting::get('openticket', 'int'));
    }

    //catch AI dialog callback
    if (ubRouting::checkGet('hivemind')) {
        $rawCallback = ubRouting::post('aichatcallback');
        if (json_validate($rawCallback)) {
            $callback = json_decode($rawCallback, true);
            $prompt = $callback['prompt'];
            $dialog = $callback['dialog'];
            $reply = zb_TicketGetAiReply($prompt, $dialog);
            die($reply);
        } else {
            die('Error: ' . __('Invalid callback'));
        }
    }

    if (!ubRouting::checkGet('settings')) {
        //view tickets list or calendar
        if (!ubRouting::checkGet('showticket', false)) {
            $configControl =  wf_Link('?module=ticketing&settings=true', wf_img('skins/settings.png', __('Typical answers presets'))) . ' ';
            if (!ubRouting::checkGet('calendarview')) {
                $viewControl =  wf_Link('?module=ticketing&calendarview=true', wf_img('skins/icon_calendar.gif', __('As calendar')), false, '');
                show_window($configControl . __('Available user tickets') . ' ' . $viewControl, web_TicketsShow());
            } else {
                $viewControl =  wf_Link('?module=ticketing', wf_img('skins/icon_table.png', __('Grid view')), false, '');
                show_window($configControl . __('Available user tickets') . ' ' . $viewControl, web_TicketsCalendar());
            }
        } else {
            //or view ticket data and replies
            $ticketid = ubRouting::get('showticket', 'int');
            show_window(__('Ticket') . ':' . $ticketid, web_TicketDialogue($ticketid));

            // maybe someone want to post reply
            if (ubRouting::checkPost('postreply')) {
                $originaladdress = zb_TicketGetData(ubRouting::post('postreply', 'int'));
                $originaladdress = $originaladdress['from'];
                $admin = whoami();
                zb_TicketCreate('NULL', $originaladdress, ubRouting::post('replytext', 'safe'), ubRouting::post('postreply', 'int'), $admin);
                ubRouting::nav("?module=ticketing&showticket=" . ubRouting::post('postreply', 'int'));
            }

            //maybe someone deleting reply
            if (ubRouting::checkGet('deletereply')) {
                zb_TicketDeleteReply(ubRouting::get('deletereply', 'int'));
                ubRouting::nav("?module=ticketing&showticket=" . $ticketid);
            }

            //reply editing sub 
            if (ubRouting::checkPost('editreply')) {
                zb_TicketUpdateReply(ubRouting::post('editreply', 'int'), ubRouting::post('editreplytext', 'safe'));
                ubRouting::nav("?module=ticketing&showticket=" . $ticketid);
            }
        }
    } else {
        //Typical Answers Presets (TAP) configuration

        //create new one
        if (ubRouting::checkPost(array('createnewtap', 'newtaptext'))) {
            zb_TicketsTAPCreate(ubRouting::post('newtaptext'));
            ubRouting::nav('?module=ticketing&settings=true');
        }

        //deleting tap
        if (ubRouting::checkGet('deletetap')) {
            zb_TicketsTAPDelete(ubRouting::get('deletetap'));
            ubRouting::nav('?module=ticketing&settings=true');
        }

        //editing tap
        if (ubRouting::checkPost(array('edittapkey', 'edittaptext'))) {
            zb_TicketsTAPEdit(ubRouting::post('edittapkey'), $ubRouting::post('edittaptext'));
            ubRouting::nav('?module=ticketing&settings=true');
        }

        //list available
        show_window(__('Available typical answers presets'), web_TicketsTapShowAvailable());

        //add form
        show_window(__('Create new preset'), web_TicketsTAPAddForm());

        show_window('', wf_BackLink('?module=ticketing'));
    }
} else {
    show_error(__('You cant control this module'));
}
