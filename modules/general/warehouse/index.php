<?php

if (cfr('WAREHOUSE')) {

    $altcfg = $ubillingConfig->getAlter();
    if ($altcfg['WAREHOUSE_ENABLED']) {
        $greed = new Avarice();
        $avidity = $greed->runtime('WAREHOUSE');
        if (!empty($avidity)) {
            $warehouse = new Warehouse();
            show_window('', $warehouse->$avidity['M']['WARLOCK']());
//categories    
            if (wf_CheckGet(array($avidity['S']['C']))) {
                if (wf_CheckPost(array($avidity['S']['CC']))) {
                    $warehouse->$avidity['M']['CC']($_POST[$avidity['S']['CC']]);
                    rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_CATEGORIES);
                }
                if (wf_CheckGet(array($avidity['S']['CD']))) {
                    $deletionResult = $warehouse->$avidity['M']['CD']($_GET[$avidity['S']['CD']]);
                    if ($deletionResult) {
                        rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_CATEGORIES);
                    } else {
                        show_error(__('You cant do this'));
                    }
                }
                if (wf_CheckPost(array($avidity['S']['CE1'], $avidity['S']['CE2']))) {
                    $warehouse->$avidity['M']['CS']();
                    rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_CATEGORIES);
                }
                show_window(__('Categories'), $warehouse->$avidity['M']['CF']());
                show_window(__('Available categories'), $warehouse->$avidity['M']['CL']());
                $warehouse->$avidity['M']['FALL']();
            }
//itemtypes
            if (wf_CheckGet(array('itemtypes'))) {
                if (wf_CheckPost(array('newitemtypecetegoryid', 'newitemtypename', 'newitemtypeunit'))) {
                    $warehouse->$avidity['M']['XC']($_POST['newitemtypecetegoryid'], $_POST['newitemtypename'], $_POST['newitemtypeunit'], @$_POST['newitemtypereserve']);
                    rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_ITEMTYPES);
                }
                if (wf_CheckGet(array($avidity['S']['XD']))) {
                    $deletionResult = $warehouse->$avidity['M']['XD']($_GET[$avidity['S']['XD']]);
                    if ($deletionResult) {
                        rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_ITEMTYPES);
                    } else {
                        show_error(__('You cant do this'));
                    }
                }
                if (wf_CheckPost(array($avidity['S']['XS']))) {
                    $warehouse->$avidity['M']['XS']();
                    rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_ITEMTYPES);
                }

                show_window(__('Warehouse item types'), $warehouse->$avidity['M']['XCF']());
                show_window(__('Available item types'), $warehouse->$avidity['M']['XL']());
                $warehouse->$avidity['M']['FALL']();
            }

//storages
            if (wf_CheckGet(array('storages'))) {
                if (wf_CheckPost(array('newstorage'))) {
                    $warehouse->storagesCreate($_POST['newstorage']);
                    rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_STORAGES);
                }

                if (wf_CheckGet(array('deletestorage'))) {
                    $deletionResult = $warehouse->storagesDelete($_GET['deletestorage']);
                    if ($deletionResult) {
                        rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_STORAGES);
                    } else {
                        show_error(__('You cant do this'));
                    }
                }

                if (wf_CheckPost(array('editstorageid', 'editstoragename'))) {
                    $warehouse->storagesSave();
                    rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_STORAGES);
                }

                show_window(__('Warehouse storage'), $warehouse->storagesCreateForm());
                show_window(__('Available warehouse storages'), $warehouse->storagesRenderList());
                $warehouse->$avidity['M']['FALL']();
            }


