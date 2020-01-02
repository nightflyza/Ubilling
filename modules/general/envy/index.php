<?php

if (cfr('ENVY')) {
    $altCfg = $ubillingConfig->getAlter();

    if (@$altCfg['ENVY_ENABLED']) {
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

        show_window('DEBUG CONTROLS', $envy->renderControls());
        show_window(__('Available envy scripts'), $envy->renderScriptsList());
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}