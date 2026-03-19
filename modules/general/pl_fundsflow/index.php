<?php

if (cfr('PLFUNDS')) {

    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username');
        $considerVServices = $ubillingConfig->getAlterParam('FUNDSFLOW_CONSIDER_VSERVICES', false);
     
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
        $userAddress = zb_UserGetFullAddress($login);
        $userLink = wf_Link(UserProfile::URL_PROFILE . $login, web_profile_icon() . ' ' . $userAddress);

        show_window('', $funds->getOnlineLeftCount($login, false, $considerVServices, false));
        show_window(__('Users funds flow') . ' ' . $userLink, $funds->renderArray($fundsflow));
        show_window('', web_UserControls($login));
    } else {
        show_error(__('Strange exeption') . ': EX_NO_USERNAME');
    }
} else {
    show_error(__('You cant control this module'));
}