//contractors
            if (wf_CheckGet(array('contractors'))) {
                if (wf_CheckPost(array('newcontractor'))) {
                    $warehouse->contractorCreate($_POST['newcontractor']);
                    rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_CONTRACTORS);
                }

                if (wf_CheckGet(array('deletecontractor'))) {
                    $deletionResult = $warehouse->contractorsDelete($_GET['deletecontractor']);
                    if ($deletionResult) {
                        rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_CONTRACTORS);
                    } else {
                        show_error(__('You cant do this'));
                    }
                }
                if (wf_CheckPost(array('editcontractorid', 'editcontractorname'))) {
                    $warehouse->contractorsSave();
                    rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_CONTRACTORS);
                }

                show_window(__('Contractors'), $warehouse->contractorsCreateForm());
                show_window(__('Available contractors'), $warehouse->contractorsRenderList());
                $warehouse->$avidity['M']['FALL']();
            }

            if (wf_CheckGet(array('in'))) {
                if (wf_CheckGet(array('ajits'))) {
                    die($warehouse->itemtypesCategorySelector('newinitemtypeid', $_GET['ajits']));
                }
                if (wf_CheckGet(array('ajaxinlist'))) {
                    $warehouse->$avidity['A']['CINDERELLA']();
                }
                if (wf_CheckPost(array('newindate', 'newinitemtypeid', 'newincontractorid', 'newinstorageid', 'newincount'))) {
                    $warehouse->incomingCreate($_POST['newindate'], $_POST['newinitemtypeid'], $_POST['newincontractorid'], $_POST['newinstorageid'], $_POST['newincount'], @$_POST['newinprice'], @$_POST['newinbarcode'], $_POST['newinnotes']);
                    rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_IN);
                }
                show_window(__('Create new incoming operation'), $warehouse->incomingCreateForm());
                show_window(__('Available incoming operations'), $warehouse->incomingOperationsList());

                $warehouse->$avidity['M']['FALL']();
            }


//outcoming
            if (wf_CheckGet(array('out'))) {
                if (wf_CheckGet(array('ajods'))) {
                    die($warehouse->$avidity['A']['CHAINSAW']($_GET['ajods']));
                }

                if (wf_CheckGet(array('ajaxoutlist'))) {
                    $warehouse->$avidity['A']['ALICE']();
                }

                if (wf_CheckPost(array('newoutdate', 'newoutdesttype', 'newoutdestparam', 'newoutitemtypeid', 'newoutstorageid', 'newoutcount'))) {
                    $outCreateResult = $warehouse->outcomingCreate($_POST['newoutdate'], $_POST['newoutdesttype'], $_POST['newoutdestparam'], $_POST['newoutstorageid'], $_POST['newoutitemtypeid'], $_POST['newoutcount'], @$_POST['newoutprice'], @$_POST['newoutnotes']);
                    if (!empty($outCreateResult)) {
                        show_window(__('Something went wrong'), $outCreateResult);
                    } else {
                        rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_OUT);
                    }
                }
                if (!wf_CheckGet(array('storageid'))) {
                    show_window(__('Warehouse storages'), $warehouse->outcomingStoragesList());
                    show_window(__('Available outcoming operations'), $warehouse->outcomingOperationsList());
                    $warehouse->$avidity['M']['FALL']();
                } else {
                    if (wf_CheckGet(array('storageid', 'ajaxremains'))) {
                        $warehouse->$avidity['A']['FRIDAY']($_GET['storageid']);
                    }

                    if (!wf_CheckGet(array('outitemid'))) {
                        $storageId = $_GET['storageid'];
                        $remainsPrintControls = ' ' . wf_Link($warehouse::URL_ME . '&' . $warehouse::URL_VIEWERS . '&printremainsstorage=' . $storageId, wf_img('skins/icon_print.png', __('Print')));
                        show_window(__('The remains in the warehouse storage') . ': ' . $warehouse->storageGetName($storageId) . $remainsPrintControls, $warehouse->outcomingItemsList($storageId));
                        $warehouse->$avidity['M']['FALL']($warehouse::URL_ME . '&' . $warehouse::URL_OUT);
                    } else {
                        show_window(__('New outcoming operation') . ' ' . $warehouse->itemtypeGetName($_GET['outitemid']), $warehouse->outcomingCreateForm($_GET['storageid'], $_GET['outitemid']));
                        $warehouse->$avidity['M']['FALL']($warehouse::URL_ME . '&' . $warehouse::URL_OUT);
                    }
                }
            }

