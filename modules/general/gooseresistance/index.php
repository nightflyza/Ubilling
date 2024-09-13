<?php

if (cfr('GOOSE')) {
    if ($ubillingConfig->getAlterParam('GOOSE_RESISTANCE')) {
        $gr = new GRes();
      
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
