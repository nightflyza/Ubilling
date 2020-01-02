<?php

if (cfr('ENVY')) {
    $altCfg = $ubillingConfig->getAlter();

    if (@$altCfg['ENVY_ENABLED']) {
        set_time_limit(0);

        $envy = new Envy();
        //new script creation
        if (ubRouting::checkPost(array('newscriptmodel'))) {
            $creationResult = $envy->createScript(ubRouting::post('newscriptmodel'), ubRouting::post('newscriptdata'));
            if (empty($creationResult)) {
                ubRouting::nav($envy::URL_ME);
            } else {
                show_error($creationResult);
            }
        }

        //existing script deletion
        if (ubRouting::checkGet(array('deletescript'))) {
            $deletionResult = $envy->deleteScript(ubRouting::get('deletescript'));
            if (empty($deletionResult)) {
                ubRouting::nav($envy::URL_ME);
            } else {
                show_error($deletionResult);
            }
        }

        //existing script editin
        if (ubRouting::checkPost('editscriptid')) {
            $savingResult = $envy->saveScript();
            if (empty($savingResult)) {
                ubRouting::nav($envy::URL_ME);
            } else {
                show_error($savingResult);
            }
        }

        //new device creation
        if (ubRouting::checkPost('newdeviceswitchid')) {
            $devCreationResult = $envy->createDevice();
            if (empty($devCreationResult)) {
                ubRouting::nav($envy::URL_ME);
            } else {
                show_error($devCreationResult);
            }
        }

        if (ubRouting::checkGet('previewdevice')) {
            show_window('', wf_BackLink($envy::URL_ME));
            debarr($envy->runDeviceScript(ubRouting::get('previewdevice')));
        } else {
            show_window('DEBUG CONTROLS', $envy->renderControls());
            deb($envy->renderDeviceCreateForm());
            deb($envy->renderDevicesList());
            show_window(__('Available envy scripts'), $envy->renderScriptsList());
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}