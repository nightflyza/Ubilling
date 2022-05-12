<?php

if ($ubillingConfig->getAlterParam('MULTIGEN_ENABLED')) {
    if (cfr('MULTIGEN')) {

        $customNas = new MultigenECN();
        show_window('', wf_BackLink('?module=nas'));
        show_window(__('Extra chromosome NASes'), $customNas->renderList());
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}