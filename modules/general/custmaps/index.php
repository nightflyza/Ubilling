<?php

if (cfr('SWITCHMAP')) {

    $altercfg = $ubillingConfig->getAlter();

    if ($altercfg['CUSTMAP_ENABLED']) {
        $custmaps = new CustomMaps();

        // new custom map creation
        if (wf_CheckPost(array('newmapname'))) {
            if (cfr('CUSTMAPEDIT')) {
                $custmaps->mapCreate($_POST['newmapname']);
                rcms_redirect('?module=custmaps');
            } else {
                show_error(__('Permission denied'));
            }
        }

        //custom map deletion
        if (wf_CheckGet(array('deletemap'))) {
            if (cfr('CUSTMAPEDIT')) {
                $custmaps->mapDelete($_GET['deletemap']);
                rcms_redirect('?module=custmaps');
            } else {
                show_error(__('Permission denied'));
            }
        }

        //editing existing custom map name
        if (wf_CheckPost(array('editmapid', 'editmapname'))) {
            if (cfr('CUSTMAPEDIT')) {
                $custmaps->mapEdit($_POST['editmapid'], $_POST['editmapname']);
                rcms_redirect('?module=custmaps');
            } else {
                show_error(__('Permission denied'));
            }
        }

        //creating new map item
        if (wf_CheckPost(array('newitemgeo', 'newitemtype'))) {
            if (wf_CheckGet(array('showmap'))) {
                if (cfr('CUSTMAPEDIT')) {
                    $custmaps->itemCreate($_GET['showmap'], $_POST['newitemtype'], $_POST['newitemgeo'], $_POST['newitemname'], $_POST['newitemlocation']);
                    rcms_redirect('?module=custmaps&showmap=' . $_GET['showmap'] . '&mapedit=true');
                } else {
                    show_error(__('Permission denied'));
                }
            }
        }



        if (!wf_CheckGet(array('showmap'))) {
            //render existing custom maps list
            if (!wf_CheckGet(array('showitems'))) {
                show_window(__('Available custom maps'), $custmaps->renderMapList());
            } else {
                //render map items list
                show_window(__('Objects'), $custmaps->renderItemsList($_GET['showitems']));
            }
        } else {
            $mapId = $_GET['showmap'];
            $placemarks = $custmaps->mapGetPlacemarks($mapId);
            if (wf_CheckGet(array('layers'))) {
                $layers = $_GET['layers'];
                //switches layer
                if (ispos($layers, 'sw')) {
                    $placemarks.=sm_MapDrawSwitches();
                }
                //switches uplinks layer
                if (ispos($layers, 'ul')) {
                    $placemarks.=sm_MapDrawSwitchUplinks();
                }
                //builds layer
                if (ispos($layers, 'bs')) {
                    $placemarks.=um_MapDrawBuilds();
                }
            }
            if (wf_CheckGet(array('mapedit', 'showmap'))) {
                $editor = $custmaps->mapLocationEditor();
            } else {
                $editor = '';
            }
            show_window($custmaps->mapGetName($mapId), $custmaps->mapInit($placemarks, $editor));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>