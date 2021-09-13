<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {


//    if (ubRouting::checkGet(array(Stigma::ROUTE_SCOPE, Stigma::ROUTE_ITEMID, Stigma::ROUTE_STATE))) {
//        $stigmaCtrl = new Stigma(ubRouting::get(Stigma::ROUTE_SCOPE));
//        die($stigmaCtrl->render(ubRouting::get(Stigma::ROUTE_ITEMID)));
//    }

    $taskRanks = new Stigma('TASKRANKS');
    
    $taskRanks->stigmaController();
    show_window(__('User rating of task completion '),$taskRanks->render(666, 64));
    
    
    
}