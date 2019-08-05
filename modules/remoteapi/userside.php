<?php

/**
 * UserSide get API handling
 */
if ($_GET['action'] == 'userside') {
    if ($alterconf['USERSIDE_API']) {
        $usersideapi = new UserSideApi();
        $usersideapi->catchRequest();
    } else {
        die('ERROR:NO_USERSIDE_API_ENABLED');
    }
}

               