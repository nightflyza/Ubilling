<?php

//tsupport api
if ($_GET['action'] == 'tsupport') {
    if ($alterconf['TSUPPORT_API']) {
        $tsupport = new TSupportApi();
        $tsupport->catchRequest();
    } else {
        die('ERROR:NO_TSUPPORT_API_ENABLED');
    }
}

              