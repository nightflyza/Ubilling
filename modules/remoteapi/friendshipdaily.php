<?php

/*
 * friendship processing
 */
if ($_GET['action'] == 'friendshipdaily') {
    if ($alterconf['FRIENDSHIP_ENABLED']) {
        $friends = new FriendshipIsMagic();
        $friends->friendsDailyProcessing();
        die('OK:FRIENDSHIP');
    } else {
        die('ERROR:FRIENDSHIP DISABLED');
    }
}
