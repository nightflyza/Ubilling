<?php

if (cfr('SMSZILLA')) {

    if ($ubillingConfig->getAlterParam('SMSZILLA_ENABLED')) {
        if ($ubillingConfig->getAlterParam('SENDDOG_ENABLED')) {
            //may be to slow :(
            set_time_limit(0);

            $smszilla = new SMSZilla();

//rendering module control panel
            show_window('', $smszilla->panel());

//templates management
            if (ubRouting::checkGet('templates')) {
//creating new template
                if (ubRouting::checkPost(array('newtemplatename', 'newtemplatetext'))) {
                    $smszilla->createTemplate(ubRouting::post('newtemplatename'), ubRouting::post('newtemplatetext'));
                    ubRouting::nav($smszilla::URL_ME . '&templates=true');
                }

//deleting existing template
                if (ubRouting::checkGet('deletetemplate')) {
                    $templateDeletionResult = $smszilla->deleteTemplate(ubRouting::get('deletetemplate'));
                    if (empty($templateDeletionResult)) {
                        ubRouting::nav($smszilla::URL_ME . '&templates=true');
                    } else {
                        show_error($templateDeletionResult);
                    }
                }

//editing existing template
                if (ubRouting::checkGet('edittemplate')) {
                    //save changes to database
                    if (ubRouting::checkPost(array('edittemplateid', 'edittemplatename', 'edittemplatetext'))) {
                        $templateEditingResult = $smszilla->saveTemplate(ubRouting::post('edittemplateid'), ubRouting::post('edittemplatename'), ubRouting::post('edittemplatetext'));
                        if (empty($templateEditingResult)) {
                            ubRouting::nav($smszilla::URL_ME . '&templates=true&edittemplate=' . ubRouting::post('edittemplateid'));
                        } else {
                            show_error($templateEditingResult);
                        }
                    }
                    show_window(__('Edit template'), $smszilla->renderTemplateEditForm(ubRouting::get('edittemplate')));
                } else {
                    show_window(__('Available templates'), $smszilla->renderTemplatesList());
                }
            }

//filters management
            if (ubRouting::checkGet('filters')) {
                //rendering ajax inputs reply
                if (ubRouting::checkGet('newfilterdirection')) {
                    $smszilla->catchAjRequest();
                }
                //creatin new filter
                if (ubRouting::checkPost('newfilterdirection')) {
                    $creationResult = $smszilla->createFilter();
                    if (empty($creationResult)) {
                        ubRouting::nav($smszilla::URL_ME . '&filters=true');
                    } else {
                        show_error($creationResult);
                    }
                }
                //filter deletion
                if (ubRouting::checkGet('deletefilterid')) {
                    $filterDeletionResult = $smszilla->deleteFilter(ubRouting::get('deletefilterid'));
                    if (empty($filterDeletionResult)) {
                        ubRouting::nav($smszilla::URL_ME . '&filters=true');
                    } else {
                        show_error($filterDeletionResult);
                    }
                }
                show_window(__('New filter creation'), $smszilla->renderFilterCreateForm());
                show_window(__('Available filters'), $smszilla->renderFiltersList());
            }

//sending forms, etc
            if (ubRouting::checkGet(array('sending'))) {
                show_window(__('SMS sending'), $smszilla->renderSendingForm());
                zb_BillingStats(true);

                //preview ajax reply
                if (ubRouting::checkGet(array('ajpreview', 'filterid', 'templateid'))) {
                    $smszilla->ajaxPreviewReply(ubRouting::get('filterid'), ubRouting::get('templateid'));
                }

                //processing of filters and performing sending
                if (ubRouting::checkPost(array('sendingtemplateid', 'sendingfilterid'))) {
                    $smszilla->filtersPreprocessing(ubRouting::post('sendingfilterid'), ubRouting::post('sendingtemplateid'));
                }
            }

//numbers lists management
            if (ubRouting::checkGet('numlists')) {
                //creating new numbers list
                if (ubRouting::checkPost('newnumlistname')) {
                    $numListCreationResult = $smszilla->createNumList(ubRouting::post('newnumlistname'));
                    if (empty($numListCreationResult)) {
                        ubRouting::nav($smszilla::URL_ME . '&numlists=true');
                    } else {
                        show_error($numListCreationResult);
                    }
                }

                //deleting numlist
                if (ubRouting::checkGet('deletenumlistid')) {
                    $numListDeletionResult = $smszilla->deleteNumList(ubRouting::get('deletenumlistid'));
                    if (empty($numListDeletionResult)) {
                        ubRouting::nav($smszilla::URL_ME . '&numlists=true');
                    } else {
                        show_error($numListDeletionResult);
                    }
                }

                //uploading numbers database
                if (ubRouting::checkPost(array('uploadnumlistnumbers', 'newnumslistid'))) {
                    $smszilla->catchFileUpload();
                }
                //rendering ajax reply with numbers list data
                if (ubRouting::checkGet('ajnums')) {
                    $smszilla->ajaxNumbersReply();
                }

                //editing existing numlist
                if (ubRouting::checkPost(array('editnumlistid', 'editnumlistname'))) {
                    $smszilla->saveNumList(ubRouting::post('editnumlistid'), ubRouting::post('editnumlistname'));
                    ubRouting::nav($smszilla::URL_ME . '&numlists=true&editnumlistid=' . ubRouting::post('editnumlistid'));
                }

                //deleting some single number
                if (ubRouting::checkGet('deletenumid')) {
                    $smszilla->deleteNumlistNumber(ubRouting::get('deletenumid'));
                    ubRouting::nav($smszilla::URL_ME . '&numlists=true');
                }

                //creating single number
                if (ubRouting::checkPost(array('newsinglenumlistid', 'newsinglenumlistmobile'))) {
                    $singNumCreationResult = $smszilla->createNumlistSingleNumber(ubRouting::post('newsinglenumlistid'), ubRouting::post('newsinglenumlistmobile'), ubRouting::post('newsinglenumlistnotes'));
                    if (empty($singNumCreationResult)) {
                        ubRouting::nav($smszilla::URL_ME . '&numlists=true');
                    } else {
                        show_error($singNumCreationResult);
                    }
                }

                //numlist cleanup
                if (ubRouting::checkPost('cleanupnumlistid')) {
                    $numlistCleanupResult = $smszilla->cleanupNumlist(ubRouting::post('cleanupnumlistid'));
                    if (empty($numlistCleanupResult)) {
                        ubRouting::nav($smszilla::URL_ME . '&numlists=true');
                    } else {
                        show_error($numlistCleanupResult);
                    }
                }

                if (ubRouting::checkGet('editnumlistid')) {
                    //existing numlist edit forms
                    show_window('', wf_BackLink($smszilla::URL_ME . '&numlists=true'));
                    show_window(__('Edit'), $smszilla->renderNumListEditForm(ubRouting::get('editnumlistid')));
                } else {
                    //rendering numlists
                    show_window(__('Numbers lists'), $smszilla->renderNumListsList());
                    show_window(__('Upload'), $smszilla->uploadNumListNumbersForm());
                    show_window(__('Add'), $smszilla->createNumListNumberForm());
                    show_window(__('Cleanup') . ' ' . __('from numbers assigned to users'), $smszilla->renderCleanupNumlistForm());
                    show_window(__('Available numbers database'), $smszilla->renderNumsContainer());
                }
            }

            //per-number excludes
            if (ubRouting::checkGet('excludes')) {
                //creating new excludes
                if (ubRouting::checkPost('newexcludenumber')) {
                    $smszilla->createExclude(ubRouting::post('newexcludenumber'));
                    ubRouting::nav($smszilla::URL_ME . '&excludes=true');
                }
                //deleting existing exclude
                if (ubRouting::checkGet('deleteexclnumid')) {
                    $smszilla->deleteExlude(ubRouting::get('deleteexclnumid'));
                    ubRouting::nav($smszilla::URL_ME . '&excludes=true');
                }

                //list available exluded numbers base and some forms
                show_window(__('Create'), $smszilla->renderExcludeCreateForm());
                show_window(__('Excludes'), $smszilla->renderExcludeNumsList());
            }
        } else {
            show_error(__('SMSZilla requires SendDog'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
