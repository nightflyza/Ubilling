<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {



    $cdr = new PBXCdr('91.230.25.125', 'asterisk', '5hHy589Jznf3zcnF', 'asterisk', 'cdr');
    debarr($cdr->getCDR());
}