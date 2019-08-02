<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

$fdbarchive=new FDBArchive();

if (ubRouting::get('ajax')) {
    $fdbarchive->ajArchiveData();
}
//deb($fdbarchive->renderArchive());




