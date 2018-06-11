<?php

if (cfr('MULTIGEN')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['MULTIGEN_ENABLED']) {

        $mg = new MultiGen();

        if (wf_CheckGet(array('editnasoptions'))) {
            $editNasId = $_GET['editnasoptions'];
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
                    rcms_redirect($mg::URL_ME . '&editnasoptions=' . $editNasId);
                } else {
                    show_error($attributeDeletionResult);
                }
            }

            //manual atrributes regeneration
            if (wf_CheckGet(array('ajnasregen'))) {
                $mg->generateNasAttributes();
                die($mg->renderScenarioStats());
            }
            //rendering basic options form
            show_window(__('NAS options') . ' ' . $mg->getNaslabel($editNasId), $mg->renderNasOptionsEditForm($editNasId));
            if ($mg->nasHaveOptions($editNasId)) {
                //and attributes form
                show_window(__('Adding of RADIUS-attribute'), $mg->renderNasAttributesEditForm($editNasId));
                //listing of some existing attributes
                show_window(__('NAS attributes'), $mg->renderNasAttributesList($editNasId));
            } else {
                show_warning(__('Before setting up the attributes, you must set the base NAS options'));
            }
            //rendering NAS control panel
            show_window('', $mg->nasControlPanel($editNasId));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>