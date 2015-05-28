<?php

if (cfr('CUDISCOUNTS')) {
    if (isset($_GET['username'])) {
        $login = $_GET['username'];
        $config = $ubillingConfig->getBilling();
        $alterconfig = $ubillingConfig->getAlter();
        if ($alterconfig['CUD_ENABLED']) {

            $discounts = new CumulativeDiscounts();
            $discounts->setLogin($login);
            show_window(__('Cumulative discount'), $discounts->renderReport());
            show_window('', web_UserControls($login));
        } else {
            show_error(__('This module disabled'));
        }
    } else {
        show_error(__('Strange exeption'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>