<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

$hls = new HlsTV();
//debarr($hls->getUserInfo(14));
//debarr($hls->getTariffsBase()); // 1036 avail
//debarr($hls->getTariffsBundle()); // 1046 avail
//debarr($hls->getTariffsPromo()); 
//debarr($hls->setUserTariff(1,array('base' =>1036, 'bundle' => 1046)));
//debarr($hls->setUserBlock(1));
//debarr($hls->setUserActivate(1));
//debarr($hls->getDeviceCode(1));
//debarr($hls->addDevice(1, '1BF499EDA3E0C588')); //here is some shit
//debarr($hls->getDeviceList());

$omega = new OmegaTV();
deb($omega->renderTariffs('base', true, true));
deb($omega->renderUserInfo(1));

?>
