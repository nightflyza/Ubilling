<?php

if (cfr('DISCOUNTS')) {

    if ($ubillingConfig->getAlterParam('DISCOUNTS_ENABLED')) {
        //TODO
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}