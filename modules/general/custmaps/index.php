<?php

if (cfr('CUSTMAP')) {

    $altCfg = $ubillingConfig->getAlter();

    if ($altCfg['CUSTMAP_ENABLED']) {
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

        //deleting map item
        if (wf_CheckGet(array('deleteitem'))) {
            if (cfr('CUSTMAPEDIT')) {
                $deleteResult = $custmaps->itemDelete($_GET['deleteitem']);
                rcms_redirect('?module=custmaps&showitems=' . $deleteResult);
            } else {
                show_error(__('Permission denied'));
            }
        }

        //items upload as KML
        if (wf_CheckPost(array('itemsUploadTypes'))) {
            $custmaps->catchFileUpload();
        }



        if (!wf_CheckGet(array('showmap'))) {

            if (!wf_CheckGet(array('showitems'))) {
                if (!wf_CheckGet(array('edititem'))) {
                    //render existing custom maps list
                    show_window(__('Available custom maps'), $custmaps->renderMapList());
                    zb_BillingStats(true);
                } else {
                    $editItemId = $_GET['edititem'];
                    //editing item
                    if (wf_CheckPost(array('edititemid', 'edititemtype'))) {
                        if (cfr('CUSTMAPEDIT')) {
                            $custmaps->itemEdit($editItemId, $_POST['edititemtype'], $_POST['edititemgeo'], $_POST['edititemname'], $_POST['edititemlocation']);
                            rcms_redirect('?module=custmaps&edititem=' . $editItemId);
                        } else {
                            show_error(__('Permission denied'));
                        }
                    }

                    //show item edit form
                    show_window(__('Edit'), $custmaps->itemEditForm($editItemId));
                    //photostorage link
                    if ($altCfg['PHOTOSTORAGE_ENABLED']) {
                        $imageControl = wf_Link('?module=photostorage&scope=CUSTMAPSITEMS&itemid=' . $editItemId . '&mode=list', wf_img('skins/photostorage.png') . ' ' . __('Upload images'), false, 'ubButton');
                        show_window('', $imageControl);
                    }

                    //additional comments
                    if ($altCfg['ADCOMMENTS_ENABLED']) {
                        $adcomments = new ADcomments('CUSTMAPITEMS');
                        show_window(__('Additional comments'), $adcomments->renderComments($editItemId));
                    }
                }
            } else {
                if (!wf_CheckGet(array('duplicates'))) {
                    //render items list json data in background
                    if (wf_CheckGet(array('ajax'))) {
                        $custmaps->renderItemsListJsonData($_GET['showitems']);
                    }
                    //render map items list container
                    show_window(__('Objects') . ': ' . $custmaps->mapGetName($_GET['showitems']), $custmaps->renderItemsListFast($_GET['showitems']));
                } else {
                    //show duplicate map objects
                    show_window(__('Show duplicates') . ': ' . $custmaps->mapGetName($_GET['showitems']), $custmaps->renderItemDuplicateList($_GET['showitems']));
                }
            }
        } else {
            $mapId = $_GET['showmap'];
            $placemarks = '';
            //additional centering and zoom
            if (wf_CheckGet(array('locateitem', 'zoom'))) {
                $custmaps->setCenter($_GET['locateitem']);
                $custmaps->setZoom($_GET['zoom']);
                $searchRadius = 30;
                $placemarks.=$custmaps->mapAddCircle($_GET['locateitem'], $searchRadius, __('Search area radius') . ' ' . $searchRadius . ' ' . __('meters'), __('Search area'));
            }

            $placemarks.= $custmaps->mapGetPlacemarks($mapId);

            //custom map layers processing
            if (wf_CheckGet(array('cl'))) {
                if (!empty($_GET['cl'])) {
                    $custLayers = explode('z', $_GET['cl']);
                    if (!empty($custLayers)) {
                        foreach ($custLayers as $eachCustLayerId) {
                            if (!empty($eachCustLayerId)) {
                                $placemarks.=$custmaps->mapGetPlacemarks($eachCustLayerId);
                            }
                        }
                    }
                }
            }
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