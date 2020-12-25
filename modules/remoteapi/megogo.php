<?php

//Megogo userstats control options
if ($_GET['action'] == 'mgcontrol') {
    if ($alterconf['MG_ENABLED']) {
        if (wf_CheckGet(array('param', 'tariffid', 'userlogin'))) {

            if ($_GET['param'] == 'subscribe') {
                $mgIface = new MegogoInterface();
                $mgSubResult = $mgIface->createSubscribtion($_GET['userlogin'], $_GET['tariffid']);
                die($mgSubResult);
            }

            if ($_GET['param'] == 'unsubscribe') {
                $mgIface = new MegogoInterface();
                $mgUnsubResult = $mgIface->scheduleUnsubscribe($_GET['userlogin'], $_GET['tariffid']);
                die($mgUnsubResult);
            }
        }

        if (wf_CheckGet(array('param', 'userlogin'))) {
            if ($_GET['param'] == 'auth') {
                $mgApi = new MegogoApi();
                $authUrlData = $mgApi->authCode($_GET['userlogin']);
                die($authUrlData);
            }
        }
    } else {
        die('ERROR: MEGOGO DISABLED');
    }
}


//Megogo schedule processing
if ($_GET['action'] == 'mgqueue') {
    if ($alterconf['MG_ENABLED']) {
        $mgIface = new MegogoInterface();
        $mgQueueProcessingResult = $mgIface->scheduleProcessing();
        die($mgQueueProcessingResult);
    } else {
        die('ERROR: MEGOGO DISABLED');
    }
}

//Megogo fee processing (daily/monthly)
if ($_GET['action'] == 'mgprocessing') {
    if ($alterconf['MG_ENABLED']) {
        $mgIface = new MegogoInterface();
        $mgFeeProcessingResult = $mgIface->subscriptionFeeProcessing();
        die($mgFeeProcessingResult);
    } else {
        die('ERROR: MEGOGO DISABLED');
    }
}


//Megogo free subscriptions cleanup
if ($_GET['action'] == 'mgfreecleanup') {
    if ($alterconf['MG_ENABLED']) {
        $mgIface = new MegogoInterface();
        $mgFeeProcessingResult = $mgIface->subscriptionFreeCleanup();
        die($mgFeeProcessingResult);
    } else {
        die('ERROR: MEGOGO DISABLED');
    }
}