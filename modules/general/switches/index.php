<?php

if (cfr('SWITCHES')) {
    $altCfg = $ubillingConfig->getAlter();

    //icmp ping handling
    if (ubRouting::checkGet('backgroundicmpping')) {
        zb_SwitchBackgroundIcmpPing(ubRouting::get('backgroundicmpping'));
    }

    //switch by IP detecting
    if (ubRouting::checkGet('gotoswitchbyip')) {
        $detectSwitchId = zb_SwitchGetIdbyIP(ubRouting::get('gotoswitchbyip'));
        if ($detectSwitchId) {
            ubRouting::nav('?module=switches&edit=' . $detectSwitchId);
        } else {
            show_error(__('Strange exeption') . ': NO_SUCH_IP');
        }
    }

    //new switch creation
    if (ubRouting::checkPost('newswitchmodel')) {
        if (cfr('SWITCHESEDIT')) {
            $modelid = ubRouting::post('newswitchmodel');
            $ip = ubRouting::post('newip');
            $desc = ubRouting::post('newdesc');
            $location = ubRouting::post('newlocation');
            $snmp = ubRouting::post('newsnmp');
            $swid = ($altCfg['SWITCHES_EXTENDED']) ? ubRouting::post('newswid') : '';
            $geo = ubRouting::post('newgeo');
            $parentid = ubRouting::post('newparentid');
            $snmpwrite = ubRouting::post('newsnmpwrite');
            $switchgroup = (ubRouting::checkPost('newswgroup')) ? ubRouting::post('newswgroup') : '';
            ub_SwitchAdd($modelid, $ip, $desc, $location, $snmp, $swid, $geo, $parentid, $snmpwrite, $switchgroup);
            ubRouting::nav('?module=switches');
        } else {
            show_window(__('Error'), __('Access denied'));
        }
    }

    //existing switch deletion
    if (ubRouting::checkGet('switchdelete')) {
        $switchToDelete = ubRouting::get('switchdelete', 'int');
        if (cfr('SWITCHESEDIT')) {
            //this switch is parent for some other switches
            if (ub_SwitchIsParent($switchToDelete)) {
                if (ubRouting::checkGet('forcedel')) {
                    //forced parent switch deletion, childs flush
                    ub_SwitchFlushChilds($switchToDelete);
                    //delete the switch itself
                    ub_SwitchDelete($switchToDelete);
                    ubRouting::nav('?module=switches');
                } else {
                    show_warning(__('This switch is the parent for other switches'));
                }
            } else {
                //just delete switch
                ub_SwitchDelete($switchToDelete);
                ubRouting::nav('?module=switches');
            }
        } else {
            show_window(__('Error'), __('Access denied'));
        }
    }


    if (!ubRouting::checkGet('edit')) {
        $swlinks = '';
        if (cfr('SWITCHESEDIT')) {
            $swlinks .= wf_modalAuto(wf_img('skins/add_icon.png') . ' ' . __('Add switch'), __('Add switch'), web_SwitchFormAdd(), 'ubButton');
        }

        if (cfr('SWITCHM')) {
            $swlinks .= wf_Link('?module=switchmodels', wf_img('skins/switch_models.png') . ' ' . __('Equipment models'), false, 'ubButton');
        }

        $swlinks .= wf_Link('?module=switches&forcereping=true', wf_img('skins/refresh.gif') . ' ' . __('Force ping'), false, 'ubButton');

        if (cfr('SWITCHES')) {
            $toolsLinks = '';

            $toolsLinks .= wf_Link('?module=switches&timemachine=true', wf_img('skins/time_machine.png') . ' ' . __('Time machine'), false, 'ubButton');
            if (cfr('SWITCHESEDIT')) {
                $toolsLinks .= wf_Link('?module=switchintegrity', wf_img('skins/integrity.png') . ' ' . __('Integrity check'), false, 'ubButton');
            }

            if (cfr('SWITCHESEDIT')) {
                $toolsLinks .= wf_Link('?module=switchscan', web_icon_search() . ' ' . __('Scan for unknown devices'), false, 'ubButton');
            }

            if (cfr('SWITCHES')) {
                $toolsLinks .= wf_Link('?module=saikopasu', wf_img('skins/icon_passport.gif') . ' ' . __('Psycho-Pass'), false, 'ubButton');
            }

            if ($ubillingConfig->getAlterParam('SWITCH_GROUPS_ENABLED')) {
                if (cfr('SWITCHGROUPS')) {
                    $toolsLinks .= wf_Link('?module=switchgroups', wf_img('skins/switch_models.png') . ' ' . __('Switch groups'), false, 'ubButton');
                }
            }

            if ($altCfg['SWITCHES_EXTENDED']) {
                if (cfr('SWITCHID')) {
                    $toolsLinks .= wf_Link('?module=switchid', wf_img('skins/swid.png') . ' ' . __('Switch ID'), false, 'ubButton');
                }
            }

            //render if any of tool accessible
            if (!empty($toolsLinks)) {
                $swlinks .= wf_modalAuto(web_icon_extended() . ' ' . __('Tools'), __('Tools'), $toolsLinks, 'ubButton');
            }
        }

        if ($altCfg['SWYMAP_ENABLED']) {
            $swlinks .= wf_Link('?module=switchmap', wf_img('skins/ymaps/network.png') . ' ' . __('Switches map'), false, 'ubButton');
        }

        if ($altCfg['SWITCH_AUTOCONFIG']) {
            if (cfr(SwitchLogin::MODULE)) {
                $swlinks .= wf_Link(SwitchLogin::MODULE_URL, wf_img('skins/sw_login.png') . ' ' . __('Switch login'), false, 'ubButton');
            }
        }

        //parental switch deletion alternate controls
        if (ubRouting::checkGet('switchdelete')) {
            $swlinks = '';
            $swlinks .= wf_Link('?module=switches&edit=' . ubRouting::get('switchdelete'), web_edit_icon() . ' ' . __('Edit'), false, 'ubButton') . ' ';
            $swlinks .= wf_JSAlertStyled('?module=switches&switchdelete=' . ubRouting::get('switchdelete') . '&forcedel=true', web_delete_icon() . ' ' . __('Force deletion'), __('Removing this may lead to irreparable results'), 'ubButton');
        }

        show_window('', $swlinks);

        if (!ubRouting::get('timemachine')) {
            if (!ubRouting::checkget('switchdelete')) {
                if (ubRouting::checkGet('forcereping')) {
                    zb_SwitchesForcePing();
                }

                //display switches list
                if (ubRouting::checkGet('ajaxlist')) {
                    die(zb_SwitchesRenderAjaxList());
                }

                //rendering of existing switches list
                show_window(__('Available switches'), web_SwitchesRenderList());
            }
        } else {
            //show dead switch time machine
            if (!ubRouting::checkGet('snapshot')) {
                //cleanup subroutine
                if (ubRouting::checkGet('flushalldead')) {
                    if (cfr('SWITCHESEDIT')) {
                        ub_SwitchesTimeMachineCleanup();
                    } else {
                        log_register('SWITCH TIMEMACHINE FLUSH FAIL ACCESS VIOLATION');
                    }
                    ubRouting::nav('?module=switches&timemachine=true');
                }

                //calendar view time machine
                if (!ubRouting::checkPost('switchdeadlogsearch')) {
                    $deadTimeMachine = ub_JGetSwitchDeadLog();
                    $timeMachine = wf_FullCalendar($deadTimeMachine);
                } else {
                    //search processing
                    $timeMachine = ub_SwitchesTimeMachineSearch(ubRouting::post('switchdeadlogsearch'));
                }
                $timeMachineCleanupControl = '';
                if (cfr('SWITCHESEDIT')) {
                    $timeMachineCleanupControl = wf_JSAlert('?module=switches&timemachine=true&flushalldead=true', wf_img('skins/icon_cleanup.png', __('Cleanup')), __('Are you serious'));
                }

                //here some searchform

                $timeMachineSearchForm = web_SwitchTimeMachineSearchForm() . wf_tag('br');
                if (ubRouting::checkGet('deadtop')) {
                    $tmControls = wf_BackLink('?module=switches', __('Back')) . ' ' . wf_Link('?module=switches&timemachine=true', wf_img('skins/time_machine.png') . ' ' . __('Time machine'), false, 'ubButton');
                    show_window('', $tmControls);
                    show_window(__('Dead switches top') . ' ' . curmonth(), web_DeadSwitchesTop());
                } else {
                    $tmControls = wf_BackLink('?module=switches', __('Back')) . ' ' . wf_Link('?module=switches&timemachine=true&deadtop=true', wf_img('skins/skull.png') . ' ' . __('Dead switches top'), false, 'ubButton');
                    show_window('', $tmControls);
                    show_window(__('Dead switches time machine') . ' ' . $timeMachineCleanupControl, $timeMachineSearchForm . $timeMachine);
                }
            } else {
                //showing dead switches snapshot
                ub_SwitchesTimeMachineShowSnapshot(ubRouting::get('snapshot'));
            }
        }
    } else {
        //switch edit form
        $switchid = ubRouting::get('edit', 'int');
        $switchdata = zb_SwitchGetData($switchid);
        if (!empty($switchdata)) {

            //if someone edits switch 
            if (ubRouting::checkPost('editmodel')) {
                if (cfr('SWITCHESEDIT')) {
                    //saving switch data
                    ub_SwitchSave($switchid);
                    ubRouting::nav('?module=switches&edit=' . $switchid);
                } else {
                    show_error(__('Access denied'));
                }
            }


            //render switch edit form (aka switch profile)
            show_window(__('Edit switch'), web_SwitchEditForm($switchid));
            
            //minimap container
            if ($altCfg['SWYMAP_ENABLED']) {
                if ((!empty($switchdata['geo'])) and (!ubRouting::checkPost('editmodel'))) {
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
        } else {
            show_error(__('Strange exeption') . ': SWITCHID_NOT_EXISTS');
        }

        show_window('', wf_BackLink('?module=switches', 'Back', true, 'ubButton'));
    }
} else {
    show_error(__('Access denied'));
}
