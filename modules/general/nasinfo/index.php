<?php

$result = '';
if (cfr('USERPROFILE')) {
    if (ubRouting::get('ip', 'mres')) {
        $userIp = ubRouting::get('ip', 'mres');

        $nethosts = new NyanORM('nethosts');
        $nases = new NyanORM('nas');

        //getting some user nethost data
        $nethosts->where('ip', '=', $userIp);
        $userNethost = $nethosts->getAll();

        if (!empty($userNethost)) {
            $userNetId = $userNethost[0]['netid'];
            $nases->where('netid', '=', $userNetId);
            $allNases = $nases->getAll('id');

            if (!empty($allNases)) {
                $rows = '';
                foreach ($allNases as $io => $each) {
                    $cells = wf_TableCell($each['nasname']);
                    $cells .= wf_TableCell($each['nasip']);
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