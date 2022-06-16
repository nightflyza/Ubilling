<?php

if (cfr('BUILDPASSPORT')) {
    if ($ubillingConfig->getAlterParam('BUILD_EXTENDED')) {

        if (ubRouting::checkGet(BuildPassport::ROUTE_BUILD)) {
            $passport = new BuildPassport();
            $buildId = ubRouting::get(BuildPassport::ROUTE_BUILD, 'int');
            $allBuildsAddress = zb_AddressGetBuildAllAddress();
            $buildLabel = @$allBuildsAddress[$buildId];

            //passport navigation here
            if (ubRouting::checkGet('back')) {
                $rawBack = ubRouting::get('back');
                $backUrl = '?module=' . base64_decode($rawBack);
                $backControl = wf_BackLink($backUrl);
                $editControl = '';
                if (cfr('BUILDS')) {
                    $editLabel = wf_img('skins/icon_buildpassport.png') . ' ' . __('Edit build passport');
                    $editTitle = __('Edit build passport') . ': ' . $buildLabel;
                    $editControl = wf_modalAuto($editLabel, $editTitle, $passport->renderEditForm($buildId), 'ubButton');
                }

                //some controls here
                show_window('', $backControl . ' ' . $editControl);
            }

            if (!empty($buildId)) {
                $passportData = $passport->getPassportData($buildId);
                $buildData = zb_AddressGetBuildData($buildId);

                $buildPassportRender = '';

                if (!empty($passportData)) {
                    $buildPassportRender .= $passport->renderPassportData($buildId, $buildLabel);
                    show_window(__('Build passport') . ': ' . $buildLabel, $buildPassportRender);
                } else {
                    $messages = new UbillingMessageHelper();
                    $buildPassportRender .= $messages->getStyledMessage(__('This build have no passport data'), 'warning');
                    show_window(__('Build') . ': ' . $buildLabel, $buildPassportRender);
                }

                //ajax callbacks
                if (ubRouting::checkGet('ajax')) {
                    die($buildPassportRender);
                }

                //build on map
                if ($ubillingConfig->getAlterParam('SWYMAP_ENABLED')) {
                    if (!empty($buildData['geo'])) {
                        $mapOptions = $ubillingConfig->getYmaps();
                        $buildMiniMap = '';
                        $placemarks = generic_MapAddCircle($buildData['geo'], '30');
                        $placemarks .= um_MapDrawBuilds($buildData['id']); //only selected build on minimap
                        $buildMiniMap .= generic_MapContainer('100%', '400px;', 'singlebuildmap');
                        $buildMiniMap .= generic_MapInit($buildData['geo'], $mapOptions['FINDING_ZOOM'], $mapOptions['TYPE'], $placemarks, '', $mapOptions['LANG'], 'singlebuildmap');
                        show_window(__('Mini-map'), $buildMiniMap);
                    }
                }

                //Previous tasks on users in this build
                if (cfr('TASKMAN')) {
                    $previousBuildTasks = ts_PreviousBuildTasksRender($buildId, true);
                    if (!empty($previousBuildTasks)) {
                        show_window(__('Previous tasks in this build'), $previousBuildTasks);
                    }
                }
                //Optional additional comments
                if ($ubillingConfig->getAlterParam('ADCOMMENTS_ENABLED')) {
                    $adComments = new ADcomments('BUILDS');
                    show_window(__('Additional comments'), $adComments->renderComments($buildId));
                }
            } else {
                show_error(__('Something went wrong') . ': EX_WRONG_BUILDID');
            }
        } else {
            show_error(__('Something went wrong') . ': EX_NO_BUILDID');
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}