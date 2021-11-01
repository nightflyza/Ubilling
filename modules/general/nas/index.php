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


    // Show available NASes
    $allnas = zb_NasGetAllData();

    // construct needed editor
    $titles = array(
        'ID',
        'Network',
        'IP',
        'NAS name',
        'NAS type',
        'Graphs URL'
    );
    $keys = array('id',
        'netid',
        'nasip',
        'nasname',
        'nastype',
        'bandw'
    );

    if (!ubRouting::checkGet('edit', false)) {
        $radiusControls = '';
        if ($altCfg['FREERADIUS_ENABLED']) {
            $freeRadiusClientsData = web_FreeRadiusListClients();
            $radiusControls .= wf_modal(web_icon_extended(__('FreeRADIUS NAS parameters')), __('FreeRADIUS NAS parameters'), $freeRadiusClientsData, '', '600', '300');
        }

        if ($altCfg['JUNGEN_ENABLED']) {
            $juniperRadiusClientData = web_JuniperListClients();
            $radiusControls .= ' ' . wf_modal(web_icon_extended(__('Juniper NAS parameters')), __('Juniper NAS parameters'), $juniperRadiusClientData, '', '600', '300');
        }

        if ($altCfg['MULTIGEN_ENABLED']) {
            $multigenRadiusClientData = web_MultigenListClients();
            $radiusControls .= ' ' . wf_modal(web_icon_extended(__('Multigen NAS parameters')), __('Multigen NAS parameters'), $multigenRadiusClientData, '', '600', '300');
        }

        if ($altCfg['NASMON_ENABLED']) {
            $radiusControls .= ' ' . wf_Link('?module=report_nasmon&callback=nas', wf_img_sized('skins/icon_stats.gif', __('NAS servers state'), '16', '16'));
        }
        show_window(__('Network Access Servers') . ' ' . $radiusControls, web_GridEditorNas($titles, $keys, $allnas, 'nas'));
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
        //show editing form
        $nasid = ubRouting::get('edit', 'int');

        //if someone editing nas
        if (ubRouting::checkPost('editnastype')) {
            $targetnas = "WHERE `id` = '" . $nasid . "'";

            $nastype = ubRouting::post('editnastype', 'mres');
            $nasip = ubRouting::post('editnasip', 'mres');
            $nasname = ubRouting::post('editnasname', 'mres');
            $nasbwdurl = trim(ubRouting::post('editnasbwdurl', 'mres'));
            $netid = ubRouting::post('networkselect', 'int');

            simple_update_field('nas', 'nastype', $nastype, $targetnas);
            simple_update_field('nas', 'nasip', $nasip, $targetnas);
            simple_update_field('nas', 'nasname', $nasname, $targetnas);
            simple_update_field('nas', 'bandw', $nasbwdurl, $targetnas);
            simple_update_field('nas', 'netid', $netid, $targetnas);
            zb_NasConfigSave();
            log_register('NAS EDIT [' . $nasid . '] `' . $nasip . '`');
            ubRouting::nav('?module=nas&edit=' . $nasid);
        }


        $nasdata = zb_NasGetData($nasid);
        $currentnetid = $nasdata['netid'];
        $currentnasip = $nasdata['nasip'];
        $currentnasname = $nasdata['nasname'];
        $currentnastype = $nasdata['nastype'];
        $currentbwdurl = $nasdata['bandw'];
        $nastypes = array(
            'local' => 'Local NAS',
            'rscriptd' => 'rscriptd',
            'mikrotik' => 'MikroTik',
            'radius' => 'Radius'
        );


        //rendering editing form
        $editinputs = multinet_network_selector($currentnetid) . "<br>";
        $editinputs .= wf_Selector('editnastype', $nastypes, 'NAS type', $currentnastype, true);
        $editinputs .= wf_TextInput('editnasip', 'IP', $currentnasip, true, '15', 'ip');
        $editinputs .= wf_TextInput('editnasname', 'NAS name', $currentnasname, true, '15');
        $editinputs .= wf_TextInput('editnasbwdurl', 'Graphs URL', $currentbwdurl, true, '25');
        $editinputs .= wf_Submit('Save');
        $editform = wf_Form('', 'POST', $editinputs, 'glamour');
        show_window(__('Edit') . ' NAS', $editform);
        show_window('', wf_BackLink("?module=nas"));
    }
} else {
    show_error(__('You cant control this module'));
}

