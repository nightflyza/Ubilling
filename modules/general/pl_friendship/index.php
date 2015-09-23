<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['FRIENDSHIP_ENABLED']) {
    if (cfr('FRIENDSHIP')) {
        if (wf_CheckGet(array('username'))) {
            $login = $_GET['username'];
            $friends = new FriendshipIsMagic();

            //friendship creation
            if (wf_CheckPost(array('newparentlogin', 'newfriendlogin'))) {
                $friends->createFriend($_POST['newfriendlogin'], $_POST['newparentlogin']);
                rcms_redirect('?module=pl_friendship&username=' . $login);
            }

            //friend deletion
            if (wf_CheckGet(array('deletefriend'))) {
                $friends->deleteFriend($_GET['deletefriend']);
                rcms_redirect('?module=pl_friendship&username=' . $login);
            }

            show_window('', $friends->renderCreateForm($login));
            show_window(__('Available friends'), $friends->renderFriendsList($login));
            show_window('', web_UserControls($login));
        } else {
            show_error(__('Strange exeption') . ': GET_NO_USERNAME');
        }
    } else {
        show_error(__('You cant control this module'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>