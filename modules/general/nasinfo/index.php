<?php

$result = '';
if (cfr('USERPROFILE')) {
    if (ubRouting::get('ip', 'mres')) {
        $userIp = ubRouting::get('ip', 'mres');

        $nethosts = new NyanORM('nethosts');
        $nases = new NyanORM('nas');
        $networks = new NyanORM('networks');

        //getting some user nethost data
        $nethosts->where('ip', '=', $userIp);
        $userNethost = $nethosts->getAll();

        if (!empty($userNethost)) {
            $userNetId = $userNethost[0]['netid'];
            $userNetId = ubRouting::filters($userNetId, 'int');

            $nases->where('netid', '=', $userNetId);
            $allNases = $nases->getAll('id');

            if (!empty($allNases)) {
                $rows = '';
                //all NASes for this network
                foreach ($allNases as $io => $each) {
                    $cells = wf_TableCell(__('NAS'), '', 'row1');
                    $cells .= wf_TableCell($each['nasname']);
                    $cells .= wf_TableCell($each['nasip']);
                    $rows .= wf_TableRow($cells, 'row2');
                }
                //Network info
                if (!empty($userNetId)) {
                    $networks->where('id', '=', $userNetId);
                    $userNetwork = $networks->getAll();
                    $userNetwork = $userNetwork[0];

                    $cells = wf_TableCell(__('Network'), '', 'row1');
                    $cells .= wf_TableCell($userNetwork['nettype']);
                    $cells .= wf_TableCell($userNetwork['desc']);
                    $rows .= wf_TableRow($cells, 'row2');
                }

                $result .= wf_TableBody($rows, '40%', '0', '');
            } else {
                $messages = new UbillingMessageHelper();
                $result .= $messages->getStyledMessage(__('No NAS servers assigned for this user network'), 'warning');
            }
        } else {
            $result = __('Strange exeption') . ': NO_NETHOST_DATA';
        }
    } else {
        $result = __('Strange exeption') . ': NO_GET_IP';
    }
} else {
    $result = __('Access denied');
}

die($result);
?>