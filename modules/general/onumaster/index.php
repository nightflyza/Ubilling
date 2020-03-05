<?php
$altcfg = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');

if ($altcfg['ONU_MASTER_ENABLED']) {
    if (cfr('ONUMASTER')) {
        if (ubRouting::checkGet('username')) {
            $userLogin = ubRouting::get('username');
            $onuMaster = new OnuMaster($userLogin);

            if (ubRouting::checkPost('RebootOnu')) {
                $rebootResult = $onuMaster->reboot->rebootOnu();

                if ($rebootResult) {
                    show_success('DONE');
                    log_register('ONUMASTER ONU reboot for login [' . $userLogin . ']');
                } else {
                    show_error($onuMaster->reboot->displayMessage);
                    log_register('ONUMASTER ONU reboot failed for login [' . $userLogin . ']. Message: ' . $onuMaster->reboot->displayMessage);
                }
            }

            if (ubRouting::checkPost('DeleteOnu')) {
                $delResult = $onuMaster->delete->deleteOnu();

                if ($delResult) {
                    show_success('DONE');
                    log_register('ONUMASTER ONU delete for login [' . $userLogin . ']');
                } else {
                    show_error($onuMaster->delete->displayMessage);
                    log_register('ONUMASTER ONU delete failed for login [' . $userLogin . ']. Message: ' . $onuMaster->delete->displayMessage);
                }
            }

            if (ubRouting::checkPost('DeregOnu')) {
                $deregResult = $onuMaster->deregister->deregOnu();

                if ($deregResult) {
                    show_success('DONE');
                    log_register('ONUMASTER ONU deregister for login [' . $userLogin . ']');
                } else {
                    show_error($onuMaster->deregister->displayMessage);
                    log_register('ONUMASTER ONU deregister failed for login [' . $userLogin . ']. Message: ' . $onuMaster->deregister->displayMessage);
                }
            }

            if (ubRouting::checkPost('DescribeOnu', false)) {
                $describeResult = $onuMaster->describe->describeOnu($_POST['onuDescription']);

                if ($onuMaster->describe->operationSuccessful) {
                    $describeResult = trim($describeResult, " \t\n\r\0\x0B\x31\x22");
                    $describeResult = ($describeResult === '') ? wf_nbsp() : $describeResult;
                    show_success($describeResult);
                    log_register('ONUMASTER ONU description change for login [' . $userLogin . '] to `' . $describeResult . '`');
                } else {
                    show_error($onuMaster->describe->displayMessage);
                    log_register('ONUMASTER ONU description change failed for login [' . $userLogin . ']. Message: ' . $onuMaster->describe->displayMessage);
                }
            }

            $onuMaster->renderMain($userLogin);
        }
    }
}