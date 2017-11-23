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
        $form = web_EditorStringDataForm($fieldnames, $fieldkey, $useraddress, $current_mobile, 'mobile');
        // Edit form display        
        show_window(__('Edit mobile'), $form);

        // Additional mobile management        
        if (isset($altCfg['MOBILES_EXT'])) {
            if ($altCfg['MOBILES_EXT']) {
                $extMobiles = new MobilesExt();
                //new additional mobile creation
                if (wf_CheckPost(array('newmobileextlogin', 'newmobileextnumber'))) {
                    $extMobiles->createUserMobile($_POST['newmobileextlogin'], $_POST['newmobileextnumber'], $_POST['newmobileextnotes']);
                    rcms_redirect($extMobiles::URL_ME . '&username=' . $login);
                }

                //existing additoinal mobile deletion
                if (wf_CheckGet(array('deleteext'))) {
                    $extMobiles->deleteUserMobile($_GET['deleteext']);
                    rcms_redirect($extMobiles::URL_ME . '&username=' . $login);
                }
                $extCreateForm = $extMobiles->renderCreateForm($login);
                $extList = $extMobiles->renderUserMobilesList($login);
                show_window(__('Additional mobile phones'), $extList . $extCreateForm);
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
