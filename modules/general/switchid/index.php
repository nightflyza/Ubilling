<?php

if (cfr('SWITCHID')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['SWITCHES_EXTENDED']) {
        $messages = new UbillingMessageHelper();
        $result = wf_BackLink('?module=switches', __('Back'), true, 'ubButton');
        $result.=wf_tag('br');

        $query = "SELECT switches.modelid,switches.ip,switches.id,switches.swid,switches.location,switchmodels.id,switchmodels.modelname "
                . "FROM switches,switchmodels WHERE switches.modelid=switchmodels.id";
        $allsw = simple_queryall($query);

        if (!empty($allsw)) {

            $tablecells = wf_TableCell(__('Location'));
            $tablecells.=wf_TableCell(__('IP'));
            $tablecells.=wf_TableCell(__('Model'));
            $tablecells.=wf_TableCell(__('Remote ID'));
            $tablerows = wf_TableRow($tablecells, 'row1');

            foreach ($allsw as $io => $eachsw) {
                $swloc = $eachsw['location'];
                $swip = $eachsw['ip'];
                $swmod = $eachsw['modelname'];
                $swid = $eachsw['swid'];

                if (!empty($swid)) {

                    $tablecells = wf_TableCell($swloc);
                    $tablecells.=wf_TableCell($swip);
                    $tablecells.=wf_TableCell($swmod);
                    $tablecells.=wf_TableCell($swid);
                    $tablerows.=wf_TableRow($tablecells, 'row5');
                }
            }
            $result.= wf_TableBody($tablerows, '100%', '0', 'sortable');
        } else {
            $result.= $messages->getStyledMessage(__('No switches found'), 'info');
        }

        show_window(__('Remote Switch ID Module'), $result);
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>