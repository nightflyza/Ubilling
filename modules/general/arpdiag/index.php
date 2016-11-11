<?php

if (cfr('ARPDIAG')) {

    $alterconf = $ubillingConfig->getAlter();
    if ($alterconf['ARPDIAG_ENABLED']) {
        $messages = new UbillingMessageHelper();

        $config = $ubillingConfig->getBilling();
        $log_path = $alterconf['ARPDIAG_LOG'];
        $sudo_path = $config['SUDO'];
        $cat_path = $config['CAT'];
        $grep_path = $config['GREP'];
        $command = $sudo_path . ' ' . $cat_path . ' ' . $log_path . ' | ' . $grep_path . ' "arp"';
        $rawdata = shell_exec($command);
        $tablerows = '';
        if (!empty($rawdata)) {
            $splitdata = explodeRows($rawdata);
            if (!empty($splitdata)) {
                foreach ($splitdata as $eachrow) {
                    $rowclass = 'row3';
                    if (!empty($eachrow)) {
                        if (ispos($eachrow, 'attemp')) {
                            $rowclass = 'ukvbankstadup';
                        }

                        if (ispos($eachrow, 'moved from')) {
                            $rowclass = 'undone';
                        }


                        if (ispos($eachrow, 'ETHERTYPE')) {
                            $rowclass = 'sigcemeteryuser';
                        }

                        if (ispos($eachrow, 'hardware')) {
                            $rowclass = 'donetask';
                        }

                        if (ispos($eachrow, 'flip flop')) {
                            $rowclass = 'undone';
                        }

                        $tablecells = wf_TableCell($eachrow);
                        $tablerows.=wf_TableRow($tablecells, $rowclass);
                    }
                }
            }

            $result = wf_TableBody($tablerows, '100%', '0', '');
        } else {
            $result = $messages->getStyledMessage(__('It seems there is nothing unusual'), 'info');
        }

        show_window(__('Diagnosing problems with the ARP'), $result);
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
