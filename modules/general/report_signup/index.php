<?php

if (cfr('REPORTSIGNUP')) {
    $reportSignups = new ReportSignups();
    $reportSignups->render();
    zb_BillingStats(true);
} else {
    show_error(__('You cant control this module'));
}
