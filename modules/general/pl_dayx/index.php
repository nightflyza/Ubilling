<?php
if (cfr('DAYX')) {
    if (ubRouting::checkGet('username')) {
        $username = ubRouting::get('username');
        $fundsflow = new FundsFlow();
        show_window('', $fundsflow->getOnlineLeftCount($username, false, $ubillingConfig->getAlterParam('FUNDSFLOW_CONSIDER_VSERVICES')));
    } else {
        show_error(__('Strange exeption') . ': EX_NO_USERNAME');
    }
} else {
    show_error(__('You cant control this module'));
}
