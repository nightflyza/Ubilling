<?php

if (cfr('MULTIGEN')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['MULTIGEN_ENABLED']) {

        $mg = new MultiGen();

        if (wf_CheckGet(array('editnasoptions'))) {
            //editing NAS options
            if (wf_CheckPost(array('editnasid'))) {
                $nasOptionsSaveResult = $mg->saveNasOptions();
                if (empty($nasOptionsSaveResult)) {
                    rcms_redirect($mg::URL_ME . '&editnasoptions=' . $_POST['editnasid']);
                } else {
                    show_error($nasOptionsSaveResult);
                }
            }

            //creating some attributes
            if (wf_CheckPost(array('newattributenasid'))) {
                $nasAttributeCreationResult = $mg->createNasAttribute();
                if (empty($nasAttributeCreationResult)) {
                    rcms_redirect($mg::URL_ME . '&editnasoptions=' . $_POST['newattributenasid']);
                } else {
                    show_error($nasAttributeCreationResult);
                }
            }

            //deletion of existing attribute 
            if (wf_CheckGet(array('deleteattributeid'))) {
                $attributeDeletionResult = $mg->deleteNasAttribute($_GET['deleteattributeid']);
                if (empty($attributeDeletionResult)) {
                    rcms_redirect($mg::URL_ME . '&editnasoptions=' . $_GET['editnasoptions']);
                } else {
                    show_error($attributeDeletionResult);
                }
            }
            //rendering basic options form
            show_window(__('NAS options'), $mg->renderNasOptionsEditForm($_GET['editnasoptions']));
            if ($mg->nasHaveOptions($_GET['editnasoptions'])) {
                //and attributes form
                show_window(__('Adding of RADIUS-attribute'), $mg->renderNasAttributesEditForm($_GET['editnasoptions']));
                //listing of some existing attributes
                show_window(__('NAS attributes'), $mg->renderNasAttributesList($_GET['editnasoptions']));
            } else {
                show_warning(__('Before setting up the attributes, you must set the base NAS options'));
            }
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>