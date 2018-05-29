<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

$districts=new Districts(false);
//$districts->fillDistrictsCache();
//debarr($districts->getUserDistrictsFast('ko_he5ap11_5y2n'));
deb($districts->getUserDistrictsListFast('ko_he5ap11_5y2n'));


?>
