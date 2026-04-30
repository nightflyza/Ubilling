<?php

if (ubRouting::get('action') == 'debtrsarch') {
    if ($alterconf['DEBTRSARCH_ENABLED']) {
        $debtrsArch = new DebtrsArch();
        $debtrsArch->storeCurrentDebtors();
        die('OK:DEBTRSARCH');
    } else {
        die('ERROR:DEBTRSARCH_DISABLED');
    }
}