<?php

//just dummy module for testing purposes
error_reporting(E_ALL);


$dvrs = new nya_visor_dvrs();
$dvrs->where('id', '=', '10');
$dvrData = $dvrs->getAll();
$dvrData = $dvrData[0];

$trassir = new TrassirServer($dvrData['ip'], $dvrData['login'], $dvrData['password'], $dvrData['apikey'], 8080, true);

//$userCreationResult = $trassir->createUser('view666', 'view777');
$trassir->assignUserChannels('view666', array('f083kSzE'));


//debarr($trassir->getUserSettings('YGQlm01L'));