<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {
    $crm=new BtrxCRM();
    $crm->runExport();
}
