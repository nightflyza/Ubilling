<?php

$altCfg = $ubillingConfig->getAlter();

if (@$altCfg['LDAPMGR_ENABLED']) {
    if (cfr('LDAPMGR')) {
        $ldapMgr = new UbillingLDAPManager();

//new user creation 
        if (wf_CheckPost(array('newldapuserlogin', 'newldapuserpassword'))) {
            $newUserGroups = $ldapMgr->catchNewUserGroups();
            $ldapMgr->createUser($_POST['newldapuserlogin'], $_POST['newldapuserpassword'], $newUserGroups);
            rcms_redirect($ldapMgr::URL_ME);
        }
//user deletion
        if (wf_CheckGet(array('deleteuserid'))) {
            $deletionResult = $ldapMgr->deleteUser($_GET['deleteuserid']);
            if (empty($deletionResult)) {
                rcms_redirect($ldapMgr::URL_ME);
            } else {
                show_error($deletionResult);
            }
        }

//user password editing
        if (wf_CheckPost(array('passchid', 'passchpass'))) {
            $passChResult = $ldapMgr->changeUserPassword($_POST['passchid'], $_POST['passchpass']);
            if (empty($passChResult)) {
                rcms_redirect($ldapMgr::URL_ME);
            } else {
                show_error($passChResult);
            }
        }
//user groups editing
        if (wf_CheckPost(array('chusergroupsuserid'))) {
            $changeUserGroups = $ldapMgr->catchNewUserGroups();
            $ldapMgr->changeGroups($_POST['chusergroupsuserid'], $changeUserGroups);
            rcms_redirect($ldapMgr::URL_ME);
        }
//render some interface and controls
        show_window('', $ldapMgr->panel());
        if (!wf_CheckGet(array('groups'))) {
            show_window(__('Users'), $ldapMgr->renderUserList());
            //some groups management here
        } else {
            //new group creation
            if (wf_CheckPost(array('newldapgroupname'))) {
                $ldapMgr->createGroup($_POST['newldapgroupname']);
                rcms_redirect($ldapMgr::URL_ME . '&groups=true');
            }

            //deleting some existing group
            if (wf_CheckGet(array('deletegroupid'))) {
                $groupDeletionResult = $ldapMgr->deleteGroup($_GET['deletegroupid']);
                if (empty($groupDeletionResult)) {
                    rcms_redirect($ldapMgr::URL_ME . '&groups=true');
                } else {
                    show_error($groupDeletionResult);
                }
            }

            show_window(__('Groups'), $ldapMgr->renderGroupsList());
        }
        zb_BillingStats();
    } else {
        show_error(__('Access denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
