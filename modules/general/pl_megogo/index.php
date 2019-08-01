<?php

if ($ubillingConfig->getAlterParam('MG_ENABLED')) {
    if (cfr('MEGOGO')) {

        if (ubRouting::get('username')) {
            $userLogin = ubRouting::get('username', 'mres');
            $subscribers = new nya_mg_subscribers();
            $subscribers->where('login', '=', $userLogin);
            $assignedSubscriber = $subscribers->getAll();
            if (!empty($assignedSubscriber)) {
                $subscriberProfileUrl = MegogoInterface::URL_ME . '&' . MegogoInterface::URL_SUBVIEW . '&subid=' . $assignedSubscriber[0]['id'];
                ubRouting::nav($subscriberProfileUrl);
            } else {
                show_warning(__('This user account is not associated with any existing Megogo subscriber'));
                show_window('', web_UserControls($userLogin));
            }
        }
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module disabled'));
}