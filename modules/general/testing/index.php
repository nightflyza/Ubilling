<?php

//just dummy module for testing purposes
error_reporting(E_ALL);


$fwtbt=new ForWhomTheBellTolls();

$fwtbt->getCalls();

deb($fwtbt->renderCallsNotification());



?>
