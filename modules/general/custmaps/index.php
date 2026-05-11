<?php

if (cfr('CUSTMAP')) {

    $altCfg = $ubillingConfig->getAlter();

    if ($altCfg['CUSTMAP_ENABLED']) {
        $custmaps = new CustomMaps();

        // new custom map creation
        if (ubRouting::checkPost('newmapname')) {
            if (cfr('CUSTMAPEDIT')) {
                $custmaps->mapCreate(ubRouting::post('newmapname'));
                ubRouting::nav('?module=custmaps');
            } else {
                show_error(__('Permission denied'));
            }
        }

        //custom map deletion
        if (ubRouting::checkGet('deletemap')) {
            if (cfr('CUSTMAPEDIT')) {
                $custmaps->mapDelete(ubRouting::get('deletemap', 'int'));
                ubRouting::nav('?module=custmaps');
            } else {
                show_error(__('Permission denied'));
            }
        }

        //editing existing custom map name
        if (ubRouting::checkPost(array('editmapid', 'editmapname'))) {
            if (cfr('CUSTMAPEDIT')) {
                $custmaps->mapEdit(ubRouting::post('editmapid', 'int'), ubRouting::post('editmapname'));
                ubRouting::nav('?module=custmaps');
            } else {
                show_error(__('Permission denied'));
            }
        }

        //creating new map item
        if (ubRouting::checkPost(array('newitemgeo', 'newitemtype'))) {
            if (ubRouting::checkGet('showmap')) {
                if (cfr('CUSTMAPEDIT')) {
                    $showMapId = ubRouting::get('showmap', 'int');
                    $custmaps->itemCreate($showMapId, ubRouting::post('newitemtype'), ubRouting::post('newitemgeo'), ubRouting::post('newitemname'), ubRouting::post('newitemlocation'));
                    ubRouting::nav('?module=custmaps&showmap=' . $showMapId . '&mapedit=true');
                } else {
                    show_error(__('Permission denied'));
                }
            }
        }

        //deleting map item
        if (ubRouting::checkGet('deleteitem')) {
            if (cfr('CUSTMAPEDIT')) {
                $deleteResult = $custmaps->itemDelete(ubRouting::get('deleteitem', 'int'));
                ubRouting::nav('?module=custmaps&showitems=' . $deleteResult);
            } else {
                show_error(__('Permission denied'));
            }
        }

        //creating new map line
        if (ubRouting::checkPost(array('newline_mapid', 'newline_style_width', 'newline_style_color', 'newline_geo'))) {
            if (cfr('CUSTMAPEDIT')) {
                $lineMapId = ubRouting::post('newline_mapid', 'int');
                $lineId = ubRouting::post('newline_lineid', 'int');
                if (!empty($lineId)) {
                    $custmaps->lineEdit(
                        $lineId,
                        ubRouting::post('newline_name'),
                        ubRouting::post('newline_fibers_amount'),
                        ubRouting::post('newline_length_m'),
                        ubRouting::post('newline_style_color'),
                        ubRouting::post('newline_style_width'),
                        ubRouting::post('newline_description'),
                        ubRouting::post('newline_geo')
                    );
                    ubRouting::nav('?module=custmaps&showmap=' . $lineMapId . '&lineedit=true&editline=' . $lineId);
                } else {
                    $custmaps->lineCreate(
                        $lineMapId,
                        ubRouting::post('newline_name'),
                        ubRouting::post('newline_fibers_amount'),
                        ubRouting::post('newline_length_m'),
                        ubRouting::post('newline_style_color'),
                        ubRouting::post('newline_style_width'),
                        ubRouting::post('newline_description'),
                        ubRouting::post('newline_geo')
                    );
                    ubRouting::nav('?module=custmaps&showmap=' . $lineMapId . '&lineedit=true');
                }
            } else {
                show_error(__('Permission denied'));
            }
        }

        //deleting map line
        if (ubRouting::checkGet('deleteline')) {
            if (cfr('CUSTMAPEDIT')) {
                $deleteResult = $custmaps->lineDelete(ubRouting::get('deleteline', 'int'));
                ubRouting::nav('?module=custmaps&showlines=' . $deleteResult);
            } else {
                show_error(__('Permission denied'));
            }
        }

        if (!ubRouting::checkGet('showmap')) {
            if (ubRouting::checkGet('showitems')) {
                $showItemsMapId = ubRouting::get('showitems', 'int');
                if (!ubRouting::checkGet('duplicates')) {
                    //render items list json data in background
                    if (ubRouting::checkGet('ajax')) {
                        $custmaps->renderItemsListJsonData($showItemsMapId);
                    }
                    //render map items list container
                    show_window(__('Objects') . ': ' . $custmaps->mapGetName($showItemsMapId), $custmaps->renderItemsListFast($showItemsMapId));
                } else {
                    //show duplicate map objects
                    show_window(__('Show duplicates') . ': ' . $custmaps->mapGetName($showItemsMapId), $custmaps->renderItemDuplicateList($showItemsMapId));
                }
            } else {
                if (ubRouting::checkGet('showlines')) {
                    $showLinesMapId = ubRouting::get('showlines', 'int');
                    show_window(__('Lines') . ': ' . $custmaps->mapGetName($showLinesMapId), $custmaps->renderLinesList($showLinesMapId));
                } else {
                    if (ubRouting::checkGet('edititem')) {
                        $editItemId = ubRouting::get('edititem', 'int');
                        //editing item
                        if (ubRouting::checkPost(array('edititemid', 'edititemtype'))) {
                            if (cfr('CUSTMAPEDIT')) {
                                $custmaps->itemEdit($editItemId, ubRouting::post('edititemtype'), ubRouting::post('edititemgeo'), ubRouting::post('edititemname'), ubRouting::post('edititemlocation'));
                                ubRouting::nav('?module=custmaps&edititem=' . $editItemId);
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
                    } else {
                        if (ubRouting::checkGet('editline')) {
                            $editLineId = ubRouting::get('editline', 'int');
                            if (ubRouting::checkPost(array('editlineid', 'editline_style_width', 'editline_style_color', 'editline_geo'))) {
                                if (cfr('CUSTMAPEDIT')) {
                                    $custmaps->lineEdit(
                                        $editLineId,
                                        ubRouting::post('editline_name'),
                                        ubRouting::post('editline_fibers_amount'),
                                        ubRouting::post('editline_length_m'),
                                        ubRouting::post('editline_style_color'),
                                        ubRouting::post('editline_style_width'),
                                        ubRouting::post('editline_description'),
                                        ubRouting::post('editline_geo')
                                    );
                                    ubRouting::nav('?module=custmaps&editline=' . $editLineId);
                                } else {
                                    show_error(__('Permission denied'));
                                }
                            }
                            show_window(__('Edit'), $custmaps->lineEditForm($editLineId));
                        } else {
                            //render existing custom maps list
                            show_window(__('Available custom maps'), $custmaps->renderMapList());
                            zb_BillingStats(true);
                        }
                    }
                }
            }
        } else {
            $mapId = ubRouting::get('showmap', 'int');
            //additional centering and zoom
            if (ubRouting::checkGet(array('locateitem', 'zoom'))) {
                $custmaps->setCenter(ubRouting::get('locateitem'));
                $custmaps->setZoom(ubRouting::get('zoom', 'int'));
                $searchRadius = 30;
                $custmaps->mapAddCircle(ubRouting::get('locateitem'), $searchRadius, __('Search area radius') . ' ' . $searchRadius . ' ' . __('meters'), __('Search area'));
            }

            $custmaps->mapGetPlacemarks($mapId);
            $custmaps->mapGetLines($mapId);

            //custom map layers processing
            if (ubRouting::checkGet('cl')) {
                if (!empty(ubRouting::get('cl'))) {
                    $custLayers = explode('z', ubRouting::get('cl'));
                    if (!empty($custLayers)) {
                        foreach ($custLayers as $eachCustLayerId) {
                            if (!empty($eachCustLayerId)) {
                                $custmaps->mapGetPlacemarks($eachCustLayerId);
                                $custmaps->mapGetLines($eachCustLayerId);
                            }
                        }
                    }
                }
            }
            if (ubRouting::checkGet(array('mapedit', 'showmap'))) {
                $custmaps->mapLocationEditor();
            }

            if (ubRouting::checkGet(array('lineedit', 'showmap'))) {
                $custmaps->mapLineEditor($mapId);
            }

            show_window($custmaps->mapGetName($mapId), $custmaps->mapInit());
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
