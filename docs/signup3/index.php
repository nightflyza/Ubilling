<?php

header('Last-Modified: ' . date('r'));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

include('modules/engine/api.ubrouting.php');
include('modules/engine/api.omaeurl.php');
include('modules/engine/api.compat.php');
include('modules/engine/api.astral.php');
include('modules/engine/api.signup.php');

$signup = new SignupService();

if (!ubRouting::checkPost(array('createrequest'))) {
    if (!ubRouting::checkGet(array('success'))) {
        show_window('', $signup->renderForm());
    } else {
        $successBody = wf_tag('p') . __('Your inquiry will be dealt with in the shortest possible time, and you will be contacted by our representative for details of connection.') . wf_tag('p', true);
        $successBody .= wf_Link('index.php', 'Back', false, 'sn-submit');
        show_window(__('Thank you'), $successBody);
    }
} else {
    $request = $signup->createRequest();
    if ($request) {
        rcms_redirect('?success=yeah');
    } else {
        $errorText = $signup->getLastError();
        if ($errorText == '') {
            $errorText = sn_RequiredHint();
        }
        $errorBody = wf_tag('p') . $errorText . wf_tag('p', true);
        $errorBody .= wf_Link('index.php', 'Try again', false, 'sn-submit');
        show_window(__('Error'), $errorBody, 'sn-window-error');
    }
}

if (isset($snConfig['debug'])) {
    if ($snConfig['debug']) {
        $mtime = explode(' ', microtime());
        $totaltime = $mtime[0] + $mtime[1] - $starttime;
        show_window(__('Debug'), 'GT: ' . round($totaltime, 4));
    }
}

sn_ShowTemplate();
