<?php

if (ubRouting::get('action') == 'autoupdatehook') {
    log_register('UPDMGR AUTOUPDATEHOOK STARTED');

    //optional default administrator account cleanup
    $defaultAdminLogin = 'admin';
    if ($ubillingConfig->getAlterParam('UPDMGR_DEFADM_KILL')) {
        if (file_exists(USERS_PATH . $defaultAdminLogin)) {
            user_delete($defaultAdminLogin);
            if (!file_exists(USERS_PATH . $defaultAdminLogin)) {
                log_register('UPDMGR KILL {' . $defaultAdminLogin . '} SUCCESS');
            } else {
                log_register('UPDMGR KILL {' . $defaultAdminLogin . '} FAILED');
            }
        } else {
            log_register('UPDMGR KILL {' . $defaultAdminLogin . '} NOT_EXISTS');
        }
    } else {
        log_register('UPDMGR KILL {' . $defaultAdminLogin . '} SKIPPED');
    }

    //custom post-upgrage script execution
    $postUpgradeScriptAlias = 'postautoupgrade';
    $onePunch = new OnePunch($postUpgradeScriptAlias);
    $postUpgradeScriptCode = $onePunch->getScriptContent($postUpgradeScriptAlias);
    if (!empty($postUpgradeScriptCode)) {
        log_register('UPDMGR OPPOSTSCRIPT RUN');
        eval($postUpgradeScriptCode);
        log_register('UPDMGR OPPOSTSCRIPT EXECUTED');
    } else {
        log_register('UPDMGR OPPOSTSCRIPT EMPTY');
    }

    log_register('UPDMGR AUTOUPDATEHOOK FINISHED');
    die('OK:AUTOUPDATEHOOK');
}
