<?php

//parsing and storing FDB cache data into database
if (ubRouting::get('action') == 'fdbarchive') {
    $fdbarchive = new FDBArchive();
    $fdbarchive->storeArchive();
    die('OK:FDBARCHIVE');
}