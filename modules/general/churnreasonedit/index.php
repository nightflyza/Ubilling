<?php

if (cfr('CHURNREASONS')) {
  if ($ubillingConfig->getAlterParam('CHURN_REASONS_ENABLED')) {
    if (ubRouting::checkGet('username')) {
        $userLogin = ubRouting::filters(ubRouting::get('username'),'login');
        $userAddress=zb_UserGetFullAddress($userLogin);

        $churnReasons = new ChurnReasons($userLogin);

        show_window(__('Churn reason').' '.$userAddress, $churnReasons->renderChurnController());
        zb_BillingStats();
        show_window('', web_UserControls($userLogin));
        
    } else {
        show_error(__('Strange exception') . ': ' . __('Empty login'));
        show_window('', wf_tag('center') . wf_img('skins/unicornwrong.png') . wf_tag('center', true));
    }

  } else {
    show_error(__('This module is disabled'));
  }
} else {
    show_error(__('Access denied'));
}