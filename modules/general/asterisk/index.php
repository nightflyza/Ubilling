<?php
if ($ubillingConfig->getAlterParam('ASTERISK_ENABLED')) {
    $asterisk = new Asterisk();

	//getting asterisk data
	if (wf_CheckGet(array('ajax'))) {
		$asterisk->ajaxAvaibleCDR();
	}

    $asterisk->catchFileDownload();

    if (isset($_GET['username'])) {
        $user_login = vf($_GET['username']);
        // Profile:
        $profile = new UserProfile($user_login);
        show_window(__('User profile'), $profile->render());
        if ($ubillingConfig->getAlterParam('ADCOMMENTS_ENABLED') and isset($_GET['addComments'])) {
            $adcomments = new ADcomments('ASTERISK');
            show_window(__('Additional comments'), $adcomments->renderComments($_GET['addComments']));
        }
    } elseif (isset($_GET['AsteriskWindow']) and ! wf_CheckPost(array('datefrom', 'dateto'))) {
        if ($ubillingConfig->getAlterParam('ADCOMMENTS_ENABLED') and isset($_GET['addComments'])) {
            $adcomments = new ADcomments('ASTERISK');
            show_window(__('Additional comments'), $adcomments->renderComments($_GET['addComments']));
        }
    }

    if (cfr('ASTERISK')) {
    //showing configuration form
        if (wf_CheckGet(array('config'))) {
            //changing settings
            if (wf_CheckPost(array('newhost', 'newdb', 'newtable', 'newlogin', 'newpassword'))) {
                $asterisk->AsteriskUpdateConfig($_POST['newhost'],  $_POST['newdb'], $_POST['newtable'], $_POST['newlogin'], $_POST['newpassword'], vf($_POST['newcachetime'], 3), vf($_POST['dopmobile'], 3));
            }

            //aliases creation
            if (wf_CheckPost(array('newaliasnum', 'newaliasname'))) {
                $asterisk->AsteriskCreateAlias($_POST['newaliasnum'],  $_POST['newaliasname']);
            }

            //alias deletion
            if (wf_CheckPost(array('deletealias'))) {
                $asterisk->AsteriskDeleteAlias($_POST['deletealias']);
            }

            show_window(__('Settings'), $asterisk->AsteriskConfigForm());
            show_window(__('Phone book'), $asterisk->AsteriskAliasesForm());
        } else {
            //showing call history form
            show_window(__('Calls history'),$asterisk->panel());

            //and parse some calls history if this needed
            if (wf_CheckPost(array('datefrom', 'dateto')) or isset($user_login)) {
                show_window('', $asterisk->renderAsteriskCDR());
            }
        }
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('Asterisk PBX integration now disabled'));
}
?>
