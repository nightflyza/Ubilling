<?php

if (cfr('SMSZILLA')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['SMSZILLA_ENABLED']) {
        //may be to slow :(
        set_time_limit(0);


        $smszilla = new SMSZilla();

//rendering module control panel
        show_window('', $smszilla->panel());

//templates management
        if (wf_CheckGet(array('templates'))) {
//creating new template
            if (wf_CheckPost(array('newtemplatename', 'newtemplatetext'))) {
                $smszilla->createTemplate($_POST['newtemplatename'], $_POST['newtemplatetext']);
                rcms_redirect($smszilla::URL_ME . '&templates=true');
            }

//deleting existing template
            if (wf_CheckGet(array('deletetemplate'))) {
                $templateDeletionResult = $smszilla->deleteTemplate($_GET['deletetemplate']);
                if (empty($templateDeletionResult)) {
                    rcms_redirect($smszilla::URL_ME . '&templates=true');
                } else {
                    show_error($templateDeletionResult);
                }
            }

//editing existing template
            if (wf_CheckGet(array('edittemplate'))) {
                //save changes to database
                if (wf_CheckPost(array('edittemplateid', 'edittemplatename', 'edittemplatetext'))) {
                    $templateEditingResult = $smszilla->saveTemplate($_POST['edittemplateid'], $_POST['edittemplatename'], $_POST['edittemplatetext']);
                    if (empty($templateEditingResult)) {
                        rcms_redirect($smszilla::URL_ME . '&templates=true&edittemplate=' . $_POST['edittemplateid']);
                    } else {
                        show_error($templateEditingResult);
                    }
                }
                show_window(__('Edit template'), $smszilla->renderTemplateEditForm($_GET['edittemplate']));
            } else {
                show_window(__('Available templates'), $smszilla->renderTemplatesList());
            }
        }

//filters management
        if (wf_CheckGet(array('filters'))) {
            //rendering ajax inputs reply
            if (wf_CheckGet(array('newfilterdirection'))) {
                $smszilla->catchAjRequest();
            }
            //creatin new filter
            if (wf_CheckPost(array('newfilterdirection'))) {
                $creationResult = $smszilla->createFilter();
                if (empty($creationResult)) {
                    rcms_redirect($smszilla::URL_ME . '&filters=true');
                } else {
                    show_error($creationResult);
                }
            }
            //filter deletion
            if (wf_CheckGet(array('deletefilterid'))) {
                $filterDeletionResult = $smszilla->deleteFilter($_GET['deletefilterid']);
                if (empty($filterDeletionResult)) {
                    rcms_redirect($smszilla::URL_ME . '&filters=true');
                } else {
                    show_error($filterDeletionResult);
                }
            }
            show_window(__('New filter creation'), $smszilla->renderFilterCreateForm());
            show_window(__('Available filters'), $smszilla->renderFiltersList());
        }

//sending forms, etc
        if (wf_CheckGet(array('sending'))) {
            show_window(__('SMS sending'), $smszilla->renderSendingForm());

            //preview ajax reply
            if (wf_CheckGet(array('ajpreview', 'filterid', 'templateid'))) {
                $smszilla->ajaxPreviewReply($_GET['filterid'], $_GET['templateid']);
            }

            //processing of filters and performing sending
            if (wf_CheckPost(array('sendingtemplateid', 'sendingfilterid'))) {
                $smszilla->filtersPreprocessing($_POST['sendingfilterid'], $_POST['sendingtemplateid']);
            }
        }
    } else {
        show_window(__('Error'), __('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>