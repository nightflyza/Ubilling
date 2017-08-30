<?php

if (cfr('ZBSANN')) {
    $altercfg = $ubillingConfig->getAlter();
    if ($altercfg['ANNOUNCEMENTS']) {
        //intro editing here
        if (wf_CheckPost(array('newzbsintro'))) {
            $zbsIntro = new ZbsIntro();
            $zbsIntro->saveIntroText($_POST['newzbsintrotext']);
            rcms_redirect('?module=zbsannouncements');
        }


        show_window('', web_AnnouncementsControls());
        //userstats announcements management
        if (!wf_CheckGet(array('admiface'))) {
            $announcements = new ZbsAnnouncements();
            //creating new one
            if (wf_CheckPost(array('newtext', 'newtype'))) {
                $announcements->create($_POST['newpublic'], $_POST['newtype'], $_POST['newtitle'], $_POST['newtext']);
                rcms_redirect('?module=zbsannouncements');
            }

            //deleting announcement
            if (wf_CheckGet(array('delete'))) {
                $announcements->delete($_GET['delete']);
                rcms_redirect('?module=zbsannouncements');
            }

            if (isset($_GET['edit'])) {
                if (wf_CheckPost(array('edittext', 'edittype'))) {
                    $announcements->save($_GET['edit']);
                    rcms_redirect('?module=zbsannouncements&edit=' . $_GET['edit']);
                }
                show_window(__('Edit'), $announcements->editForm($_GET['edit']));
            } else {
                //show announcements list and create form
                show_window(__('Userstats announcements'), $announcements->render());
                show_window('', wf_modal(web_icon_create() . ' ' . __('Create'), __('Create'), $announcements->createForm(), 'ubButton', '600', '400'));
            }
        } else {
            //administrators announcements management
            $admAnnouncements = new AdminAnnouncements();
            //creating new one
            if (wf_CheckPost(array('newtext'))) {
                $admAnnouncements->create(@$_POST['newtitle'], $_POST['newtext']);
                rcms_redirect('?module=zbsannouncements&admiface=true');
            }

            //deleting announcement
            if (wf_CheckGet(array('delete'))) {
                $admAnnouncements->delete($_GET['delete']);
                rcms_redirect('?module=zbsannouncements&admiface=true');
            }

            if (isset($_GET['edit'])) {
                if (wf_CheckPost(array('edittext', 'edittitle'))) {
                    $admAnnouncements->save($_GET['edit']);
                    rcms_redirect('?module=zbsannouncements&admiface=true&edit=' . $_GET['edit']);
                }
                show_window(__('Edit'), $admAnnouncements->editForm($_GET['edit']));
            } else {
                //show announcements list and create form
                show_window(__('Administrators announcements'), $admAnnouncements->render());
                show_window('', wf_modal(web_icon_create() . ' ' . __('Create'), __('Create'), $admAnnouncements->createForm(), 'ubButton', '600', '400'));
            }
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>