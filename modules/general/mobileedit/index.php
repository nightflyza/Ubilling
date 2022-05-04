<?php

if (cfr('MOBILE')) {

    if (wf_CheckGet(array('username'))) {
        $altCfg = $ubillingConfig->getAlter();

        $login = vf($_GET['username']);
        // change mobile if need
        if (isset($_POST['newmobile'])) {
            $mobile = $_POST['newmobile'];
            zb_UserChangeMobile($login, $mobile);
            rcms_redirect("?module=mobileedit&username=" . $login);
        }

        $current_mobile = zb_UserGetMobile($login);
        $useraddress = zb_UserGetFullAddress($login) . ' (' . $login . ')';


        // Edit form construct
        $fieldnames = array('fieldname1' => __('Current mobile'), 'fieldname2' => __('New mobile'));
        $fieldkey = 'newmobile';
        if (@$altCfg['MOBILE_FILTERS_DISABLED']) {
            $formFilters = '';
        } else {
            $formFilters = 'mobile';
        }
        $form = web_EditorStringDataForm($fieldnames, $fieldkey, $useraddress, $current_mobile, $formFilters);
        // Edit form display        
        show_window(__('Edit mobile'), $form);

        // Additional mobile management        
        if (isset($altCfg['MOBILES_EXT'])) {
            if ($altCfg['MOBILES_EXT']) {
                $extMobiles = new MobilesExt();
                //new additional mobile creation
                if (ubRouting::checkPost(array($extMobiles::PROUTE_NEW_LOGIN, $extMobiles::PROUTE_NEW_NUMBER))) {
                    $newLogin = ubRouting::post($extMobiles::PROUTE_NEW_LOGIN);
                    $newNumber = ubRouting::post($extMobiles::PROUTE_NEW_NUMBER);
                    $newNotes = ubRouting::post($extMobiles::PROUTE_NEW_NOTES);
                    $extMobiles->createUserMobile($newLogin, $newNumber, $newNotes);
                    ubRouting::nav($extMobiles::URL_ME . '&' . $extMobiles::ROUTE_LOGIN . '=' . $login);
                }

                //existing additional mobile deletion
                if (ubRouting::checkGet($extMobiles::ROUTE_DELETE_ID)) {
                    $extMobiles->deleteUserMobile(ubRouting::get($extMobiles::ROUTE_DELETE_ID));
                    ubRouting::nav($extMobiles::URL_ME . '&' . $extMobiles::ROUTE_LOGIN . '=' . $login);
                }

                //updating existing additional mobile number
                if (ubRouting::checkPost(array($extMobiles::PROUTE_ED_ID, $extMobiles::PROUTE_ED_NUMBER))) {
                    $updateExtId = ubRouting::post($extMobiles::PROUTE_ED_ID);
                    $updateNumber = ubRouting::post($extMobiles::PROUTE_ED_NUMBER);
                    $updateNotes = ubRouting::post($extMobiles::PROUTE_ED_NOTES);
                    $extMobiles->updateUserMobile($updateExtId, $updateNumber, $updateNotes);
                    ubRouting::nav($extMobiles::URL_ME . '&' . $extMobiles::ROUTE_LOGIN . '=' . $login);
                }
                $extCreateForm = $extMobiles->renderCreateForm($login);
                $extList = $extMobiles->renderUserMobilesList($login);
                show_window(__('Additional mobile phones'), $extList . $extCreateForm);

                if ($altCfg['ASKOZIA_ENABLED']) {
                    $extMobiles->fastAskoziaAttachForm($login);
                }
            }
        }
        //User back to profile controls
        show_window('', web_UserControls($login));
    } else {
        show_error(__('Strange exeption') . ': EX_NO_USERNAME');
    }
} else {
    show_error(__('You cant control this module'));
}
?>
