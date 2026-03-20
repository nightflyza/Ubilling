<?php

$result = '';

if ($darkVoidContext['ubConfig']->getAlterParam('INSURANCE_ENABLED')) {
    $insurance = new Insurance(false);
    $hinsReqCount = $insurance->getUnprocessedHinsReqCount();
    if ($hinsReqCount > 0) {
        $insuranceRequestsAlert = $hinsReqCount . ' ' . __('insurance requests waiting for your reaction');
        $result .= wf_Link($insurance::URL_ME, wf_img('skins/insurance_notify.png', $insuranceRequestsAlert));
    }
}

return ($result);
