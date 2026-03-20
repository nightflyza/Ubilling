<?php

$result = '';

if ($darkVoidContext['ubConfig']->getAlterParam('BIRTHDAY_REMINDER')) {
    if (cfr('EMPLOYEEDIR')) {
        $todayBirthdays = em_EmployeeGetTodayBirthdays();
        if (!empty($todayBirthdays)) {
            $birthdayList = '';
            foreach ($todayBirthdays as $eachId => $eachData) {
                $birthdayList .= $eachData['name'] . ' ';
            }
            $result .= wf_Link('?module=employee', wf_img('skins/cake32.png', __('Birthday') . ' ' . __('today') . ': ' . $birthdayList));
        }
    }
}

return ($result);
