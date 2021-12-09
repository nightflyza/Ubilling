<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {

    $olltvService = new OllTVService();
    
    debarr($olltvService->getSubscriberData('_he12ap1_rkh2'));

}