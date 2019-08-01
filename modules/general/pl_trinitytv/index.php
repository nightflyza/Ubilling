<?php

if ($ubillingConfig->getAlterParam('TRINITYTV_ENABLED')) {
    if (cfr('TRINITYTV')) {

        if (ubRouting::get('username')) {
            $userLogin = ubRouting::get('username', 'mres');
            $subscribers = new nya_trinitytv_subscribers();
            $subscribers->where('login', '=', $userLogin);
            $assignedSubscriber = $subscribers->getAll();
            if (!empty($assignedSubscriber)) {
                $subscriberProfileUrl = TrinityTv::URL_SUBSCRIBER . $assignedSubscriber[0]['id'];
                ubRouting::nav($subscriberProfileUrl);
            } else {
                show_warning(__('This user account is not associated with any existing TrinityTV subscriber'));
                show_window('', web_UserControls($userLogin));
            }
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module disabled'));
}