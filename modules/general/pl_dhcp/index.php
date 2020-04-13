<?php

if (cfr('PLDHCP')) {


    if (isset($_GET['username'])) {
        $login = $_GET['username'];
        $config = $ubillingConfig->getBilling();
        $alter_conf = $ubillingConfig->getAlter();
        $cat_path = $config['CAT'];
        $grep_path = $config['GREP'];
        $tail_path = $config['TAIL'];
        $sudo_path = $config['SUDO'];
        $leasefile = $alter_conf['NMLEASES'];
        $userdata = zb_UserGetStargazerData($login);
        $user_ip = $userdata['IP'];
        $user_mac = zb_MultinetGetMAC($user_ip);
        $messages = new UbillingMessageHelper();
        $currentMacLabel = $messages->getStyledMessage(wf_tag('h2') . __('Current MAC') . ': ' . $user_mac . wf_tag('h2', true), 'info');
        show_window('', $currentMacLabel);
        $command = $sudo_path . ' ' . $cat_path . ' ' . $leasefile . ' | ' . $grep_path . ' ' . $user_mac . ' | ' . $tail_path . '  -n 30';
        $output = shell_exec($command);
        if (!empty($output)) {
            $result = '';
            $rowdata = '';
            $allrows = explodeRows($output);
            foreach ($allrows as $eachrow) {
                if (!empty($eachrow)) {
                    $celldata = wf_TableCell($eachrow);
                    $rowdata .= wf_TableRow($celldata, 'row3');
                }
            }
            $result = wf_TableBody($rowdata, '100%', 0);
            show_window(__('User DHCP log'), $result);
        } else {
            show_warning(__('User DHCP log') . ': ' . __('Nothing found'));
        }


        show_window('', web_UserControls($login));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
