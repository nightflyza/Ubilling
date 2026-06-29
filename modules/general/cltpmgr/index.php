<?php

$clapTrapEnabled = $ubillingConfig->getAlterParam('CLAPTRAPBOT_ENABLED');
if ($clapTrapEnabled) {
    if (cfr('ROOT')) {
        $clapTrapMgr = new ClapTrapMgr();

        show_window('', $clapTrapMgr->renderControls());

        if (ubRouting::checkGet($clapTrapMgr::ROUTE_SENDING)) {
            $sendDogFlag = $ubillingConfig->getAlterParam('SENDDOG_ENABLED',false);
            if ($sendDogFlag) {
            set_time_limit(0);
            show_window('', $clapTrapMgr->renderSendingControls());

            if (ubRouting::checkGet($clapTrapMgr::ROUTE_TEMPLATES)) {
                    if (ubRouting::checkPost(array('newtemplatename', 'newtemplatetext'))) {
                        $clapTrapMgr->createTemplate(ubRouting::post('newtemplatename'), ubRouting::post('newtemplatetext'));
                        ubRouting::nav($clapTrapMgr->urlSending($clapTrapMgr::ROUTE_TEMPLATES . '=true'));
                    }

                    if (ubRouting::checkGet('deletetemplate')) {
                        $templateDeletionResult = $clapTrapMgr->deleteTemplate(ubRouting::get('deletetemplate'));
                        if (empty($templateDeletionResult)) {
                            ubRouting::nav($clapTrapMgr->urlSending($clapTrapMgr::ROUTE_TEMPLATES . '=true'));
                        } else {
                            show_error($templateDeletionResult);
                        }
                    }

                    if (ubRouting::checkGet('edittemplate')) {
                        if (ubRouting::checkPost(array('edittemplateid', 'edittemplatename', 'edittemplatetext'))) {
                            $templateEditingResult = $clapTrapMgr->saveTemplate(ubRouting::post('edittemplateid'), ubRouting::post('edittemplatename'), ubRouting::post('edittemplatetext'));
                            if (empty($templateEditingResult)) {
                                ubRouting::nav($clapTrapMgr->urlSending($clapTrapMgr::ROUTE_TEMPLATES . '=true&edittemplate=' . ubRouting::post('edittemplateid')));
                            } else {
                                show_error($templateEditingResult);
                            }
                        }
                        show_window(__('Edit template'), $clapTrapMgr->renderTemplateEditForm(ubRouting::get('edittemplate')));
                    } else {
                        show_window(__('Available templates'), $clapTrapMgr->renderTemplatesList());
                    }
                } else {
                    if (ubRouting::checkGet($clapTrapMgr::ROUTE_FILTERS)) {
                        if (ubRouting::checkPost('newfiltername')) {
                            $creationResult = $clapTrapMgr->createFilter();
                            if (empty($creationResult)) {
                                ubRouting::nav($clapTrapMgr->urlSending($clapTrapMgr::ROUTE_FILTERS . '=true'));
                            } else {
                                show_error($creationResult);
                            }
                        }

                        if (ubRouting::checkGet('deletefilterid')) {
                            $filterDeletionResult = $clapTrapMgr->deleteFilter(ubRouting::get('deletefilterid'));
                            if (empty($filterDeletionResult)) {
                                ubRouting::nav($clapTrapMgr->urlSending($clapTrapMgr::ROUTE_FILTERS . '=true'));
                            } else {
                                show_error($filterDeletionResult);
                            }
                        }

                        show_window(__('Available filters'), $clapTrapMgr->renderFiltersList());
                    } else {
                        show_window(__('Messages sending'), $clapTrapMgr->renderSendingForm());

                        if (ubRouting::checkGet(array('ajpreview', 'filterid', 'templateid'))) {
                            $clapTrapMgr->ajaxPreviewReply(ubRouting::get('filterid'), ubRouting::get('templateid'));
                        }

                        if (ubRouting::checkPost(array('sendingtemplateid', 'sendingfilterid'))) {
                            $clapTrapMgr->filtersPreprocessing(ubRouting::post('sendingfilterid'), ubRouting::post('sendingtemplateid'));
                        }
                }
            }
            } else {
                show_error(__('SendDog').' - '. __('disabled'));
            }
        }

        if (ubRouting::checkGet($clapTrapMgr::ROUTE_USERS)) {
            show_window(__('Users'), $clapTrapMgr->renderAuthData());
        }

        if (ubRouting::checkGet($clapTrapMgr::ROUTE_CONFIG)) {
            if (ubRouting::checkPost($clapTrapMgr::PROUTE_HOOK_URL)) {
                $installResult = $clapTrapMgr->installHook(ubRouting::post($clapTrapMgr::PROUTE_HOOK_URL));
                if (!empty($installResult)) {
                    show_window(__('Hook installation result'), $installResult);
                }
            }

            show_window(__('Actual bot hook state'), $clapTrapMgr->renderHookInfo($clapTrapMgr->getActualHookInfo()));
            show_window(__('Install hook'), $clapTrapMgr->renderInstallHookForm());
        }

        zb_BillingStats();
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
