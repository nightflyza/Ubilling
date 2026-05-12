<?php

if (cfr('CUSTMAP')) {

    $altCfg = $ubillingConfig->getAlter();

    if ($altCfg['CUSTMAP_ENABLED']) {
        $custmaps = new CustMaps();

        // new custom map creation
        if (ubRouting::checkPost($custmaps::PROUTE_NEWMAPNAME)) {
            if (cfr('CUSTMAPEDIT')) {
                $custmaps->mapCreate(ubRouting::post($custmaps::PROUTE_NEWMAPNAME));
                ubRouting::nav(CustMaps::urlMapList());
            } else {
                show_error(__('Permission denied'));
            }
        }

        //custom map deletion
        if (ubRouting::checkGet($custmaps::ROUTE_DELETEMAP)) {
            if (cfr('CUSTMAPEDIT')) {
                $custmaps->mapDelete(ubRouting::get($custmaps::ROUTE_DELETEMAP, 'int'));
                ubRouting::nav(CustMaps::urlMapList());
            } else {
                show_error(__('Permission denied'));
            }
        }

        //editing existing custom map name
        if (ubRouting::checkPost(array($custmaps::PROUTE_EDITMAPID, $custmaps::PROUTE_EDITMAPNAME))) {
            if (cfr('CUSTMAPEDIT')) {
                $custmaps->mapEdit(ubRouting::post($custmaps::PROUTE_EDITMAPID, 'int'), ubRouting::post($custmaps::PROUTE_EDITMAPNAME));
                ubRouting::nav(CustMaps::urlMapList());
            } else {
                show_error(__('Permission denied'));
            }
        }

        //creating new map item
        if (ubRouting::checkPost(array($custmaps::PROUTE_NEWITEMGEO, $custmaps::PROUTE_NEWITEMTYPE))) {
            if (ubRouting::checkGet($custmaps::ROUTE_SHOWMAP)) {
                if (cfr('CUSTMAPEDIT')) {
                    $showMapId = ubRouting::get($custmaps::ROUTE_SHOWMAP, 'int');
                    $custmaps->itemCreate($showMapId, ubRouting::post($custmaps::PROUTE_NEWITEMTYPE), ubRouting::post($custmaps::PROUTE_NEWITEMGEO), ubRouting::post($custmaps::PROUTE_NEWITEMNAME), ubRouting::post($custmaps::PROUTE_NEWITEMLOCATION));
                    ubRouting::nav($custmaps::URL_ME . '&' . $custmaps::ROUTE_SHOWMAP . '=' . $showMapId . '&' . $custmaps::ROUTE_MAPEDIT . '=true');
                } else {
                    show_error(__('Permission denied'));
                }
            }
        }

        //deleting map item
        if (ubRouting::checkGet($custmaps::ROUTE_DELETEITEM)) {
            if (cfr('CUSTMAPEDIT')) {
                $deleteResult = $custmaps->itemDelete(ubRouting::get($custmaps::ROUTE_DELETEITEM, 'int'));
                ubRouting::nav($custmaps::URL_ME . '&' . $custmaps::ROUTE_SHOWITEMS . '=' . $deleteResult);
            } else {
                show_error(__('Permission denied'));
            }
        }

        //creating new map line
        if (ubRouting::checkPost(array($custmaps::PROUTE_NEWLINE_MAPID, $custmaps::PROUTE_NEWLINE_STYLE_WIDTH, $custmaps::PROUTE_NEWLINE_STYLE_COLOR, $custmaps::PROUTE_NEWLINE_GEO))) {
            if (cfr('CUSTMAPEDIT')) {
                $lineMapId = ubRouting::post($custmaps::PROUTE_NEWLINE_MAPID, 'int');
                $lineId = ubRouting::post($custmaps::PROUTE_NEWLINE_LINEID, 'int');
                if (!empty($lineId)) {
                    $custmaps->lineEdit(
                        $lineId,
                        ubRouting::post($custmaps::PROUTE_NEWLINE_NAME),
                        ubRouting::post($custmaps::PROUTE_NEWLINE_FIBERS_AMOUNT),
                        ubRouting::post($custmaps::PROUTE_NEWLINE_LENGTH_M),
                        ubRouting::post($custmaps::PROUTE_NEWLINE_STYLE_COLOR),
                        ubRouting::post($custmaps::PROUTE_NEWLINE_STYLE_WIDTH),
                        ubRouting::post($custmaps::PROUTE_NEWLINE_DESCRIPTION),
                        ubRouting::post($custmaps::PROUTE_NEWLINE_GEO)
                    );
                    ubRouting::nav($custmaps::URL_ME . '&' . $custmaps::ROUTE_SHOWMAP . '=' . $lineMapId . '&' . $custmaps::ROUTE_LINEEDIT . '=true&' . $custmaps::ROUTE_MODIFYLINE . '=' . $lineId);
                } else {
                    $custmaps->lineCreate(
                        $lineMapId,
                        ubRouting::post($custmaps::PROUTE_NEWLINE_NAME),
                        ubRouting::post($custmaps::PROUTE_NEWLINE_FIBERS_AMOUNT),
                        ubRouting::post($custmaps::PROUTE_NEWLINE_LENGTH_M),
                        ubRouting::post($custmaps::PROUTE_NEWLINE_STYLE_COLOR),
                        ubRouting::post($custmaps::PROUTE_NEWLINE_STYLE_WIDTH),
                        ubRouting::post($custmaps::PROUTE_NEWLINE_DESCRIPTION),
                        ubRouting::post($custmaps::PROUTE_NEWLINE_GEO)
                    );
                    ubRouting::nav($custmaps::URL_ME . '&' . $custmaps::ROUTE_SHOWMAP . '=' . $lineMapId . '&' . $custmaps::ROUTE_LINEEDIT . '=true');
                }
            } else {
                show_error(__('Permission denied'));
            }
        }

        //deleting map line
        if (ubRouting::checkGet($custmaps::ROUTE_DELETELINE)) {
            if (cfr('CUSTMAPEDIT')) {
                $deleteResult = $custmaps->lineDelete(ubRouting::get($custmaps::ROUTE_DELETELINE, 'int'));
                ubRouting::nav($custmaps::URL_ME . '&' . $custmaps::ROUTE_SHOWLINES . '=' . $deleteResult);
            } else {
                show_error(__('Permission denied'));
            }
        }

        if (!ubRouting::checkGet($custmaps::ROUTE_SHOWMAP)) {
            if (!ubRouting::checkGet($custmaps::ROUTE_SHOWITEMS) and !ubRouting::checkGet($custmaps::ROUTE_SHOWLINES) and !ubRouting::checkGet($custmaps::ROUTE_EDITITEM) and !ubRouting::checkGet($custmaps::ROUTE_MODIFYLINE) and !ubRouting::checkGet($custmaps::ROUTE_MAPLIST)) {
                ubRouting::nav(CustMaps::urlMapList());
            }
            if (ubRouting::checkGet($custmaps::ROUTE_SHOWITEMS)) {
                $showItemsMapId = ubRouting::get($custmaps::ROUTE_SHOWITEMS, 'int');
                show_window(__('Markers') . ': ' . $custmaps->mapGetName($showItemsMapId), $custmaps->renderItemsList($showItemsMapId));
            } else {
                if (ubRouting::checkGet($custmaps::ROUTE_SHOWLINES)) {
                    $showLinesMapId = ubRouting::get($custmaps::ROUTE_SHOWLINES, 'int');
                    show_window(__('Lines') . ': ' . $custmaps->mapGetName($showLinesMapId), $custmaps->renderLinesList($showLinesMapId));
                } else {
                    if (ubRouting::checkGet($custmaps::ROUTE_EDITITEM)) {
                        $editItemId = ubRouting::get($custmaps::ROUTE_EDITITEM, 'int');
                        if (ubRouting::checkPost(array($custmaps::PROUTE_EDITITEMID, $custmaps::PROUTE_EDITITEMTYPE))) {
                            if (cfr('CUSTMAPEDIT')) {
                                $custmaps->itemEdit($editItemId, ubRouting::post($custmaps::PROUTE_EDITITEMTYPE), ubRouting::post($custmaps::PROUTE_EDITITEMGEO), ubRouting::post($custmaps::PROUTE_EDITITEMNAME), ubRouting::post($custmaps::PROUTE_EDITITEMLOCATION));
                                ubRouting::nav($custmaps::URL_ME . '&' . $custmaps::ROUTE_EDITITEM . '=' . $editItemId);
                            } else {
                                show_error(__('Permission denied'));
                            }
                        }

                        //render marker edit UI aka marker profile
                        $custmaps->renderMarkerEdit($editItemId);
                    } else {
                        if (ubRouting::checkGet($custmaps::ROUTE_MODIFYLINE)) {
                            if (cfr('CUSTMAPEDIT')) {
                                $editLineId = ubRouting::get($custmaps::ROUTE_MODIFYLINE, 'int');
                                if (ubRouting::checkPost(array($custmaps::PROUTE_EDITLINEID, $custmaps::PROUTE_EDITLINE_STYLE_WIDTH, $custmaps::PROUTE_EDITLINE_STYLE_COLOR, $custmaps::PROUTE_EDITLINE_GEO))) {
                                    $custmaps->lineEdit(
                                        $editLineId,
                                        ubRouting::post($custmaps::PROUTE_EDITLINE_NAME),
                                        ubRouting::post($custmaps::PROUTE_EDITLINE_FIBERS_AMOUNT),
                                        ubRouting::post($custmaps::PROUTE_EDITLINE_LENGTH_M),
                                        ubRouting::post($custmaps::PROUTE_EDITLINE_STYLE_COLOR),
                                        ubRouting::post($custmaps::PROUTE_EDITLINE_STYLE_WIDTH),
                                        ubRouting::post($custmaps::PROUTE_EDITLINE_DESCRIPTION),
                                        ubRouting::post($custmaps::PROUTE_EDITLINE_GEO)
                                    );
                                    ubRouting::nav($custmaps::URL_ME . '&' . $custmaps::ROUTE_MODIFYLINE . '=' . $editLineId);
                                }
                                show_window(__('Edit'), $custmaps->lineEditForm($editLineId));
                            } else {
                                show_error(__('Permission denied'));
                            }
                        } else {
                            if (ubRouting::checkGet($custmaps::ROUTE_MAPLIST)) {
                                //rendering available maps list
                                show_window(__('Available custom maps'), $custmaps->renderMapList());
                                zb_BillingStats(true);
                            }
                        }
                    }
                }
            }
        } else {
            $mapId = ubRouting::get($custmaps::ROUTE_SHOWMAP, 'int');

            $custmaps->mapGetPlacemarks($mapId);
            $custmaps->mapGetLines($mapId);

            //custom map layers processing
            if (ubRouting::checkGet($custmaps::ROUTE_CL)) {
                if (!empty(ubRouting::get($custmaps::ROUTE_CL))) {
                    $custLayers = explode('_', ubRouting::get($custmaps::ROUTE_CL));
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
            
            if (ubRouting::checkGet(array($custmaps::ROUTE_MAPEDIT, $custmaps::ROUTE_SHOWMAP))) {
                if (cfr('CUSTMAPEDIT')) {
                    $custmaps->mapLocationEditor();
                }
            }

            if (ubRouting::checkGet(array($custmaps::ROUTE_LINEEDIT, $custmaps::ROUTE_SHOWMAP))) {
                if (cfr('CUSTMAPEDIT')) {
                    $custmaps->mapLineEditor($mapId);
                }
            }

            show_window($custmaps->mapGetName($mapId), $custmaps->mapInit());
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
