<?php

if ($ubillingConfig->getAlterParam('MULTIGEN_ENABLED')) {
    if (cfr('MULTIGEN')) {
        set_time_limit(0);
        $multigen = new MultiGen();

        if (ubRouting::checkGet('editnasoptions')) {
            $editNasId = ubRouting::get('editnasoptions');
            //editing NAS options
            if (ubRouting::checkPost('editnasid')) {
                $nasOptionsSaveResult = $multigen->saveNasOptions();
                if (empty($nasOptionsSaveResult)) {
                    ubRouting::nav($multigen::URL_ME . '&editnasoptions=' . $editNasId);
                } else {
                    show_error($nasOptionsSaveResult);
                }
            }

            //editing NAS services templates
            if (ubRouting::checkPost('newnasservicesid')) {
                $nasServicesSaveResult = $multigen->saveNasServices();
                if (empty($nasServicesSaveResult)) {
                    ubRouting::nav($multigen::URL_ME . '&editnasoptions=' . $editNasId);
                } else {
                    show_error($nasServicesSaveResult);
                }
            }

            //creating some attributes
            if (ubRouting::checkPost('newattributenasid')) {
                $nasAttributeCreationResult = $multigen->createNasAttribute();
                if (empty($nasAttributeCreationResult)) {
                    ubRouting::nav($multigen::URL_ME . '&editnasoptions=' . ubRouting::post('newattributenasid'));
                } else {
                    show_error($nasAttributeCreationResult);
                }
            }

            //editing existing attribute template
            if (ubRouting::checkPost('chattributenasid')) {
                $nasAttributeChangeResult = $multigen->saveNasAttribute();
                if (empty($nasAttributeChangeResult)) {
                    ubRouting::nav($multigen::URL_ME . '&editnasoptions=' . ubRouting::post('chattributenasid'));
                } else {
                    show_error($nasAttributeCreationResult);
                }
            }

            //deletion of existing attribute 
            if (ubRouting::checkGet('deleteattributeid')) {
                $attributeDeletionResult = $multigen->deleteNasAttribute(ubRouting::get('deleteattributeid'));
                if (empty($attributeDeletionResult)) {
                    ubRouting::nav($multigen::URL_ME . '&editnasoptions=' . $editNasId);
                } else {
                    show_error($attributeDeletionResult);
                }
            }

            //manual atrributes regeneration
            if (ubRouting::checkGet('ajnasregen')) {
                $multigen->generateNasAttributes();
                die($multigen->renderScenarioStats());
            }

            //flush all scenarios attributes
            if (ubRouting::checkGet('ajscenarioflush')) {
                $multigen->flushAllScenarios();
                die($multigen->renderFlushAllScenariosNotice());
            }

            //cloning NAS options
            if (ubRouting::checkPost(array('clonenasfromid', 'clonenastoid'))) {
                if (ubRouting::checkPost('clonenasagree')) {
                    $nasCloneResult = $multigen->cloneNasConfiguration(ubRouting::post('clonenasfromid'), ubRouting::post('clonenastoid'));
                    if (empty($nasCloneResult)) {
                        ubRouting::nav($multigen::URL_ME . '&editnasoptions=' . $editNasId);
                    } else {
                        show_error($nasCloneResult);
                    }
                } else {
                    show_error(__('You are not mentally prepared for this'));
                }
            }

            //copypasting NAS options
            if (ubRouting::checkPost('nascopypastetext')) {
                if (ubRouting::checkPost('nascopypasteagree')) {
                    $nasCopyPasteResult = $multigen->pasteNasConfiguration($editNasId, ubRouting::post('nascopypastetext'));
                    if (empty($nasCopyPasteResult)) {
                        ubRouting::nav($multigen::URL_ME . '&editnasoptions=' . $editNasId);
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
            if (ubRouting::checkGet('dlmultigenlog')) {
                $multigen->logDownload();
            }

            if (ubRouting::checkGet('ajacct')) {
                $multigen->renderAcctStatsAjList();
            }

            if (!ubRouting::checkGet('manualpod') AND ! ubRouting::checkGet('userattributes')) {
                if (!ubRouting::checkGet('lastsessions')) {
                    //ignored in lastsessions
                    $dateFormControls = $multigen->renderDateSerachControls();
                } else {
                    $dateFormControls = '';
                }
                show_window(__('Multigen NAS sessions stats') . ' ' . $multigen->renderLogControl(), $dateFormControls . $multigen->renderAcctStatsContainer());
            } else {
                //manual POD
                if (ubRouting::checkGet('manualpod')) {

                    if (ubRouting::checkPost('manualpod')) {
                        $manualPodResult = $multigen->runManualPod();
                        if (empty($manualPodResult)) {
                            ubRouting::nav($multigen::URL_ME . '&manualpod=true&username=' . ubRouting::get('username'));
                        } else {
                            show_error($manualPodResult);
                        }
                    }

                    show_window(__('Terminate user session'), $multigen->renderManualPodForm(ubRouting::get('username')));
                }

                //render user attributes
                if (ubRouting::checkGet(array('userattributes', 'username'))) {
                    //attribute deletion from some scenario
                    if (ubRouting::checkGet(array('delattr', 'delscenario'))) {
                        $attrDeletionResult = $multigen->deleteUserAttribute(ubRouting::get('delattr', 'int'), ubRouting::get('delscenario', 'mres'));
                        if (empty($attrDeletionResult)) {
                            ubRouting::nav($multigen::URL_ME . '&userattributes=true&username=' . ubRouting::get('username', 'mres'));
                        } else {
                            show_error($attrDeletionResult);
                        }
                    }

                    //render users attributes list
                    show_window(__('User attributes'), $multigen->renderUserAttributes(ubRouting::get('username', 'mres')));
                }
            }
        }

        zb_BillingStats(true);
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
