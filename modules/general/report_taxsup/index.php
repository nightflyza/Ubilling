<?php

if ($ubillingConfig->getAlterParam('TAXSUP_ENABLED')) {
    if (cfr('TAXSUP')) {
        $taxa = new TaxSup();
        show_window(__('Additional fees'),$taxa->renderReport());
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
