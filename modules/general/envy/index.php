<?php

if (cfr('ENVY')) {
    $altCfg = $ubillingConfig->getAlter();

    if (@$altCfg['ENVY_ENABLED']) {
        set_time_limit(0); // may be so slow

        $envy = new Envy();
        //new script creation
        if (ubRouting::checkPost(array('newscriptmodel'))) {
            $creationResult = $envy->createScript(ubRouting::post('newscriptmodel'), ubRouting::post('newscriptdata'));
            if (empty($creationResult)) {
                ubRouting::nav($envy::URL_ME . '&' . $envy::ROUTE_SCRIPTS . '=true');
            } else {
                show_error($creationResult);
            }
        }

        //existing script deletion
        if (ubRouting::checkGet(array('deletescript'))) {
            $deletionResult = $envy->deleteScript(ubRouting::get('deletescript'));
            if (empty($deletionResult)) {
                ubRouting::nav($envy::URL_ME . '&' . $envy::ROUTE_SCRIPTS . '=true');
            } else {
                show_error($deletionResult);
            }
        }

        //existing script editing
        if (ubRouting::checkPost('editscriptid')) {
            $savingResult = $envy->saveScript();
            if (empty($savingResult)) {
                ubRouting::nav($envy::URL_ME . '&' . $envy::ROUTE_SCRIPTS . '=true');
            } else {
                show_error($savingResult);
            }
        }

        //new device creation
        if (ubRouting::checkPost('newdeviceswitchid')) {
            $devCreationResult = $envy->createDevice();
            if (empty($devCreationResult)) {
                ubRouting::nav($envy::URL_ME . '&' . $envy::ROUTE_DEVICES . '=true');
            } else {
                show_error($devCreationResult);
            }
        }


        //editing existing device
        if (ubRouting::checkPost(array('editdeviceid', 'editdeviceswitchid'))) {
            $devSaveResult = $envy->saveDevice();
            if (empty($devSaveResult)) {
                ubRouting::nav($envy::URL_ME . '&' . $envy::ROUTE_DEVICES . '=true');
            } else {
                show_error($devSaveResult);
            }
        }

        //device config storing to archive
        if (ubRouting::checkGet('storedevice')) {
            $storeResult = $envy->storeArchiveData(ubRouting::get('storedevice'), $envy->runDeviceScript(ubRouting::get('storedevice')));
            if (empty($storeResult)) {
                if (ubRouting::checkGet('resave')) {
                    $returnUrl = $envy::URL_ME;
                } else {
                    $returnUrl = $envy::URL_ME . '&' . $envy::ROUTE_DEVICES . ' = true';
                }
                ubRouting::nav($returnUrl);
            } else {
                show_error($storeResult);
            }
        }

        //all existing devices config backup
        if (ubRouting::checkGet($envy::ROUTE_ARCHALL)) {
            $envy->storeArchiveAllDevices();
            ubRouting::nav($envy::URL_ME . '&' . $envy::ROUTE_DEVICES . ' = true');
        }

        //device deletion
        if (ubRouting::checkGet('deletedevice')) {
            $devDeletionResult = $envy->deleteDevice(ubRouting::get('deletedevice'));
            if (empty($devDeletionResult)) {
                ubRouting::nav($envy::URL_ME . '&' . $envy::ROUTE_DEVICES . ' = true');
            } else {
                show_error($devDeletionResult);
            }
        }

        //archive record deletion
        if (ubRouting::checkGet('deletearchiveid')) {
            $archDeletionResult = $envy->deleteArchiveRecord(ubRouting::get('deletearchiveid'));
            if (empty($archDeletionResult)) {
                ubRouting::nav($envy::URL_ME);
            } else {
                show_error($archDeletionResult);
            }
        }

        //archive record download
        if (ubRouting::checkGet('downloadarchiveid')) {
            $envy->downloadArchiveRecordConfig(ubRouting::get('downloadarchiveid'));
        }

        //background archive JSON rendering
        if (ubRouting::checkGet($envy::ROUTE_ARCHIVE_AJ)) {
            $envy->getAjArchive();
        }

        if (ubRouting::checkGet('previewdevice') OR ubRouting::checkGet('viewarchiveid')) {
            //device preview
            if (ubRouting::checkGet('previewdevice')) {
                show_window('', wf_BackLink($envy::URL_ME . '&' . $envy::ROUTE_DEVICES . ' = true'));
                show_window(__('Preview'), $envy->previewScriptsResult($envy->runDeviceScript(ubRouting::get('previewdevice'))));
            }

            //archive record view
            if (ubRouting::checkGet('viewarchiveid')) {
                show_window('', wf_BackLink($envy::URL_ME));
                show_window(__('Preview'), $envy->previewScriptsResult($envy->renderArchiveRecordConfig(ubRouting::get('viewarchiveid'))));
            }
        } else {

            //showing some module controls here
            show_window('', $envy->renderControls());

            //devices management
            if (ubRouting::checkGet($envy::ROUTE_DEVICES)) {
                show_window(__('Available envy devices'), $envy->renderDevicesList());
            }

            //scripts management
            if (ubRouting::checkGet($envy::ROUTE_SCRIPTS)) {
                show_window(__('Available envy scripts'), $envy->renderScriptsList());
            }

            //diff viewer
            if (ubRouting::checkGet($envy::ROUTE_DIFF)) {
                show_window('', $envy->renderDiffForm());
                //diff display between existing configs
                if (ubRouting::checkPost('rundiff', 'diffone', 'difftwo')) {
                    show_window(__('Changes'), $envy->renderDiff(ubRouting::post('diffone'), ubRouting::post('difftwo')));
                }
            }

            //here previous data archive
            if (!ubRouting::checkGet($envy::ROUTE_DEVICES) AND ! ubRouting::checkGet($envy::ROUTE_SCRIPTS) AND ! ubRouting::checkGet($envy::ROUTE_DIFF)) {
                show_window(__('Previously collected devices configs'), $envy->renderArchive());
                zb_BillingStats(true);
            }
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}