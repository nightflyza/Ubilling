<?php

$altcfg = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
if ($altcfg['ONU_MASTER_ENABLED']) {
    if (cfr('ONUMASTER')) {
        if (isset($_GET['username'])) {
            $onuMaster = new OnuMaster($_GET['username']);
            $onuMaster->renderMain($_GET['username']);

            if (isset($_POST['RebootOnu'])) {
                $rebootResult = $onuMaster->reboot->RebootOnu();
                if ($rebootResult) {
                    show_success('DONE');
                } else {
                    show_error('ONU NOT FOUND');
                }
            }

            if (isset($_POST['DescribeOnu'])) {
                $describeResult = $onuMaster->describe->DescribeOnu($_POST['onuDescription']);
                if (!empty($describeResult)) {
                    show_success($describeResult);
                } else {
                    show_error('Unsuccessful');
                }
            }
        }
    }
}
    