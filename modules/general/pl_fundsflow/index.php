<?php

if (cfr('PLFUNDS')) {

    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username');
        $considerVServices = $ubillingConfig->getAlterParam('FUNDSFLOW_CONSIDER_VSERVICES', false);
        $controls = '';
        $controls .= wf_BackLink(UserProfile::URL_PROFILE . $login);

        $funds = new FundsFlow();
        $allfees = $funds->getFees($login);
        $allpayments = $funds->getPayments($login);
        $allcorrectings = $funds->getPaymentsCorr($login);

        if ($funds->avoidDTKeysDuplicates) {
            $fundsflow = $funds->concatAvoidDuplicateKeys($allfees, $allpayments, $allcorrectings);
        } else {
            $fundsflow = $allfees + $allpayments + $allcorrectings;
        }

        $fundsflow = $funds->transformArray($fundsflow);



//        show_window('', $controls);

        show_window('', $funds->getOnlineLeftCount($login, false, $considerVServices, true));
        show_window(__('Funds flow'), $funds->renderArray($fundsflow));
        show_window('', web_UserControls($login));
    } else {
        show_error(__('Strange exeption') . ': EX_NO_USERNAME');
    }
} else {
    show_error(__('You cant control this module'));
}
