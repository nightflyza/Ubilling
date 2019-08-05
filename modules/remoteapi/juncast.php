<?php

//some juniper mx coa handling
if ($_GET['action'] == 'juncast') {
    if ($alterconf['JUNGEN_ENABLED']) {
        if ((isset($_GET['login'])) AND ( isset($_GET['run']))) {
            $junRun = $_GET['run'];
            $junUserName = $_GET['login'];
            $juncast = new JunCast();
            switch ($junRun) {
                case 'block':
                    $juncast->blockUser($junUserName);
                    break;
                case 'unblock':
                    $juncast->unblockUser($junUserName);
                    break;
                case 'terminate':
                    $juncast->terminateUser($junUserName);
                    break;
            }
        } else {
            die('ERROR: RUN OR PARAM NOT SET');
        }

        die('OK: JUNCAST');
    } else {
        die('ERROR: JUNGEN DISABLED');
    }
}