<?php

if (cfr('NAS')) {
    $altCfg = $ubillingConfig->getAlter();

    //NAS deletion
    if (ubRouting::checkGet('delete', false)) {
        $deletenas = ubRouting::get('delete', 'int');
        zb_NasDelete($deletenas);
        zb_NasConfigSave();
        if (@$altCfg['MULTIGEN_ENABLED']) {
            $multigen = new MultiGen();
            $multigen->deleteAllNasConfiguration($deletenas);
        }
        ubRouting::nav('?module=nas');
    }

    //NAS creation
    if (ubRouting::checkPost('newnasip')) {
        $newnasip = ubRouting::post('newnasip');
        $newnetid = ubRouting::post('networkselect');
        $newnasname = ubRouting::post('newnasname');
        $newnastype = ubRouting::post('newnastype');
        $newbandw = ubRouting::post('newbandw');
        if ((!empty($newnasip)) AND ( !empty($newnasname))) {
            zb_NasAdd($newnetid, $newnasip, $newnasname, $newnastype, $newbandw);
            zb_NasConfigSave();
            ubRouting::nav('?module=nas');
        }
    }


    if (!ubRouting::checkGet('edit', false)) {
        $radiusControls = '';
        if ($altCfg['MULTIGEN_ENABLED']) {
            $multigenRadiusClientData = web_MultigenListClients();
            $radiusControls .= ' ' . wf_modal(web_icon_extended(__('Multigen NAS parameters')), __('Multigen NAS parameters'), $multigenRadiusClientData, '', '600', '300');
        }

        if ($altCfg['NASMON_ENABLED']) {
            $radiusControls .= ' ' . wf_Link('?module=report_nasmon&callback=nas', wf_img_sized('skins/icon_health.png', __('NAS servers state'), '16', '16'));
        }
        show_window(__('Network Access Servers') . ' ' . $radiusControls, web_NasList());
        show_window(__('Add new'), web_NasAddForm());
        //vlangen patch start
        if ($altCfg['VLANGEN_SUPPORT']) {
            $terminator = new VlanTerminator;
            if (ubRouting::checkGet('DeleteTerminator', false)) {
                $TermID = ubRouting::get('DeleteTerminator');
                $terminator->delete($TermID);
                ubRouting::nav(VlanTerminator::MODULE_URL);
            }

            if (!ubRouting::checkGet('EditTerminator', false)) {
                if (ubRouting::checkPost('AddTerminator', false)) {
                    $terminator_req = array('IP', 'Username', 'Password');
                    if (wf_CheckPost($terminator_req)) {
                        $terminator->add(ubRouting::post('NetworkSelected'), ubRouting::post('VlanPoolSelected'), ubRouting::post('IP'), ubRouting::post('Type'), ubRouting::post('Username'), ubRouting::post('Password'), ubRouting::post('RemoteID'), ubRouting::post('Interface'), ubRouting::post('Relay'));
                        ubRouting::nav(VlanTerminator::MODULE_URL);
                    } else {
                        show_window(__('Error'), __('No all of required fields is filled'));
                    }
                }
                $terminator->RenderTerminators();
                $terminator->AddForm();
            } else {
                if (ubRouting::checkGet('EditTerminator', false)) {
                    $term_id = ubRouting::get('EditTerminator');
                    if (ubRouting::checkPost('TerminatorEdit', false)) {
                        $terminator_req = array('IP', 'Username', 'Password');
                        if (wf_CheckPost($terminator_req)) {
                            $terminator->edit(ubRouting::post('NetworkSelected'), ubRouting::post('VlanPoolSelected'), ubRouting::post('IP'), ubRouting::post('Type'), ubRouting::post('Username'), ubRouting::post('Password'), ubRouting::post('RemoteID'), ubRouting::post('Interface'), ubRouting::post('Relay'), $term_id);
                            ubRouting::nav(VlanTerminator::MODULE_URL);
                        } else {
                            show_window(__('Error'), __('No all of required fields is filled'));
                        }
                    }
                    $terminator->EditForm($term_id);
                }
            }
        }
        //vlangen patch end
    } else {
        //NAS id to edit
        $nasid = ubRouting::get('edit', 'int');
        //updating NAS parameters on form receive
        if (ubRouting::checkPost('editnastype')) {
            $nastype = ubRouting::post('editnastype');
            $nasip = ubRouting::post('editnasip');
            $nasname = ubRouting::post('editnasname');
            $nasbwdurl = ubRouting::post('editnasbwdurl');
            $netid = ubRouting::post('networkselect');

            zb_NasUpdateParams($nasid, $nastype, $nasip, $nasname, $nasbwdurl, $netid);
            zb_NasConfigSave();
            ubRouting::nav('?module=nas&edit=' . $nasid);
        }
        //rendering editing form
        show_window(__('Edit') . ' NAS', web_NasEditForm($nasid));
    }
} else {
    show_error(__('You cant control this module'));
}

