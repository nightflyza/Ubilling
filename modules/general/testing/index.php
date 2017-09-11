<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

$upd=new UbillingUpdateStuff();

$upd->downloadRemoteFile('http://ubilling.net.ua/packages/phpsysinfo.tar.gz', '/tmp/','phpsysinfo.tar.gz');
//$upd->extractTgz('/tmp/phpsysinfo.tar.gz', 'ttt/');

?>