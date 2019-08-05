<?php

//OmegaTV userstats control options
if ($_GET['action'] == 'omcontrol') {
    if ($alterconf['OMEGATV_ENABLED']) {
        $omega = new OmegaTV();
        if (wf_CheckGet(array('param', 'tariffid', 'userlogin'))) {
            if ($_GET['param'] == 'subscribe') {
                $omSubResult = $omega->createSubscription($_GET['userlogin'], $_GET['tariffid']);
                die($omSubResult);
            }
        }

        if (wf_CheckGet(array('param', 'tariffid', 'userlogin'))) {
            if ($_GET['param'] == 'unsubscribe') {
                $omUnsubResult = $omega->deleteSubscription($_GET['userlogin'], $_GET['tariffid']);
                die($omUnsubResult);
            }
        }

        if (wf_CheckGet(array('param'))) {
            if ($_GET['param'] == 'chargefee') {
                $omega->chargeAllUsersFee();
                die('OMEGATV_CHARGE_DONE');
            }

            if ($_GET['param'] == 'resurrect') {
                $omega->resurrectAllUsers();
                die('OMEGATV_RESURRECT_DONE');
            }
        }


        if (wf_CheckGet(array('param', 'userlogin'))) {
            if ($_GET['param'] == 'viewurl') {
                die($omega->getWebUrlByLogin($_GET['userlogin']));
            }

            if ($_GET['param'] == 'getcode') {
                die($omega->getDeviceCodeByLogin($_GET['userlogin']));
            }

            if ($_GET['param'] == 'getdevices') {
                die($omega->getUserDevicesData($_GET['userlogin']));
            }

            if ($_GET['param'] == 'getplaylists') {
                die($omega->getUserPlaylistsData($_GET['userlogin']));
            }

            if ($_GET['param'] == 'deletedev') {
                if (wf_CheckGet(array('uniq'))) {
                    die($omega->deleteUserDevice($_GET['userlogin'], $_GET['uniq']));
                }
            }

            if ($_GET['param'] == 'deletepl') {
                if (wf_CheckGet(array('uniq'))) {
                    die($omega->deleteUserPlaylist($_GET['userlogin'], $_GET['uniq']));
                }
            }

            if ($_GET['param'] == 'assignpl') {
                die($omega->assignUserPlaylist($_GET['userlogin']));
            }
        }
    } else {
        die('ERROR: OMEGATV_DISABLED');
    }
}