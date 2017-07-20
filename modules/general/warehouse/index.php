<?php

if (cfr('WAREHOUSE')) {

    $altcfg = $ubillingConfig->getAlter();
    if ($altcfg['WAREHOUSE_ENABLED']) {
        $greed = new Avarice();
        $avidity = $greed->runtime('WAREHOUSE');
        if (!empty($avidity)) {
            $warehouse = new Warehouse();
            $avidity_m = $avidity['M']['WARLOCK'];
            show_window('', $warehouse->$avidity_m());
//categories    
            if (wf_CheckGet(array($avidity['S']['C']))) {
                if (wf_CheckPost(array($avidity['S']['CC']))) {
                    $avidity_m = $avidity['M']['CC'];
                    $warehouse->$avidity_m($_POST[$avidity['S']['CC']]);
                    rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_CATEGORIES);
                }
                if (wf_CheckGet(array($avidity['S']['CD']))) {
                    $avidity_m = $avidity['M']['CD'];
                    $deletionResult = $warehouse->$avidity_m($_GET[$avidity['S']['CD']]);
                    if ($deletionResult) {
                        rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_CATEGORIES);
                    } else {
                        show_error(__('You cant do this'));
                    }
                }
                if (wf_CheckPost(array($avidity['S']['CE1'], $avidity['S']['CE2']))) {
                    $avidity_m = $avidity['M']['CS'];
                    $warehouse->$avidity_m();
                    rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_CATEGORIES);
                }
                $avidity_m = $avidity['M']['CF'];
                show_window(__('Categories'), $warehouse->$avidity_m());
                $avidity_m = $avidity['M']['CL'];
                show_window(__('Available categories'), $warehouse->$avidity_m());
                $avidity_m = $avidity['M']['FALL'];
                $warehouse->$avidity_m();
            }
//itemtypes
            if (wf_CheckGet(array('itemtypes'))) {
                if (wf_CheckPost(array('newitemtypecetegoryid', 'newitemtypename', 'newitemtypeunit'))) {
                    $avidity_m = $avidity['M']['XC'];
                    $warehouse->$avidity_m($_POST['newitemtypecetegoryid'], $_POST['newitemtypename'], $_POST['newitemtypeunit'], @$_POST['newitemtypereserve']);
                    rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_ITEMTYPES);
                }
                if (wf_CheckGet(array($avidity['S']['XD']))) {
                    $avidity_m = $avidity['M']['XD'];
                    $deletionResult = $warehouse->$avidity_m($_GET[$avidity['S']['XD']]);
                    if ($deletionResult) {
                        rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_ITEMTYPES);
                    } else {
                        show_error(__('You cant do this'));
                    }
                }
                if (wf_CheckPost(array($avidity['S']['XS']))) {
                    $avidity_m = $avidity['M']['XS'];
                    $warehouse->$avidity_m();
                    rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_ITEMTYPES);
                }
                $avidity_m = $avidity['M']['XCF'];
                show_window(__('Warehouse item types'), $warehouse->$avidity_m());
                $avidity_m = $avidity['M']['XL'];
                show_window(__('Available item types'), $warehouse->$avidity_m());
                $avidity_m = $avidity['M']['FALL'];
                $warehouse->$avidity_m();
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
                $avidity_m = $avidity['M']['FALL'];
                $warehouse->$avidity_m();
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
                $avidity_m = $avidity['M']['FALL'];
                $warehouse->$avidity_m();
            }

            if (wf_CheckGet(array('in'))) {
                if (wf_CheckGet(array('ajits'))) {
                    die($warehouse->itemtypesCategorySelector('newinitemtypeid', $_GET['ajits']));
                }
                if (wf_CheckGet(array('ajaxinlist'))) {
                    $avidity_a = $avidity['A']['CINDERELLA'];
                    $warehouse->$avidity_a();
                }
                if (wf_CheckPost(array('newindate', 'newinitemtypeid', 'newincontractorid', 'newinstorageid', 'newincount'))) {
                    $warehouse->incomingCreate($_POST['newindate'], $_POST['newinitemtypeid'], $_POST['newincontractorid'], $_POST['newinstorageid'], $_POST['newincount'], @$_POST['newinprice'], @$_POST['newinbarcode'], $_POST['newinnotes']);
                    rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_IN);
                }
                show_window(__('Create new incoming operation'), $warehouse->incomingCreateForm());
                show_window(__('Available incoming operations'), $warehouse->incomingOperationsList());
                $avidity_m = $avidity['M']['FALL'];
                $warehouse->$avidity_m();
            }


