<?php
if (cfr('SWITCHGROUPS')) {
    if ($ubillingConfig->getAlterParam('SWITCH_GROUPS_ENABLED')) {
        $switchGroups = new SwitchGroups();

        if (wf_CheckGet(array('ajax'))) {
            $swGroupsData = $switchGroups->getSwitchGroupsData('', true);
            $switchGroups->renderJSON($swGroupsData);
        }

        if (wf_CheckPost(array('swgroupcreate'))) {
            if (wf_CheckPost(array('swgroupname'))) {
                $newSwitchGroupName = $_POST['swgroupname'];
                $foundId = $switchGroups->checkSwitchGroupNameExists($newSwitchGroupName);

                if (empty($foundId)) {
                    $switchGroups->addSwitchGroup($newSwitchGroupName, $_POST['swgroupdescr']);
                    die();
                } else {
                    $errormes = $switchGroups->getUbMsgHelperInstance()->getStyledMessage(__('Switch group with such name already exists with ID: ') . $foundId,
                                                                                          'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                    die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                }
            }

            die(wf_modalAutoForm(__('Add switch group'), $switchGroups->renderAddForm($_POST['modalWindowId']), $_POST['modalWindowId'], $_POST['modalWindowBodyId'], true));
        }

        if (wf_CheckPost(array('swgroupid'))) {
            $swGroupId = $_POST['swgroupid'];

            if (wf_CheckPost(array('swgroupedit'))) {
                if (wf_CheckPost(array('swgroupname'))) {
                    $newSwitchGroupName = $_POST['swgroupname'];
                    $foundId = $switchGroups->checkSwitchGroupNameExists($newSwitchGroupName, $swGroupId);

                    if (empty($foundId)) {
                        $switchGroups->editSwitchGroup($swGroupId, $newSwitchGroupName, $_POST['swgroupdescr']);
                        die();
                    } else {
                        $errormes = $switchGroups->getUbMsgHelperInstance()->getStyledMessage(__('Switch group with such name already exists with ID: ') . $foundId,
                                                                                              'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                        die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                    }
                }

                die(wf_modalAutoForm(__('Edit switch group'), $switchGroups->renderEditForm($swGroupId, $_POST['modalWindowId']), $_POST['modalWindowId'], $_POST['modalWindowBodyId'], true));
            }

            if (wf_CheckPost(array('showswingroup'))) {
                die(wf_modalAutoForm('', $switchGroups->renderSwitchesInGroupTable($swGroupId), $_POST['modalWindowId'], $_POST['modalWindowBodyId'], true));
            }

            if (wf_CheckPost(array('delSWGroup'))) {
                if ( !$switchGroups->checkSwitchGroupProtected($swGroupId) ) {
                    $switchGroups->deleteSwitchGroup($swGroupId);
                    die();
                } else {
                    $errormes = $switchGroups->getUbMsgHelperInstance()->getStyledMessage(__('Can not remove switch group which has existing relations on users or other DB entities'),
                                                                                          'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"' );
                    die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                }
            }
        }

        $lnkId = wf_InputId();
        $addServiceJS = wf_tag('script', false, '', 'type="text/javascript"');
        $addServiceJS.= wf_JSAjaxModalOpener($switchGroups::URL_ME, array('swgroupcreate' => 'true'), $lnkId, false, 'POST');
        $addServiceJS.= wf_tag('script', true);

        show_window(__('Switch groups'), wf_Link('#', web_add_icon() . ' ' . __('Add switch group'), false, 'ubButton', 'id="' . $lnkId . '"') .
                    wf_nbsp(2) . wf_BackLink('?module=switches') . wf_delimiter() . $addServiceJS . $switchGroups->renderJQDT());

    }
} else {
    show_error(__('Access denied'));
}
?>