<?php

if (cfr('SWITCHID')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['SWITCHES_EXTENDED']) {
        $messages = new UbillingMessageHelper();
        $result = wf_BackLink('?module=switches', __('Back'), true, 'ubButton');
        $result .= wf_tag('br');

        $allSwitchModels = zb_SwitchModelsGetAllTag();
        $switchesDb = new NyanORM('switches');
        $allsw = $switchesDb->getAll();

        $columns = array('Location', 'IP', 'Model', 'Remote ID', 'Actions');
        $dataArr = array();

        if (!empty($allsw)) {
            foreach ($allsw as $io => $eachsw) {
                if (!empty($eachsw['swid'])) {
                    $swLink = wf_Link('?module=switches&edit=' . $eachsw['id'], web_edit_icon());
                    $dataArr[] = array(
                        $eachsw['location'],
                        $eachsw['ip'],
                        @$allSwitchModels[$eachsw['modelid']],
                        $eachsw['swid'],
                        $swLink
                    );
                }
            }

            $result .= wf_JqDtEmbed($columns, $dataArr, false, 'Switches', 50);
        } else {
            $result .= $messages->getStyledMessage(__('No switches found'), 'info');
        }

        show_window(__('Remote Switch ID Module'), $result);
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
