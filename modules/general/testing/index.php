<?php

//just dummy module for testing purposes
error_reporting(E_ALL);


//$dvrs = new nya_visor_dvrs();
//$dvrs->where('id', '=', '10');
//$dvrData = $dvrs->getAll();
//$dvrData = $dvrData[0];
////
////
//$trassir = new TrassirServer($dvrData['ip'], $dvrData['login'], $dvrData['password'], $dvrData['apikey']);
////$trassir->setDebug(true);
//$trassir->getHealth();
//// efXwC0I5/boards,4294967296,/efXwC0I5/channels/VxPmGjBc,6605659701248,/efXwC0I5/channels/YpqJfqNq,6609954668544,/efXwC0I5/ip_cameras,4294967296
//
//
//
//
//$allUsers=$trassir->getUserNames();
//debarr($allUsers);
//
//
//if (!empty($allUsers)) {
//    foreach ($allUsers as $login=>$eachGuid) {
//    $userData=$trassir->getUserSettings($eachGuid);
//    //debarr($userData);
//    debarr($userData['fulldata']['acl']);
//    }
//    
//}
//
////$allServerObjects = $trassir->getServerObjects();
////debarr($allServerObjects);
//
////debarr($allServerObjects);
////debarr($trassir->getHealth());
////debarr($trassir->getChannels());
////debarr($trassir->getUserSettings('lOF9R0ul')); //test user
//
//
//
//function trassir_setBasicRights($guid) {
//    global $trassir;
//    $trassir->setUserSettings($guid, 'base_rights', 0);
//    $trassir->setUserSettings($guid, 'templates_managing', 0);
//    $trassir->setUserSettings($guid, 'enable_web', 1);
//    $trassir->setUserSettings($guid, 'enable_remote', 1);
//    $trassir->setUserSettings($guid, 'view_button', 1);
//    $trassir->setUserSettings($guid, 'settings_button', 0);
//    $trassir->setUserSettings($guid, 'shutdown_button', 0);
//    $trassir->setUserSettings($guid, 'enable_local', 0);
//    
//}
//
////trassir_setBasicRights('lOF9R0ul');
////////////
//
//
////debarr($trassir->createCamera('TRASSIR', 'TR-D8141IR2', '192.168.0.153', '80', 'admin', 'admin'));
//
//
//
////$allChannels = $trassir->getChannels();
///**
// * Array
//(
//    [0] => Array
//        (
//            [name] => TR-D8141IR2 5
//            [guid] => iF99SsWk
//            [parent] => efXwC0I5C
//        )
//
//    [1] => Array
//        (
//            [name] => TR-D8141IR2 3
//            [guid] => qp5yOGoQ
//            [parent] => efXwC0I5C
//        )
//
//)
// */
//
///**
// * Array
//(
//    [UvG8g30z] => UvG8g30z
//    [e9xRpBRQ] => e9xRpBRQ
//)
// */
//
//
//
//
////if (!empty($allChannels)) {
////    foreach ($allChannels as $io => $eachChan) {
////        $streamUrl = $trassir->getLiveVideoStream($eachChan['guid'], 'main', 'mjpeg');
////        
////        deb(wf_img_sized($streamUrl, $eachChan['guid'], '40%'));
////        //deb($eachChan['guid']);
////    }
////}
