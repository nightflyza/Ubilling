<?php

if ($ubillingConfig->getAlterParam('MULTIGEN_ENABLED')) {
    if (cfr('MULTIGEN')) {
        set_time_limit(0);
        $multigen = new MultiGen();

        if (wf_CheckGet(array('editnasoptions'))) {
            $editNasId = $_GET['editnasoptions'];
            //editing NAS options
            if (wf_CheckPost(array('editnasid'))) {
                $nasOptionsSaveResult = $multigen->saveNasOptions();
                if (empty($nasOptionsSaveResult)) {
                    rcms_redirect($multigen::URL_ME . '&editnasoptions=' . $editNasId);
                } else {
                    show_error($nasOptionsSaveResult);
                }
            }

            //editing NAS services templates
            if (wf_CheckPost(array('newnasservicesid'))) {
                $nasServicesSaveResult = $multigen->saveNasServices();
                if (empty($nasServicesSaveResult)) {
                    rcms_redirect($multigen::URL_ME . '&editnasoptions=' . $editNasId);
                } else {
                    show_error($nasServicesSaveResult);
                }
            }

            //creating some attributes
            if (wf_CheckPost(array('newattributenasid'))) {
                $nasAttributeCreationResult = $multigen->createNasAttribute();
                if (empty($nasAttributeCreationResult)) {
                    rcms_redirect($multigen::URL_ME . '&editnasoptions=' . $_POST['newattributenasid']);
                } else {
                    show_error($nasAttributeCreationResult);
                }
            }

            //editing existing attribute template
            if (wf_CheckPost(array('chattributenasid'))) {
                $nasAttributeChangeResult = $multigen->saveNasAttribute();
                if (empty($nasAttributeChangeResult)) {
                    rcms_redirect($multigen::URL_ME . '&editnasoptions=' . $_POST['chattributenasid']);
                } else {
                    show_error($nasAttributeCreationResult);
                }
            }

            //deletion of existing attribute 
            if (wf_CheckGet(array('deleteattributeid'))) {
                $attributeDeletionResult = $multigen->deleteNasAttribute($_GET['deleteattributeid']);
                if (empty($attributeDeletionResult)) {
                    rcms_redirect($multigen::URL_ME . '&editnasoptions=' . $editNasId);
                } else {
                    show_error($attributeDeletionResult);
                }
            }

            //manual atrributes regeneration
            if (wf_CheckGet(array('ajnasregen'))) {
                $multigen->generateNasAttributes();
                die($multigen->renderScenarioStats());
            }

            //flush all scenarios attributes
            if (wf_CheckGet(array('ajscenarioflush'))) {
                $multigen->flushAllScenarios();
                die($multigen->renderFlushAllScenariosNotice());
            }

            //cloning NAS options
            if (wf_CheckPost(array('clonenasfromid', 'clonenastoid'))) {
                if (wf_CheckPost(array('clonenasagree'))) {
                    $nasCloneResult = $multigen->cloneNasConfiguration($_POST['clonenasfromid'], $_POST['clonenastoid']);
                    if (empty($nasCloneResult)) {
                        rcms_redirect($multigen::URL_ME . '&editnasoptions=' . $editNasId);
                    } else {
                        show_error($nasCloneResult);
                    }
                } else {
                    show_error(__('You are not mentally prepared for this'));
                }
            }

            //copypasting NAS options
            if (wf_CheckPost(array('nascopypastetext'))) {
                if (wf_CheckPost(array('nascopypasteagree'))) {
                    $nasCopyPasteResult = $multigen->pasteNasConfiguration($editNasId, $_POST['nascopypastetext']);
                    if (empty($nasCopyPasteResult)) {
                        rcms_redirect($multigen::URL_ME . '&editnasoptions=' . $editNasId);
                    } else {
                        show_error($nasCopyPasteResult);
                    }
                } else {
                    show_error(__('You are not mentally prepared for this'));
                }
            }
            //rendering basic options form
            show_window(__('NAS options') . ': ' . $multigen->getNaslabel($editNasId), $multigen->renderNasOptionsEditForm($editNasId));
            if ($multigen->nasHaveOptions($editNasId)) {
                //and attributes form
                show_window(__('Adding of RADIUS-attribute'), $multigen->renderNasAttributesCreateForm($editNasId));
                //listing of some existing attributes
                show_window(__('NAS attributes'), $multigen->renderNasAttributesList($editNasId));
            } else {
                show_warning(__('Before setting up the attributes, you must set the base NAS options'));
            }
            //rendering NAS control panel
            show_window('', $multigen->nasControlPanel($editNasId));
        } else {
            //render some accounting stats
            if (wf_CheckGet(array('dlmultigenlog'))) {
                $multigen->logDownload();
            }

            if (wf_CheckGet(array('ajacct'))) {
                $multigen->renderAcctStatsAjList();
            }

            if (!wf_CheckGet(array('manualpod'))) {
                if (!wf_CheckGet(array('lastsessions'))) {
                    //ignored in lastsessions
                    $dateFormControls = $multigen->renderDateSerachControls();
                } else {
                    $dateFormControls = '';
                }
                show_window(__('Multigen NAS sessions stats') . ' ' . $multigen->renderLogControl(), $dateFormControls . $multigen->renderAcctStatsContainer());
            } else {
                //manual POD
                if (wf_CheckPost(array('manualpod'))) {
                    $manualPodResult = $multigen->runManualPod();
                    if (empty($manualPodResult)) {
                        rcms_redirect($multigen::URL_ME . '&manualpod=true&username=' . $_GET['username']);
                    } else {
                        show_error($manualPodResult);
                    }
                }

                show_window(__('Terminate user session'), $multigen->renderManualPodForm($_GET['username']));
            }
        }
        
        zb_BillingStats(true);
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>