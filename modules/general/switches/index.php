<?php

//frontend for cron task
if (isset($_GET['cronping'])) {
    $hostid_q = "SELECT * from `ubstats` WHERE `key`='ubid'";
    $hostid = simple_query($hostid_q);
    if (!empty($hostid)) {
        $ubserial = $hostid['value'];
        //check for ubserial validity
        if ($_GET['cronping'] == $ubserial) {
            $currenttime = time();
            $deadSwitches = zb_SwitchesRepingAll();
            zb_StorageSet('SWPINGTIME', $currenttime);
            //store dead switches log data
            if (!empty($deadSwitches)) {
                zb_SwitchesDeadLog($currenttime, $deadSwitches);
            }
            die('SWITCH REPING DONE ' . date("Y-m-d H:i:s"));
        } else {
            die('WRONG SERIAL');
        }
    }
}


if (cfr('SWITCHES')) {
    $altCfg = $ubillingConfig->getAlter();
    
    //icmp ping handling
    if (wf_CheckGet(array('backgroundicmpping'))) {
        $billingConf=$ubillingConfig->getBilling();
        $command=$billingConf['SUDO'].' '.$billingConf['PING'].' -i 0.01 -c 10  '.$_GET['backgroundicmpping'];
        $icmpPingResult=shell_exec($command);
        die(wf_tag('pre').$icmpPingResult.  wf_tag('pre',true));
    }
    
    //switch by IP detecting
    if (wf_CheckGet(array('gotoswitchbyip'))) {
        $detectSwitchId=  zb_SwitchGetIdbyIP($_GET['gotoswitchbyip']);
        if ($detectSwitchId) {
            rcms_redirect('?module=switches&edit='.$detectSwitchId);
        } else {
            show_warning(__('Strange exeption').': NO_SUCH_IP');
        }
    }

//switch adding
    if (isset($_POST['newswitchmodel'])) {
        if (cfr('SWITCHESEDIT')) {
            $modelid = $_POST['newswitchmodel'];
            $ip = $_POST['newip'];
            $desc = $_POST['newdesc'];
            $location = $_POST['newlocation'];
            $snmp = $_POST['newsnmp'];
            $geo = $_POST['newgeo'];
            $parentid = $_POST['newparentid'];
            ub_SwitchAdd($modelid, $ip, $desc, $location, $snmp, $geo, $parentid);
            rcms_redirect("?module=switches");
        } else {
            show_window(__('Error'), __('Access denied'));
        }
    }
//switch deletion
    if (isset($_GET['switchdelete'])) {
        if (!empty($_GET['switchdelete'])) {
            if (cfr('SWITCHESEDIT')) {
                if (ub_SwitchIsParent($_GET['switchdelete'])) {
                    if (wf_CheckGet(array('forcedel'))) {
                        //forced parent switch deletion, childs flush
                          ub_SwitchFlushChilds($_GET['switchdelete']);
                          ub_SwitchDelete($_GET['switchdelete']);
                          rcms_redirect("?module=switches");
                    } else {
                        show_warning(__('This switch is the parent for other switches'));
                    }
                    
                } else {
                    ub_SwitchDelete($_GET['switchdelete']);
                    rcms_redirect("?module=switches");
                }
            } else {
                show_window(__('Error'), __('Access denied'));
            }
        }
    }


    if (!isset($_GET['edit'])) {
        $swlinks = '';
        if (cfr('SWITCHESEDIT')) {
            $swlinks.= wf_modalAuto(wf_img('skins/add_icon.png') . ' ' . __('Add switch'), __('Add switch'), web_SwitchFormAdd(), 'ubButton');
        }

        if (cfr('SWITCHM')) {
            $swlinks.=wf_Link('?module=switchmodels', wf_img('skins/switch_models.png') . ' ' . __('Available switch models'), false, 'ubButton');
        }

        $swlinks.=wf_Link('?module=switches&forcereping=true', wf_img('skins/refresh.gif') . ' ' . __('Force ping'), false, 'ubButton');
        $swlinks.=wf_Link('?module=switches&timemachine=true', wf_img('skins/time_machine.png') . ' ' . __('Time machine'), false, 'ubButton');


        if ($altCfg['SWYMAP_ENABLED']) {
            $swlinks.=wf_Link('?module=switchmap', wf_img('skins/ymaps/network.png') . ' ' . __('Switches map'), false, 'ubButton');
        }

        if($altCfg['SWITCH_AUTOCONFIG']) {
            if(cfr(SwitchLogin::MODULE)) {
                $swlinks.=wf_Link(SwitchLogin::MODULE_URL, wf_img('skins/sw_login.png') . ' ' . __('Switch login'), false, 'ubButton');
            }
        }
        //parental switch deletion alternate controls
        if (isset($_GET['switchdelete'])) {
            $swlinks = '';
            $swlinks.= wf_Link('?module=switches&edit=' . $_GET['switchdelete'], web_edit_icon() . ' ' . __('Edit'), false, 'ubButton') . ' ';
            $swlinks.= wf_JSAlertStyled('?module=switches&switchdelete=' . $_GET['switchdelete'].'&forcedel=true', web_delete_icon() . ' ' . __('Force deletion'), __('Removing this may lead to irreparable results'), 'ubButton');
        }
        show_window('', $swlinks);

        if (!isset($_GET['timemachine'])) {
            if ((!isset($_GET['switchdelete']))) {
                //display switches list
                show_window(__('Available switches'), web_SwitchesShow());
            }
        } else {
            //show dead switch time machine
            if (!isset($_GET['snapshot'])) {
                //cleanup subroutine
                if (wf_CheckGet(array('flushalldead'))) {
                    ub_SwitchesTimeMachineCleanup();
                    rcms_redirect("?module=switches&timemachine=true");
                }

                //calendar view time machine
                if (!wf_CheckPost(array('switchdeadlogsearch'))) {
                    $deadTimeMachine = ub_JGetSwitchDeadLog();
                    $timeMachine = wf_FullCalendar($deadTimeMachine);
                } else {
                    //search processing
                    $timeMachine = ub_SwitchesTimeMachineSearch($_POST['switchdeadlogsearch']);
                }
                $timeMachineCleanupControl = wf_JSAlert('?module=switches&timemachine=true&flushalldead=true', wf_img('skins/icon_cleanup.png', __('Cleanup')), __('Are you serious'));
                //here some searchform
                $timeMachineSearchForm = web_SwitchTimeMachineSearchForm() . wf_tag('br');
                
                show_window(__('Dead switches time machine') . ' ' . $timeMachineCleanupControl, $timeMachineSearchForm . $timeMachine);
                show_window(__('Dead switches top'),web_DeadSwitchesTop());
                
            } else {
                //showing dead switches snapshot
                ub_SwitchesTimeMachineShowSnapshot($_GET['snapshot']);
            }
        }
    } else {
        //editing switch form
        $switchid = vf($_GET['edit'], 3);
        $switchdata = zb_SwitchGetData($switchid);


        //if someone edit switch 
        if (wf_CheckPost(array('editmodel'))) {
            if (cfr('SWITCHESEDIT')) {
                simple_update_field('switches', 'modelid', $_POST['editmodel'], "WHERE `id`='" . $switchid . "'");
                simple_update_field('switches', 'ip', $_POST['editip'], "WHERE `id`='" . $switchid . "'");
                simple_update_field('switches', 'location', $_POST['editlocation'], "WHERE `id`='" . $switchid . "'");
                simple_update_field('switches', 'desc', $_POST['editdesc'], "WHERE `id`='" . $switchid . "'");
                simple_update_field('switches', 'snmp', $_POST['editsnmp'], "WHERE `id`='" . $switchid . "'");
                simple_update_field('switches', 'geo', $_POST['editgeo'], "WHERE `id`='" . $switchid . "'");
                if ($_POST['editparentid'] != $switchid) {
                    simple_update_field('switches', 'parentid', $_POST['editparentid'], "WHERE `id`='" . $switchid . "'");
                }
                log_register('SWITCH CHANGE [' . $switchid . ']' . ' IP ' . $_POST['editip'] . " LOC `" . $_POST['editlocation'] . "`");
                rcms_redirect("?module=switches&edit=" . $switchid);
            } else {
                show_error(__('Access denied'));
            }
        }

        //render switch edit form
        show_window(__('Edit switch'), web_SwitchEditForm($switchid));
        //minimap container
        if ($altCfg['SWYMAP_ENABLED']) {
            if (!empty($switchdata['geo'])) {
                show_window(__('Mini-map'), wf_delimiter() . web_SwitchMiniMap($switchdata));
            }
        }

        //downlinks list
        web_SwitchDownlinksList($switchid);


        //additional comments engine
        if ($altCfg['ADCOMMENTS_ENABLED']) {
            $adcomments = new ADcomments('SWITCHES');
            show_window(__('Additional comments'), $adcomments->renderComments($switchid));
        }


        show_window('', wf_Link('?module=switches', 'Back', true, 'ubButton'));
    }
} else {
    show_error(__('Access denied'));
}
?>