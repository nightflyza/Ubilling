<?php

//periodic builds map cache update
if (ubRouting::get('action') == 'buildsmap') {
    if ($alterconf['SWYMAP_ENABLED']) {
        um_MapDrawBuilds();
    } else {
        die('ERROR: SWYMAP DISABLED');
    }
}