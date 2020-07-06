<?php

if (cfr('TASKBAR')) {
    $mapUrl = $ubillingConfig->getAlterParam('BLITZORTUNG_URL');
    if (!empty($mapUrl)) {
        $contentOptions = 'width="100%" height="700" frameborder="0"';
        $content = wf_tag('iframe', false, '', 'src="' . $mapUrl . '" ' . $contentOptions);
        show_window(__('Lightning map'), $content);
    } else {
        show_error(__('Missed config option') . ' BLITZORTUNG_URL ' . __('Or') . ' ' . __('is empty'));
    }
} else {
    show_error(__('Access denied'));
}