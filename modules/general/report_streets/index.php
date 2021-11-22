<?php

if ($system->checkForRight('STREETEPORT')) {

    $streetReport = new ReportStreets();
    show_window(__('Payments'), $streetReport->renderDateForm());
    show_window(__('Streets report'), $streetReport->render());
} else {
    show_error(__('Access denied'));
}
