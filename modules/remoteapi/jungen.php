<?php

// juniper mx attributes regeneration
if ($_GET['action'] == 'jungen') {
    if ($alterconf['JUNGEN_ENABLED']) {
        $jungen = new JunGen();
        $jungen->totalRegeneration();
        die('OK: JUNGEN');
    } else {
        die('ERROR: JUNGEN DISABLED');
    }
}

            