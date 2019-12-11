<?php

//just dummy module for testing purposes
error_reporting(E_ALL);



$onepunch = new OnePunch(); // reading all available scripts for minimizing DB operations
$scriptContent = $onepunch->getScriptContent('wdtest');
deb($scriptContent);
eval($scriptContent);

deb($watchdogCallbackResult);


