<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

$hls=new HlsTV();
$hash=$hls->generateApiHash(array('lang'=>'ru'));
debarr($hls->pushApiRequest(array(), false));

?>
