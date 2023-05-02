<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

if (cfr('ROOT')) {
    $wolfRecorder = new WolfRecorder('https://127.0.0.1/dev/WolfRecorder', 'WR00000000000000000000000000000000');
    debarr($wolfRecorder->systemGetHealth());
}
