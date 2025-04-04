<?php

if ($ubillingConfig->getAlterParam('TAXSUP_ENABLED')) {
    if (cfr('TAXSUP')) {
        if (ubRouting::checkGet('username')) {
            $userLogin = ubRouting::get('username');
            $taxa = new TaxSup();

            //catch new user fee request
            if (ubRouting::checkPost($taxa::PROUTE_FEE, false)) {
                $newFee = ubRouting::post($taxa::PROUTE_FEE);
                $taxa->changeUserFee($userLogin, $newFee);
                ubRouting::nav($taxa::URL_ME . '&' . $taxa::ROUTE_USERNAME . '=' . $userLogin);
            }

            $useraddress = zb_UserGetFullAddress($userLogin) . ' (' . $userLogin . ')';
            $currentUserFee = $taxa->getUserFee($userLogin);

            $fieldnames = array('fieldname1' => __('Current fee'), 'fieldname2' => __('New fee'));
            $fieldkey = $taxa::PROUTE_FEE;
            $feeEditor = web_EditorStringDataForm($fieldnames, $fieldkey, $useraddress, $currentUserFee, 'float');

            show_window(__('Change additional fee'), $feeEditor);
            show_window('', web_UserControls($userLogin));
            zb_BillingStats(true, 'suplimentara');
        } else {
            show_error(__('Strange exception') . ': ' . __('Empty login'));
            show_window('', wf_tag('center') . wf_img('skins/unicornwrong.png') . wf_tag('center', true));
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
