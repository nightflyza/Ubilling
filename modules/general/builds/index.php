<?php

if (cfr('BUILDS')) {
    //listing streets with builds
    if (!ubRouting::checkGet('action')) {
        if (ubRouting::checkGet('ajax')) {
            renderBuildsEditJSON();
        }

        show_window(__('Builds editor'), web_StreetListerBuildsEdit());
    } else {
        if (ubRouting::checkGet('streetid')) {
            $streetid = ubRouting::get('streetid', 'int');
            if (ubRouting::get('action') == 'edit') {
                //new build creation
                if (ubRouting::checkPost('newbuildnum')) {
                    $FoundBuildID = checkBuildOnStreetExists(ubRouting::post('newbuildnum'), $streetid);
                    if (empty($FoundBuildID)) {
                        zb_AddressCreateBuild($streetid, trim(ubRouting::post('newbuildnum')));
                        die();
                    } else {
                        $messages = new UbillingMessageHelper();
                        $errormes = $messages->getStyledMessage(__('Build with such number already exists on this street with ID: ') . $FoundBuildID, 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                        log_register('BUILD CREATE FAILED STREETID [' . $streetid . '] NUM `' . trim(ubRouting::post('newbuildnum')) . '` EXISTS');
                        die(wf_modalAutoForm(__('Error'), $errormes, ubRouting::post('errfrmid'), '', true));
                    }
                }

                if (ubRouting::checkGet('ajax')) {
                    renderBuildsListerJSON($streetid);
                }

                $streetname = zb_AddressGetStreetData($streetid);
                if (!empty($streetname)) {
                    $streetname = $streetname['streetname'];
                }

                show_window(__('Available buildings on street') . ' ' . $streetname, web_BuildLister($streetid));
            }

            //build deletion handler
            if (ubRouting::get('action') == 'delete') {
                if (!zb_AddressBuildProtected(ubRouting::get('buildid', 'int'))) {
                    zb_AddressDeleteBuild(ubRouting::get('buildid', 'int'));
                    die();
                } else {
                    $messages = new UbillingMessageHelper();
                    $errormes = $messages->getStyledMessage(__('You can not delete a building if there are users of the apartment'), 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                    log_register('BUILD DELETE FAILED PROTECTED [' . ubRouting::get('buildid', 'int') . ']');
                    die(wf_modalAutoForm(__('Error'), $errormes, ubRouting::get('errfrmid'), '', true));
                }
            }

            if (ubRouting::get('action') == 'editbuild') {
                $buildid = ubRouting::get('buildid', 'int');
                $streetid = ubRouting::get('streetid', 'int');

                if (ubRouting::checkGet('ajax')) {
                    renderBuildsListerJSON($streetid, $buildid);
                }

                //build edit subroutine
                if (ubRouting::checkPost('editbuildnum')) {
                    $FoundBuildID = checkBuildOnStreetExists(ubRouting::post('editbuildnum'), $streetid, $buildid);

                    if (empty($FoundBuildID)) {
                        simple_update_field('build', 'buildnum', trim(ubRouting::post('editbuildnum')), "WHERE `id`='" . $buildid . "'");
                        zb_AddressChangeBuildGeo($buildid, ubRouting::post('editbuildgeo'));
                        log_register("BUILD CHANGE [" . $buildid . "] NUM `" . trim(ubRouting::post('editbuildnum')) . "`");
                        die();
                    } else {
                        $messages = new UbillingMessageHelper();
                        $errormes = $messages->getStyledMessage(__('Build with such number already exists on this street with ID: ') . $FoundBuildID, 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                        log_register('BUILD CHANGE FAILED [' . $buildid . '] NUM `' . trim(ubRouting::post('editbuildnum')) . '` EXISTS');
                        die(wf_modalAutoForm(__('Error'), $errormes, ubRouting::post('errfrmid'), '', true));
                    }
                }

                //construct edit form
                if (ubRouting::checkGet('frommaps')) {
                    $streetname = zb_AddressGetStreetData($streetid);
                    if (!empty($streetname)) {
                        $streetname = $streetname['streetname'];
                    }

                    show_window(__('Available buildings on street') . ' ' . $streetname, web_BuildLister($streetid, $buildid));
                } else {
                    die(wf_modalAutoForm(__('Edit') . ' ' . __('Build'), web_BuildEditForm($buildid, $streetid, ubRouting::get('ModalWID')), ubRouting::get('ModalWID'), ubRouting::get('ModalWBID'), true));
                }
            }
        }
    }
} else {
    show_error(__('Access denied'));
}

