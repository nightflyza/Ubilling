<?php

if (cfr('PLSENDMESSAGE')) {

    /**
     * Renders message sending form
     * 
     * @return void
     */
    function web_MessageSendForm() {
        $inputs = wf_TextArea('messagetext', '', '', true, '60x10');
        $inputs .= wf_Submit(__('Send'));
        $form = wf_Form('', 'POST', $inputs, 'glamour');

        show_window(__('Send message'), $form);
    }

    /**
     * Renders previous messages for some user
     * 
     * @param string $login
     * 
     * @return
     */
    function web_MessagesShowPrevious($login) {
        $result = '';
        $login = ubRouting::filters($login, 'mres');

        $ticketingDb = new NyanORM('ticketing');
        $ticketingDb->where('to', '=', $login);
        $ticketingDb->whereRaw("`from`='NULL'");
        $ticketingDb->where('status', '=', '1');
        $ticketingDb->orderBy('date', 'DESC');
        $allmessages = $ticketingDb->getAll();

        if (!empty($allmessages)) {
            $cells = wf_TableCell(__('Date'), '15%');
            $cells .= wf_TableCell(__('Text'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($allmessages as $io => $each) {
                $cells = wf_TableCell($each['date']);
                $cells .= wf_TableCell($each['text']);
                $rows .= wf_TableRow($cells, 'row3');
            }

            $result .= wf_TableBody($rows, '100%', 0, 'sortable');
        } else {
            $messages = new UbillingMessageHelper();
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'info');
        }

        show_window(__('Previous messages'), $result);
    }

    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username');

        //creating new message
        if (ubRouting::checkPost('messagetext')) {
            zb_TicketCreate('NULL', $login, ubRouting::post('messagetext'), 'NULL', whoami());
            $newid = simple_get_lastid('ticketing');
            zb_TicketSetDone($newid);
            ubRouting::nav('?module=pl_sendmessage&username=' . $login);
        }


        show_window('', wf_BackLink('?module=pl_ticketing&username=' . $login));
        //render previous messages
        web_MessagesShowPrevious($login);

        //and new message form
        web_MessageSendForm();
        show_window('', web_UserControls($login));
    } else {
        show_error(__('User not exist'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
