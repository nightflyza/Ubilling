<?php

if (cfr('GARAGE')) {
    $garage = new Garage();
    deb($garage->renderDriverCreateForm());
    deb($garage->renderCarCreateForm());

    //creating new driver
    if (ubRouting::checkPost($garage::PROUTE_NEWDRIVER)) {
        $garage->createDriver(ubRouting::post($garage::PROUTE_NEWDRIVER));
        ubRouting::nav($garage::URL_ME);
    }

    //creating new car
    if (ubRouting::checkPost(array($garage::PROUTE_NEWCAR, $garage::PROUTE_CARVENDOR, $garage::PROUTE_CARMODEL))) {
        $garage->createCar();
        ubRouting::nav($garage::URL_ME);
    }



    show_window(__('Existing drivers'), $garage->renderDriversList());
    show_window(__('Available cars'), $garage->renderCarsList());
} else {
    show_error(__('Access denied'));
}