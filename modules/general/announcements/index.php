<?php

if ($ubillingConfig->getAlterParam('ANNOUNCEMENTS')) {
    if (cfr('ZBSANN')) {
        $announcements = new Announcements();

        //getting announcements data
        if (wf_CheckGet(array('ajaxavaibleann'))) {
            $announcements->ajaxAvaibleAnnouncements();
        }

        //getting acquainted users data
        if (wf_CheckGet(array('ajaxannusers'))) {
            $announcements->ajaxAvaibAcquaintedUsers();
        }

        //intro editing here
        if (cfr('ZBSANNCONFIG') and wf_CheckPost(array('newzbsintro'))) {
            $announcements->saveIntroText($_POST['newzbsintrotext']);
            rcms_redirect($_SERVER['REQUEST_URI']);
        }

        //show announcements control panel
        show_window('', $announcements->panel());

        // show form for create
        if (wf_CheckGet(array('action'))) {
            if (cfr('ZBSANNCONFIG')) {
                // create new
                if ($_GET['action'] == 'create') {
                    $displayTitle = (wf_CheckGet(array('admiface'))) ? __('administrators announcement') : __('userstats announcement');
                    if (wf_CheckPost(array('createann'))) {
                        show_window('', $announcements->controlAnn($_POST['createann']));
                    }
                    show_window(__('Create').' '.$displayTitle, $announcements->renderForm());
                }
                // edit
                if ($_GET['action'] == 'edit') {
                    if (wf_CheckPost(array('editann'))) {
                        show_window('', $announcements->controlAnn($_POST['editann']));
                    }
                    show_window(__('Edit'), $announcements->renderForm());
                }
                // delete
                if ($_GET['action'] == 'delete') {
                    $announcements->deleteAnnounceData();
                }
            } else {
                show_error(__('Access denied'));
            }
        } elseif (wf_CheckGet(array('show_acquainted'))) {
            $displayTitle=(wf_CheckGet(array('admiface')))  ? __('Administrators') : __('Users') ;
            show_window($displayTitle.' '.__('acquainted'), $announcements->renderAcquaintedUsers());
        } else {
            $displayTitle = (wf_CheckGet(array('admiface'))) ? __('Administrators announcements') : __('Userstats announcements');
            show_window($displayTitle, $announcements->renderAvaibleAnnouncements());
        }
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>