//reservation
            if (wf_CheckGet(array('reserve'))) {
                if (wf_CheckGet(array('itemtypeid', 'storageid'))) {
                    if (wf_CheckPost(array('newreserveitemtypeid', 'newreservestorageid', 'newreserveemployeeid', 'newreservecount'))) {
                        $creationResult = $warehouse->reserveCreate($_POST['newreservestorageid'], $_POST['newreserveitemtypeid'], $_POST['newreservecount'], $_POST['newreserveemployeeid']);
                        //succefull
                        if (!$creationResult) {
                            //old style redirect to outcome form
                            //rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_OUT . '&storageid=' . $_POST['newreservestorageid'] . '&outitemid=' . $_POST['newreserveitemtypeid']);
                            //new style - reservation preview
                            rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_RESERVE);
                        } else {
                            show_window('', $creationResult);
                        }
                    }
                    $reservationTitle = __('Reservation') . ' ' . $warehouse->itemtypeGetName($_GET['itemtypeid']) . ' ' . __('from') . ' ' . $warehouse->storageGetName($_GET['storageid']);
                    show_window($reservationTitle, $warehouse->reserveCreateForm($_GET['storageid'], $_GET['itemtypeid']));
                    //old back to outcoming operation creation
                    //$warehouse->$avidity['M']['FALL']($warehouse::URL_ME . '&' . $warehouse::URL_OUT . '&storageid=' . $_GET['storageid'] . '&outitemid=' . $_GET['itemtypeid']);
                    //new back to total remains report
                    $warehouse->$avidity['M']['FALL']($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&' . 'totalremains=true');
                } else {
                    if (wf_CheckGet(array('deletereserve'))) {
                        $warehouse->reserveDelete($_GET['deletereserve']);
                        rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_RESERVE);
                    }

                    if (wf_CheckPost(array('editreserveid'))) {
                        $warehouse->reserveSave();
                        rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_RESERVE);
                    }
                    show_window(__('Reserved'), $warehouse->reserveRenderList());
                    $warehouse->$avidity['M']['FALL']($warehouse::URL_ME);
                }
            }

//viewers
            if (wf_CheckGet(array('viewers'))) {
                if (wf_CheckGet(array('showinid'))) {
                    show_window(__('Incoming operation') . ': ' . $_GET['showinid'], $warehouse->incomingView($_GET['showinid']));
                    $warehouse->$avidity['M']['FALL']($warehouse::URL_ME . '&' . $warehouse::URL_IN);
                }

                if (wf_CheckGet(array('showoutid'))) {
                    show_window(__('Outcoming operation') . ': ' . $_GET['showoutid'], $warehouse->outcomingView($_GET['showoutid']));
                    $warehouse->$avidity['M']['FALL']($warehouse::URL_ME . '&' . $warehouse::URL_OUT);
                }

                if (wf_CheckGet(array('showremains'))) {
                    show_window(__('The remains in the warehouse storage'), $warehouse->reportAllStoragesRemainsView($_GET['showremains']));
                    $warehouse->$avidity['M']['FALL']($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&totalremains=true');
                }

                if (wf_CheckGet(array('qrcode', 'renderid'))) {
                    $warehouse->qrCodeDraw($_GET['qrcode'], $_GET['renderid']);
                }

                if (wf_CheckGet(array('printremainsstorage'))) {
                    $warehouse->reportStorageRemainsPrintable($_GET['printremainsstorage']);
                }
            }

//reports
            if (wf_CheckGet(array('reports'))) {
                if (wf_CheckGet(array('ajaxtremains'))) {
                    $warehouse->$avidity['A']['SEENOEVIL']();
                }

                if (wf_CheckGet(array('calendarops'))) {
                    show_window(__('Operations in the context of time'), $warehouse->reportCalendarOps());
                    $warehouse->$avidity['M']['FALL']($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&' . 'totalremains=true');
                }
                if (wf_CheckGet(array('totalremains'))) {
                    $calendarLink = wf_Link($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&calendarops=true', wf_img('skins/icon_calendar.gif', __('Operations in the context of time')), false, '');
                    $dateRemainsLink = wf_Link($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&dateremains=true', wf_img('skins/ukv/report.png', __('Date remains')));
                    show_window(__('The remains in all storages') . ' ' . $calendarLink . ' ' . $dateRemainsLink, $warehouse->reportAllStoragesRemains());
                    $warehouse->$avidity['M']['FALL']();
                }
                if (wf_CheckGet(array('dateremains'))) {
                    show_window(__('Date remains'), $warehouse->reportDateRemains());
                    $warehouse->$avidity['M']['FALL']();
                }
            }


            $warehouse->$avidity['M']['FRONT']();
        } else {
            show_error(__('No license key available'));
        }
    } else {
        show_error(__('This module is disabled'));
    }
} else {
    show_error(__('Permission denied'));
}
?>