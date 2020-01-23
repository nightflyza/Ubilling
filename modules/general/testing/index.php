<?php

//just dummy module for testing purposes
error_reporting(E_ALL);


//
//
//
//$dvrs = new nya_visor_dvrs();
//$dvrs->where('id', '=', '10');
//$dvrData = $dvrs->getAll();
//$dvrData = $dvrData[0];
//
//
//$trassir = new TrassirServer($dvrData['ip'], $dvrData['login'], $dvrData['password'], $dvrData['apikey']);
//$allServerObjects = $trassir->getServerObjects();
//debarr($allServerObjects);
//
////debarr($allServerObjects);
////debarr($trassir->getHealth());
////debarr($trassir->getChannels());
////debarr($trassir->getUserSettings('lOF9R0ul')); //test user
////debarr($trassir->setUserSettings('lOF9R0ul', 'base_rights', '1795'));
//
//
//function trassir_setBasicRights($guid) {
//    global $trassir;
//    $trassir->setUserSettings($guid, 'templates_managing', 0);
//    $trassir->setUserSettings($guid, 'enable_web', 1);
//    $trassir->setUserSettings($guid, 'enable_remote', 1);
//    $trassir->setUserSettings($guid, 'view_button', 1);
//    $trassir->setUserSettings($guid, 'settings_button', 0);
//
//    $trassir->setUserSettings($guid, 'shutdown_button', 0);
//    $trassir->setUserSettings($guid, 'enable_local', 0);
//    $trassir->setUserSettings($guid, 'base_rights', 1795);
//}
//
////trassir_setBasicRights('lOF9R0ul');
////////////
//
//
//debarr($trassir->createCamera('TRASSIR', 'TR-D8141IR2', '192.168.0.160', '80', 'admin', 'admin'));
//
//
//
//$allChannels = $trassir->getChannels();
//
//if (!empty($allChannels)) {
//    foreach ($allChannels as $io => $eachChan) {
//        $streamUrl = $trassir->getLiveVideoStream($eachChan['guid'], 'main', 'mjpeg');
//        deb(wf_img_sized($streamUrl, $eachChan['guid'], '40%'));
//        deb($eachChan['guid']);
//    }
//}
