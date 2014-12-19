<?php

if (cfr('PLFUNDS')) {

    if (isset($_GET['username'])) {
        $login = $_GET['username'];

        $funds=new FundsFlow();

        $allfees = $funds->getFees($login);
        $allpayments = $funds->getPayments($login);
        $allcorrectings = $funds->getPaymentsCorr($login);

        $fundsflow = $allfees + $allpayments + $allcorrectings;
        $fundsflow=$funds->transformArray($fundsflow);

        
        show_window(__('Funds flow'), $funds->renderArray($fundsflow));

        show_window('', web_UserControls($login));
    }
} else {
    show_error(__('You cant control this module'));
}
?>