<?php
if (cfr('CORPS')) {
    $altcfg=$ubillingConfig->getAlter();
    if ($altcfg['CORPS_ENABLED']) {
        $greed=new Avarice();
        $beggar=$greed->runtime('CORPS');
        if (!empty($beggar)) {
            
            $corps=new Corps();
            $funds=new FundsFlow();
            
            //all that we need
            $corpsData=$corps->getCorps();
            $corpUsers=$corps->getUsers();
            $tariffPrices=  zb_TariffGetPricesAll();
            $AllUserTariffs=  zb_TariffsGetAllUsers();
            
            ///////////////////////////////
            if (!empty($corpUsers)) {
                foreach ($corpUsers as $eachlogin=>$eachcorpid) {
                   $fees=$funds->getFees($eachlogin);
                   debarr($funds->filterByDate($fees, '2014-12-'));
                    
                }
            }
            
            
            
         } else {
            show_window(__('Error'), __('No license key available'));
        }
        
    } else {
        show_window(__('Error'), __('This module is disabled'));
    }
    
} else {
    show_window(__('Error'), __('Access denied'));
}
?>