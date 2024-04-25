<?php

/*
 * friendship processing
 */
if (ubRouting::get('action') == 'friendshipdaily') {
    if ($alterconf['FRIENDSHIP_ENABLED']) {
        $friends = new FriendshipIsMagic();
        $friends->friendsDailyProcessing();
        die('OK:FRIENDSHIP');
    } else {
        die('ERROR:FRIENDSHIP DISABLED');
    }
}
