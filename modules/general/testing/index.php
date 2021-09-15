<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {


    $taskFails = new Stigma('TASKFAILS');
    $taskFails->stigmaController();
    show_window(__('Task checklist fails'), $taskFails->render(667, 128));


    $taskRanks = new Stigma('TASKRANKS');
    $taskRanks->stigmaController();

    show_window(__('User rating of task completion '), $taskRanks->render(667, 64));
    
    
//    $test=$taskFails->render(667, 128).' '.$taskRanks->render(667, 64);
//    
//    deb(wf_modalAuto('test modal', 'test modal', $test, 'ubButton'));
}