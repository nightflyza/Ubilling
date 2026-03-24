<?php

if (cfr('WAREHOUSE')) {

    $altcfg = $ubillingConfig->getAlter();
    if ($altcfg['WAREHOUSE_ENABLED']) {
        $warehouse = new Warehouse();
        show_window('', $warehouse->renderPanel());
            //categories    
            if (ubRouting::checkGet(array('categories'))) {
                if (ubRouting::checkPost(array('newcategory'))) {
                    $warehouse->categoriesCreate(ubRouting::post('newcategory'));
                    ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_CATEGORIES);
                }
                if (ubRouting::checkGet(array('deletecategory'))) {
                    $deletionResult = $warehouse->categoriesDelete(ubRouting::get('deletecategory'));
                    if ($deletionResult) {
                        ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_CATEGORIES);
                    } else {
                        show_error(__('You cant do this'));
                    }
                }
                if (ubRouting::checkPost(array('editcategoryname', 'editcategoryid'))) {
                    $warehouse->categoriesSave();
                    ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_CATEGORIES);
                }
                show_window(__('Categories'), $warehouse->categoriesCreateForm());
                show_window(__('Available categories'), $warehouse->categoriesRenderList());
                $warehouse->backControl();
            }
            //itemtypes
            if (ubRouting::checkGet(array('itemtypes'))) {
                if (ubRouting::checkPost(array('newitemtypecetegoryid', 'newitemtypename', 'newitemtypeunit'))) {
                    $warehouse->itemtypesCreate(ubRouting::post('newitemtypecetegoryid'), ubRouting::post('newitemtypename'), ubRouting::post('newitemtypeunit'), ubRouting::post('newitemtypereserve'));
                    ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_ITEMTYPES);
                }
                if (ubRouting::checkGet(array('deleteitemtype'))) {
                    $deletionResult = $warehouse->itemtypesDelete(ubRouting::get('deleteitemtype'));
                    if ($deletionResult) {
                        ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_ITEMTYPES);
                    } else {
                        show_error(__('You cant do this'));
                    }
                }
                if (ubRouting::checkPost(array('edititemtypeid'))) {
                    $warehouse->itemtypesSave();
                    ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_ITEMTYPES);
                }

                if (ubRouting::checkGet('edititemtype')) {
                    $editingItemtypeId = ubRouting::get('edititemtype', 'int');
                    $itemtypeEditingName = $warehouse->itemtypeGetName($editingItemtypeId);
                    show_window(__('Edit') . ' ' . $itemtypeEditingName, $warehouse->itemtypesEditForm($editingItemtypeId));
                    show_window('', wf_BackLink($warehouse::URL_ME . '&' . $warehouse::URL_ITEMTYPES));
                } else {
                    show_window(__('Warehouse item types'), $warehouse->itemtypesCreateForm());
                    show_window(__('Available item types'), $warehouse->itemtypesRenderList());
                    $warehouse->backControl();
                }
            }

            //storages
            if (ubRouting::checkGet(array('storages'))) {
                if (ubRouting::checkPost(array('newstorage'))) {
                    $warehouse->storagesCreate(ubRouting::post('newstorage'));
                    ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_STORAGES);
                }

                if (ubRouting::checkGet(array('deletestorage'))) {
                    $deletionResult = $warehouse->storagesDelete(ubRouting::get('deletestorage'));
                    if ($deletionResult) {
                        ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_STORAGES);
                    } else {
                        show_error(__('You cant do this'));
                    }
                }

                if (ubRouting::checkPost(array('editstorageid', 'editstoragename'))) {
                    $warehouse->storagesSave();
                    ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_STORAGES);
                }

                show_window(__('Warehouse storage'), $warehouse->storagesCreateForm());
                show_window(__('Available warehouse storages'), $warehouse->storagesRenderList());
                    $warehouse->backControl();
            }


            //contractors
            if (ubRouting::checkGet(array('contractors'))) {
                if (ubRouting::checkPost(array('newcontractor'))) {
                    $warehouse->contractorCreate(ubRouting::post('newcontractor'));
                    ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_CONTRACTORS);
                }

                if (ubRouting::checkGet(array('deletecontractor'))) {
                    $deletionResult = $warehouse->contractorsDelete(ubRouting::get('deletecontractor'));
                    if ($deletionResult) {
                        ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_CONTRACTORS);
                    } else {
                        show_error(__('You cant do this'));
                    }
                }
                if (ubRouting::checkPost(array('editcontractorid', 'editcontractorname'))) {
                    $warehouse->contractorsSave();
                    ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_CONTRACTORS);
                }

                show_window(__('Contractors'), $warehouse->contractorsCreateForm());
                show_window(__('Available contractors'), $warehouse->contractorsRenderList());
                    $warehouse->backControl();
            }

            if (ubRouting::checkGet(array('in'))) {
                if (ubRouting::checkGet(array('ajits'))) {
                    die($warehouse->itemtypesCategorySelector('newinitemtypeid', ubRouting::get('ajits')));
                }
                if (ubRouting::checkGet(array('ajaxinlist'))) {
                    $warehouse->incomingListAjaxReply();
                }
                if (ubRouting::checkPost(array('newindate', 'newinitemtypeid', 'newincontractorid', 'newinstorageid', 'newincount'))) {
                    $warehouse->incomingCreate(ubRouting::post('newindate'), ubRouting::post('newinitemtypeid'), ubRouting::post('newincontractorid'), ubRouting::post('newinstorageid'), ubRouting::post('newincount'), ubRouting::post('newinprice'), ubRouting::post('newinbarcode'), ubRouting::post('newinnotes'));
                    ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_IN);
                }
                show_window(__('Create new incoming operation'), $warehouse->incomingCreateForm());
                show_window(__('Available incoming operations'), $warehouse->incomingOperationsList());
                    $warehouse->backControl();
            }


            //outcoming
            if (ubRouting::checkGet(array('out'))) {
                if (ubRouting::checkGet(array('ajods'))) {
                                        die($warehouse->outcomindAjaxDestSelector(ubRouting::get('ajods')));
                }

                if (ubRouting::checkGet(array('ajaxoutlist'))) {
                    $warehouse->outcomingListAjaxReply();
                }

                if (ubRouting::checkPost(array('newoutdate', 'newoutdesttype', 'newoutdestparam', 'newoutitemtypeid', 'newoutstorageid', 'newoutcount'))) {
                    $outCreateResult = $warehouse->outcomingCreate(ubRouting::post('newoutdate'), ubRouting::post('newoutdesttype'), ubRouting::post('newoutdestparam'), ubRouting::post('newoutstorageid'), ubRouting::post('newoutitemtypeid'), ubRouting::post('newoutcount'), ubRouting::post('newoutprice'), ubRouting::post('newoutnotes'), ubRouting::post('newoutfromreserve'), ubRouting::post('newoutnetw'));
                    if (!empty($outCreateResult)) {
                        show_window(__('Something went wrong'), $outCreateResult);
                    } else {
                        ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_OUT);
                    }
                }
                if (!ubRouting::checkGet(array('storageid'))) {
                    show_window(__('Warehouse storages'), $warehouse->outcomingStoragesList());
                    if (!ubRouting::checkGet('withnotes')) {
                        $notesControl = ' ' . wf_Link($warehouse::URL_ME . '&' . $warehouse::URL_OUT . '&withnotes=true', wf_img_sized('skins/icon_note.gif', __('Show notes'), '12', '12'));
                    } else {
                        $notesControl = ' ' . wf_Link($warehouse::URL_ME . '&' . $warehouse::URL_OUT, wf_img_sized('skins/icon_note.gif', __('Hide notes'), '12', '12'));
                    }
                    show_window(__('Available outcoming operations') . $notesControl, $warehouse->outcomingOperationsList());
                    $warehouse->backControl();
                } else {
                    if (ubRouting::checkGet(array('storageid', 'ajaxremains'))) {
                    $warehouse->outcomingRemainsAjaxReply(ubRouting::get('storageid'));
                    }

                    if (!ubRouting::checkGet(array('outitemid'))) {
                        $storageId = ubRouting::get('storageid');
                        $remainsPrintControls = ' ' . wf_Link($warehouse::URL_ME . '&' . $warehouse::URL_VIEWERS . '&printremainsstorage=' . $storageId, wf_img('skins/icon_print.png', __('Print')));
                        show_window(__('The remains in the warehouse storage') . ': ' . $warehouse->storageGetName($storageId) . $remainsPrintControls, $warehouse->outcomingItemsList($storageId));
                    $warehouse->backControl($warehouse::URL_ME . '&' . $warehouse::URL_OUT);
                    } else {
                        show_window(__('New outcoming operation') . ' ' . $warehouse->itemtypeGetName(ubRouting::get('outitemid')), $warehouse->outcomingCreateForm(ubRouting::get('storageid'), ubRouting::get('outitemid'), @ubRouting::get('reserveid')));
                    $warehouse->backControl($warehouse::URL_ME . '&' . $warehouse::URL_OUT);
                    }
                }
            }

            //reservation
            if (ubRouting::checkGet(array('reserve'))) {
                if (ubRouting::checkGet(array('itemtypeid', 'storageid'))) {
                    if (ubRouting::checkPost(array('newreserveitemtypeid', 'newreservestorageid', 'newreserveemployeeid', 'newreservecount'))) {
                        $creationResult = $warehouse->reserveCreate(ubRouting::post('newreservestorageid'), ubRouting::post('newreserveitemtypeid'), ubRouting::post('newreservecount'), ubRouting::post('newreserveemployeeid'));
                        //succefull
                        if (!$creationResult) {
                            ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_RESERVE);
                        } else {
                            show_window('', $creationResult);
                        }
                    }
                    $reservationTitle = __('Reservation') . ' ' . $warehouse->itemtypeGetName(ubRouting::get('itemtypeid')) . ' ' . __('from') . ' ' . $warehouse->storageGetName(ubRouting::get('storageid'));
                    show_window($reservationTitle, $warehouse->reserveCreateForm(ubRouting::get('storageid'), ubRouting::get('itemtypeid')));
                    $warehouse->backControl($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&' . 'totalremains=true');
                } else {
                    if (ubRouting::checkGet(array('deletereserve'))) {
                    $warehouse->reserveDelete(ubRouting::get('deletereserve'));
                        ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_RESERVE);
                    }

                    if (ubRouting::checkPost(array('editreserveid'))) {
                    $warehouse->reserveSave();
                        ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_RESERVE);
                    }

                    if (ubRouting::checkGet(array('reshistajlist'))) {
                    $warehouse->reserveHistoryAjaxReply();
                    }

                    if (ubRouting::checkPost(array('reshistfilterfrom'))) {
                    $warehouse->reserveHistoryPrintFiltered();
                    }

                    if (ubRouting::checkGet(array('reserveajlist'))) {
                        $resEmpFilter = ubRouting::checkGet('empidfilter') ? ubRouting::get('empidfilter') : '';
                    $warehouse->reserveListAjaxReply($resEmpFilter);
                    }

                    if (ubRouting::checkPost(array('newmassemployeeid', 'newmassstorageid', 'newmasscreation'))) {
                        $massReserveResult = $warehouse->reserveMassCreate();
                        //rendering mass reserve results
                        show_window('', $massReserveResult);
                    }

                    $reserveControls = '';
                    if (ubRouting::checkGet('empidfilter')) {
                        $inventoryUrl = $warehouse::URL_ME . '&' . $warehouse::URL_RESERVE . '&empinventory=' . ubRouting::get('empidfilter');
                        $reserveControls .= wf_Link($inventoryUrl, wf_img('skins/icon_user.gif', __('Employee inventory')), false) . ' ';
                    } else {
                        $reserveControls = wf_Link($warehouse::URL_ME . '&' . $warehouse::URL_RESERVE . '&printable=true', web_icon_print(), false, '', 'target="_BLANK"') . ' ';
                    }

                    $reserveControls .= wf_Link($warehouse::URL_ME . '&' . $warehouse::URL_RESERVE . '&reshistory=true', wf_img('skins/time_machine.png', __('History')), false) . ' ';
                    if (ubRouting::checkGet('empidfilter')) {
                        if (cfr('WAREHOUSEOUTRESERVE') or cfr('WAREHOUSEOUT')) {
                            $massOutUrl = $warehouse::URL_ME . '&' . $warehouse::URL_RESERVE . '&massoutemployee=' . ubRouting::get('empidfilter');
                            $reserveControls .= wf_Link($massOutUrl, wf_img('skins/drain_icon.png', __('Mass outcome')), false) . ' ';
                        }
                    }
                    $reserveControls .= wf_Link($warehouse::URL_ME . '&' . $warehouse::URL_RESERVE . '&mass=true', web_icon_create(__('Mass reservation')), false) . ' ';

                    if (!ubRouting::checkGet('mass') and !ubRouting::checkGet('massoutemployee')) {
                        if (ubRouting::checkGet(array('reshistory'))) {
                            show_window(__('Reserve') . ': ' . __('History'), $warehouse->reserveRenderHistory());
                        } else {
                            if (ubRouting::checkGet('empinventory')) {
                    $warehouse->reportEmployeeInventrory(ubRouting::get('empinventory'));
                            } else {
                                show_window(__('Reserved') . ' ' . $reserveControls, $warehouse->reserveRenderList());
                            }
                        }
                    } else {
                        if (!ubRouting::checkGet('massoutemployee')) {
                            show_window(__('Mass reservation'), $warehouse->reserveMassForm());
                        } else {
                            //batch outcome creation
                            if (ubRouting::checkPost($warehouse::PROUTE_DOMASSRESOUT)) {
                                $massResOutResult = $warehouse->runMassReserveOutcome();
                                if (empty($massResOutResult)) {
                                    ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_OUT);
                                } else {
                                    show_window(__('Error'), $massResOutResult);
                                }
                            } else {
                                //rendering some mass reserve outcome UI
                                $massOutEmployeeId = ubRouting::get('massoutemployee');
                                $massoutEmployeeName = $warehouse->getEmployeeName($massOutEmployeeId);
                                $massOutWinLabel = __('Mass outcome') . ' ' . __('from reserved on') . ' ' . $massoutEmployeeName . ' ';
                                $massEmpChForm = $warehouse->renderMassOutEmployyeReplaceForm($massOutEmployeeId);
                                $massOutWinLabel .= wf_modalAuto(wf_img('skins/icon_replace_employee.png', __('Change employee')), __('Change employee'), $massEmpChForm);
                                show_window($massOutWinLabel, $warehouse->renderMassOutForm($massOutEmployeeId));
                            }
                        }
                    }
                    $warehouse->backControl($warehouse::URL_ME);
                }
            }

            //viewers
            if (ubRouting::checkGet('viewers')) {
                if (ubRouting::checkGet('showinid')) {
                    //editing subroutine
                    if (ubRouting::checkPost('editincomeid')) {
                        if (cfr('WAREHOUSEINEDT')) {
                            $incEditResult = $warehouse->incomingSaveChanges();
                            if (empty($incEditResult)) {
                                ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_VIEWERS . '&showinid=' . ubRouting::post('editincomeid'));
                            } else {
                                show_error($incEditResult);
                            }
                        } else {
                            show_error(__('Access denied'));
                        }
                    }

                    //deletion subroutine
                    if (ubRouting::checkGet($warehouse::ROUTE_DELIN)) {
                        if (cfr('WAREHOUSEINEDT')) {
                            $incDelResult = $warehouse->incomingDelete(ubRouting::get($warehouse::ROUTE_DELIN));
                            if (empty($incDelResult)) {
                                ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_VIEWERS . '&showinid=' . ubRouting::get($warehouse::ROUTE_DELIN));
                            } else {
                                show_error($incDelResult);
                            }
                        } else {
                            show_error(__('Access denied'));
                        }
                    }
                    //rendering income op itself
                    show_window(__('Incoming operation') . ': ' . ubRouting::get('showinid'), $warehouse->incomingView(ubRouting::get('showinid')));
                    $warehouse->backControl($warehouse::URL_ME . '&' . $warehouse::URL_IN);
                }

                if (ubRouting::checkGet('showoutid')) {
                    if (ubRouting::checkGet($warehouse::ROUTE_DELOUT)) {
                    $warehouse->outcomingDelete(ubRouting::get($warehouse::ROUTE_DELOUT));
                        ubRouting::nav($warehouse::URL_ME . '&' . $warehouse::URL_VIEWERS . '&showoutid=' . ubRouting::get($warehouse::ROUTE_DELOUT));
                    }
                    show_window(__('Outcoming operation') . ': ' . ubRouting::get('showoutid'), $warehouse->outcomingView(ubRouting::get('showoutid')));
                    $warehouse->backControl($warehouse::URL_ME . '&' . $warehouse::URL_OUT);
                }

                if (ubRouting::checkGet(array('showremains'))) {
                    show_window(__('The remains in the warehouse storage'), $warehouse->reportAllStoragesRemainsView(ubRouting::get('showremains')));
                    $warehouse->backControl($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&totalremains=true');
                }

                if (ubRouting::checkGet(array('qrcode', 'renderid'))) {
                    $warehouse->qrCodeDraw(ubRouting::get('qrcode'), ubRouting::get('renderid'));
                }

                if (ubRouting::checkGet(array('printremainsstorage'))) {
                    $warehouse->reportStorageRemainsPrintable(ubRouting::get('printremainsstorage'));
                }

                if (ubRouting::checkGet(array('itemhistory'))) {
                    $warehouse->renderItemHistory(ubRouting::get('itemhistory'));
                }
            }

            //reports
            if (ubRouting::checkGet(array('reports'))) {
                if (ubRouting::checkGet(array('ajaxtremains'))) {
                    $warehouse->reportAllStoragesRemainsAjaxReply();
                }

                if (ubRouting::checkGet(array('calendarops'))) {
                    if (cfr('WAREHOUSEREPORTS')) {
                        show_window(__('Operations in the context of time'), $warehouse->reportCalendarOps());
                    } else {
                        show_error(__('Access denied'));
                    }
                    $warehouse->backControl($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&' . 'totalremains=true');
                }
                if (ubRouting::checkGet(array('totalremains'))) {
                    show_window(__('The remains in all storages'), $warehouse->reportAllStoragesRemains());
                    $warehouse->backControl();
                }

                if (ubRouting::checkGet(array('dateremains'))) {
                    if (cfr('WAREHOUSEREPORTS')) {
                        show_window(__('Date remains'), $warehouse->reportDateRemains());
                    } else {
                        show_error(__('Access denied'));
                    }
                    $warehouse->backControl($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&' . 'totalremains=true');
                }

                if (ubRouting::checkGet(array('storagesremains'))) {
                    if (cfr('WAREHOUSEREPORTS')) {
                        show_window(__('The remains in the warehouse storage'), $warehouse->reportStoragesRemains());
                    $warehouse->backControl($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&' . 'totalremains=true');
                    } else {
                        show_error(__('Access denied'));
                    }
                }

                if (ubRouting::checkGet('itemtypeoutcomes')) {
                    if (cfr('WAREHOUSEREPORTS')) {
                        show_window(__('Sales'), $warehouse->renderItemtypeOutcomesHistory());
                    $warehouse->backControl($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&' . 'totalremains=true');
                    } else {
                        show_error(__('Access denied'));
                    }
                }

                if (ubRouting::checkGet('purchases')) {
                    if (cfr('WAREHOUSEREPORTS')) {
                        show_window(__('Purchases'), $warehouse->renderPurchasesReport());
                    $warehouse->backControl($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&' . 'totalremains=true');
                    } else {
                        show_error(__('Access denied'));
                    }
                }

                if (ubRouting::checkGet('contractorincomes')) {
                    if (cfr('WAREHOUSEREPORTS')) {
                        show_window(__('Income') . ' ' . __('from') . ' ' . __('Contractor'), $warehouse->renderContractorIncomesReport());
                    $warehouse->backControl($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&' . 'totalremains=true');
                    } else {
                        show_error(__('Access denied'));
                    }
                }

                if (ubRouting::checkGet('returns')) {
                    if (cfr('WAREHOUSEREPORTS')) {
                        if (ubRouting::checkGet('ajreturnslist')) {
                    $warehouse->ajReturnsList();
                        }
                        show_window(__('Returns'), $warehouse->renderReturnsReport());
                    $warehouse->backControl($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&' . 'totalremains=true');
                    } else {
                        show_error(__('Access denied'));
                    }
                }

                if (ubRouting::checkGet('netwupgrade')) {
                    if (cfr('WAREHOUSEREPORTS')) {
                        show_window(__('Item types spent on network upgrade'), $warehouse->renderNetwUpgradeReport());
                    $warehouse->backControl($warehouse::URL_ME);
                    } else {
                        show_error(__('Access denied'));
                    }
                }
            }


            $warehouse->summaryReport();
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Permission denied'));
}
