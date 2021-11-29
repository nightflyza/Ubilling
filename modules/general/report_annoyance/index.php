<?php

if (cfr('ANNOYANCE')) {
    if ($ubillingConfig->getAlterParam('ANNOYANCE_ENABLED')) {
        $annoyance = new Annoyance();
        show_window(__('Users'), $annoyance->renderUsersFilterForm());
        if (ubRouting::checkPost($annoyance::PROUTE_FILTERUSERS)) {
            show_window(__('Users'), $annoyance->runUsersFilter());
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}