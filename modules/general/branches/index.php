<?php

if (cfr('BRANCHES')) {
    $altCfg = $ubillingConfig->getAlter();
    if ($altCfg['BRANCHES_ENABLED']) {
        $branch = new UbillingBranches();

        show_window('', $branch->panel());

        //user branches assign * management interface
        if (wf_CheckGet(array('userbranch'))) {
            $userLogin = $_GET['userbranch'];
            if ($branch->isMyUser($userLogin)) {
                $branch->catchUserBranchEditRequest();
                show_window(__('Change branch'), $branch->renderUserBranchFrom($userLogin));
            } else {
                show_error(__('Access denied'));
            }
        }

        //rendering branches users list
        if (wf_CheckGet(array('userlist'))) {
            if (wf_CheckGet(array('ajaxuserlist'))) {
                $branch->renderUserListJson();
            }
            show_window(__('Users'), $branch->renderUserList());
        }

        //rendering branches users registration interface
        if (wf_CheckGet(array('userreg'))) {
            show_window(__('Users registration'), 'TODO');
        }



        //financial report 
        if (wf_CheckGet(array('finreport'))) {
            show_window(__('Finance report'), $branch->renderFinanceReport());
        }

        //signups report here
        if (wf_CheckGet(array('sigreport'))) {
            show_window(__('Signup report'), $branch->renderSignupReport());
        }

        if (wf_CheckGet(array('settings'))) {
            //additional rights check
            if (cfr('BRANCHESCONF')) {
                //create new branch
                if (wf_CheckPost(array('newbranch', 'newbranchname'))) {
                    $branch->createBranch($_POST['newbranchname']);
                    rcms_redirect($branch::URL_ME . '&settings=true');
                }

                //branches editing
                if (wf_CheckPost(array('editbranch', 'editbranchid', 'editbranchname'))) {
                    $branch->editBranch($_POST['editbranchid'], $_POST['editbranchname']);
                    rcms_redirect($branch::URL_ME . '&settings=true');
                }

                //branches deletion
                if (wf_CheckGet(array('deletebranch'))) {
                    if ($branch->isBranchProtected($_GET['deletebranch'])) {
                        show_error(__('You know, we really would like to let you perform this action, but our conscience does not allow us to do'));
                    } else {
                        $branch->deleteBranch($_GET['deletebranch']);
                        rcms_redirect($branch::URL_ME . '&settings=true');
                    }
                }

                //branches administrators assign
                if (wf_CheckPost(array('newadminbranch', 'newadminlogin'))) {
                    $branch->adminAssignBranch($_POST['newadminbranch'], $_POST['newadminlogin']);
                    rcms_redirect($branch::URL_ME . '&settings=true');
                }

                //admin branch deassign
                if (wf_CheckGet(array('deleteadmin', 'adminbranchid'))) {
                    $branch->adminDeassignBranch($_GET['adminbranchid'], $_GET['deleteadmin']);
                    rcms_redirect($branch::URL_ME . '&settings=true');
                }

                //city branch assigns
                if (wf_CheckPost(array('newcitybranchid', 'newcityid'))) {
                    $branch->cityAssignBranch($_POST['newcitybranchid'], $_POST['newcityid']);
                    rcms_redirect($branch::URL_ME . '&settings=true');
                }

                //city branch deassign
                if (wf_CheckGet(array('deletecity', 'citybranchid'))) {
                    $branch->cityDeassignBranch($_GET['citybranchid'], $_GET['deletecity']);
                    rcms_redirect($branch::URL_ME . '&settings=true');
                }

                //tariff branch assigns
                if (wf_CheckPost(array('newtariffbranchid', 'newtariffname'))) {
                    $branch->tariffAssignBranch($_POST['newtariffbranchid'], $_POST['newtariffname']);
                    rcms_redirect($branch::URL_ME . '&settings=true');
                }

                //tariff branch deassign
                if (wf_CheckGet(array('deletetariff', 'tariffbranchid'))) {
                    $branch->tariffDeassignBranch($_GET['tariffbranchid'], $_GET['deletetariff']);
                    rcms_redirect($branch::URL_ME . '&settings=true');
                }

                show_window(__('Configuration'), $branch->renderSettingsBranches());
            }
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>