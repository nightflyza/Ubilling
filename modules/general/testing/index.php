<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

$callmeback=new CallMeBack();
debarr($callmeback->getUndoneCalls());
debarr($callmeback->getUndoneCount());