//outcoming
            if (wf_CheckGet(array('out'))) {
                if (wf_CheckGet(array('ajods'))) {
                    $avidity_a = $avidity['A']['CHAINSAW'];
                    die($warehouse->$avidity_a($_GET['ajods']));
                }

                if (wf_CheckGet(array('ajaxoutlist'))) {
                    $avidity_a = $avidity['A']['ALICE'];
                    $warehouse->$avidity_a();
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
                    $avidity_m = $avidity['M']['FALL'];
                    $warehouse->$avidity_m();
                } else {
                    if (wf_CheckGet(array('storageid', 'ajaxremains'))) {
                        $avidity_a = $avidity['A']['FRIDAY'];
                        $warehouse->$avidity_a($_GET['storageid']);
                    }

                    if (!wf_CheckGet(array('outitemid'))) {
                        $storageId = $_GET['storageid'];
                        $remainsPrintControls = ' ' . wf_Link($warehouse::URL_ME . '&' . $warehouse::URL_VIEWERS . '&printremainsstorage=' . $storageId, wf_img('skins/icon_print.png', __('Print')));
                        show_window(__('The remains in the warehouse storage') . ': ' . $warehouse->storageGetName($storageId) . $remainsPrintControls, $warehouse->outcomingItemsList($storageId));
                        $avidity_m = $avidity['M']['FALL'];
                        $warehouse->$avidity_m($warehouse::URL_ME . '&' . $warehouse::URL_OUT);
                    } else {
                        show_window(__('New outcoming operation') . ' ' . $warehouse->itemtypeGetName($_GET['outitemid']), $warehouse->outcomingCreateForm($_GET['storageid'], $_GET['outitemid']));
                        $avidity_m = $avidity['M']['FALL'];
                        $warehouse->$avidity_m($warehouse::URL_ME . '&' . $warehouse::URL_OUT);
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
                            rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_RESERVE);
                        } else {
                            show_window('', $creationResult);
                        }
                    }
                    $reservationTitle = __('Reservation') . ' ' . $warehouse->itemtypeGetName($_GET['itemtypeid']) . ' ' . __('from') . ' ' . $warehouse->storageGetName($_GET['storageid']);
                    show_window($reservationTitle, $warehouse->reserveCreateForm($_GET['storageid'], $_GET['itemtypeid']));
                    $avidity_m = $avidity['M']['FALL'];
                    $warehouse->$avidity_m($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&' . 'totalremains=true');
                } else {
                    if (wf_CheckGet(array('deletereserve'))) {
                        $warehouse->reserveDelete($_GET['deletereserve']);
                        rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_RESERVE);
                    }

                    if (wf_CheckPost(array('editreserveid'))) {
                        $warehouse->reserveSave();
                        rcms_redirect($warehouse::URL_ME . '&' . $warehouse::URL_RESERVE);
                    }

                    if (wf_CheckGet(array('reshistajlist'))) {
                        $warehouse->reserveHistoryAjaxReply();
                    }
                    show_window(__('Reserved'), $warehouse->reserveRenderList());
                    show_window(__('History'), $warehouse->reserveRenderHistory());
                    $avidity_m = $avidity['M']['FALL'];
                    $warehouse->$avidity_m($warehouse::URL_ME);
                }
            }

//viewers
            if (wf_CheckGet(array('viewers'))) {
                if (wf_CheckGet(array('showinid'))) {
                    show_window(__('Incoming operation') . ': ' . $_GET['showinid'], $warehouse->incomingView($_GET['showinid']));
                    $avidity_m = $avidity['M']['FALL'];
                    $warehouse->$avidity_m($warehouse::URL_ME . '&' . $warehouse::URL_IN);
                }

                if (wf_CheckGet(array('showoutid'))) {
                    show_window(__('Outcoming operation') . ': ' . $_GET['showoutid'], $warehouse->outcomingView($_GET['showoutid']));
                    $avidity_m = $avidity['M']['FALL'];
                    $warehouse->$avidity_m($warehouse::URL_ME . '&' . $warehouse::URL_OUT);
                }

                if (wf_CheckGet(array('showremains'))) {
                    show_window(__('The remains in the warehouse storage'), $warehouse->reportAllStoragesRemainsView($_GET['showremains']));
                    $avidity_m = $avidity['M']['FALL'];
                    $warehouse->$avidity_m($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&totalremains=true');
                }

                if (wf_CheckGet(array('qrcode', 'renderid'))) {
                    $warehouse->qrCodeDraw($_GET['qrcode'], $_GET['renderid']);
                }

                if (wf_CheckGet(array('printremainsstorage'))) {
                    $warehouse->reportStorageRemainsPrintable($_GET['printremainsstorage']);
                }

                if (wf_CheckGet(array('itemhistory'))) {
                    $warehouse->renderItemHistory($_GET['itemhistory']);
                }
            }

//reports
            if (wf_CheckGet(array('reports'))) {
                if (wf_CheckGet(array('ajaxtremains'))) {
                    $avidity_a = $avidity['A']['SEENOEVIL'];
                    $warehouse->$avidity_a();
                }

                if (wf_CheckGet(array('calendarops'))) {
                    show_window(__('Operations in the context of time'), $warehouse->reportCalendarOps());
                    $avidity_m = $avidity['M']['FALL'];
                    $warehouse->$avidity_m($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&' . 'totalremains=true');
                }
                if (wf_CheckGet(array('totalremains'))) {
                    $calendarLink = wf_Link($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&calendarops=true', wf_img('skins/icon_calendar.gif', __('Operations in the context of time')), false, '');
                    $dateRemainsLink = wf_Link($warehouse::URL_ME . '&' . $warehouse::URL_REPORTS . '&dateremains=true', wf_img('skins/ukv/report.png', __('Date remains')));
                    show_window(__('The remains in all storages') . ' ' . $calendarLink . ' ' . $dateRemainsLink, $warehouse->reportAllStoragesRemains());
                    $avidity_m = $avidity['M']['FALL'];
                    $warehouse->$avidity_m();
                }
                if (wf_CheckGet(array('dateremains'))) {
                    show_window(__('Date remains'), $warehouse->reportDateRemains());
                    $avidity_m = $avidity['M']['FALL'];
                    $warehouse->$avidity_m();
                }
            }


            $avidity = $avidity['M']['FRONT'];
            $warehouse->$avidity();
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