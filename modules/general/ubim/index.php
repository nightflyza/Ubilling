<?php

if (cfr('UBIM')) {
    $altcfg = $ubillingConfig->getAlter();

//posting new message 
    if (wf_CheckPost(array('im_message_to', 'im_message_text'))) {
        im_CreateMessage($_POST['im_message_to'], $_POST['im_message_text']);
        rcms_redirect("?module=ubim&gothread=" . $_POST['im_message_to']);
    }

//checking for new messages 
    if (!wf_CheckGet(array('checknew'))) {
        $unreadMessageCount = im_CheckForUnreadMessages();
        if ($unreadMessageCount) {
            $unreadIMNotify = __('You received') . ' ' . $unreadMessageCount . ' ' . __('new messages');
            $urlIM = $unreadIMNotify . wf_delimiter() . wf_Link("?module=ubim&checknew=true", __('Click here to go to the instant messaging service.'), false, 'ubButton');
            show_window('', wf_modalOpened(__('New messages received'), $urlIM, '450', '200'));
        }
    }


//ajax thread data    
    if (wf_CheckGet(array('showthread'))) {
        $threadContent = im_ConversationForm($_GET['showthread']) . im_ThreadShow($_GET['showthread']);
        die($threadContent);
    }

//refresh time testing  for unread messages
    if (wf_CheckGet(array('timecheckunread'))) {
        $unreadMessageCount = im_CheckForUnreadMessages();
        if ($unreadMessageCount) {
            die(json_encode(array('messagesCount' => $unreadMessageCount)));
        }
    }

    if (!wf_checkGet(array('avatarcontrol'))) {
//display main grid
        $mainGrid = im_MainWindow();
        show_window(__('Instant messaging service'), $mainGrid);
        if (!wf_CheckGet(array('timecheckunread'))) {
            //update notification area
            $darkVoid = new DarkVoid();
            $darkVoid->flushCache();
        }
    } else {
        //avatar control and mail change form
        show_window(__('Avatar control'), im_AvatarControlForm());
    }


//refresh container 
    if ($altcfg['UBIM_REFRESH']) {
        im_RefreshContainer($altcfg['UBIM_REFRESH']);
    }




// direct link thread loader
    if (wf_CheckGet(array('gothread'))) {
        show_window('', im_ThreadLoad($_GET['gothread']));
    }
    zb_BillingStats(true);
} else {
    show_error(__('You cant control this module'));
}
?>
