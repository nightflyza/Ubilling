<?php
if (cfr('DREAMKAS')) {
    if ($ubillingConfig->getAlterParam('DREAMKAS_ENABLED')) {
        $greed = new Avarice();
        $insatiability = $greed->runtime('DREAMKAS');

        if (!empty($insatiability)) {
            $DreamKasNotify = new DreamKasNotifications();

            if (wf_CheckGet(array('getnotys'))) {
                $DreamKasNotify->getDreamkasNotifications();
            }

            $DreamKas = new DreamKas();
            $voracity = $insatiability['LT']['LAL'];
            $rapacity = $insatiability['M']['KICKTHEFLOOR'];

            show_window(__($insatiability['LT']['LIL']), $DreamKas->$rapacity());
            zb_BillingStats(true);

            if (wf_CheckGet(array('dreamkasforcecacheupdate'))) {
                $DreamKas->refreshCacheForced();
                $messageWindow = $DreamKas->getUbMsgHelperInstance()->getStyledMessage(__('Cache data updated succesfuly'),
                                                                                       'success', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                die(wf_modalAutoForm('', $messageWindow, $_GET['modalWindowId'], '', true));
            }

            if (wf_CheckGet(array('cashiersslistajax'))) {
                $DreamKas->renderCashiersListJSON();
            }

            if (wf_CheckGet(array('cashmachineslistajax'))) {
                $DreamKas->renderCashMachinesListJSON();
            }

            if (wf_CheckGet(array('goodslistajax'))) {
                $DreamKas->renderSellPositionsListJSON();
            }

            if (wf_CheckGet(array('foperationslistajax'))) {
                //$dateFrom = (wf_CheckGet(array('fopsdatefrom'))) ? $_GET['fopsdatefrom'] : date('Y-m-d', strtotime(curdate() . "-1 day"));
                //$dateTo = (wf_CheckGet(array('fopsdateto'))) ? $_GET['fopsdateto'] : curdate();
                $dateFrom = (wf_CheckGet(array('fopsdatefrom'))) ? $_GET['fopsdatefrom'] : '';
                $dateTo = (wf_CheckGet(array('fopsdateto'))) ? $_GET['fopsdateto'] : '';

                $DreamKas->renderFiscalOperationsListJSON($dateFrom, $dateTo);
            }

            $rapacity = $insatiability['M']['GETBABLO'];
            $voracity_a = $insatiability['PG']['RLX'];
            $voracity_b = $insatiability['PG']['RDF'];
            $voracity_c = $insatiability['PG']['RDT'];
            $voracity_d = $insatiability['PG']['DAVID'];
            $voracity_e = $insatiability['PG']['RMC'];

            if (wf_CheckGet(array($voracity_a))) {
                $voracity_b = (wf_CheckGet(array($voracity_b))) ? $_GET[$voracity_b] : '';
                $voracity_c = (wf_CheckGet(array($voracity_c))) ? $_GET[$voracity_c] : '';
                $voracity_d = (wf_CheckGet(array($voracity_d))) ? $_GET[$voracity_d] : '';
                $voracity_e = (wf_CheckGet(array($voracity_e))) ? $_GET[$voracity_e] : '';

                $DreamKas->$rapacity($voracity_b, $voracity_c, $voracity_d, $voracity_e);
            }

            if (wf_CheckGet(array('webhookslistajax'))) {
                $DreamKas->renderWebhooksListJSON();
            }

            if (wf_CheckGet(array('getcashiers'))) {
                show_window(__('Cashiers'), $DreamKas->renderCashiersJQDT());
            }

            if (wf_CheckGet(array('getcashmachines'))) {
                show_window(__('Cash machines'), $DreamKas->renderCashMachinesJQDT());
            }

            if (wf_CheckGet(array('getgoods'))) {
                show_window(__('Selling positions'), $DreamKas->renderSellPositionsJQDT());
            }

            if (wf_CheckGet(array('getoperations'))) {
                show_window(__('Operations'), $DreamKas->web_FiscalOperationsFilter() . $DreamKas->renderFiscalOperationsJQDT());
            }

            if (wf_CheckGet(array('getreceipts'))) {
                show_window(__('Checks'), $DreamKas->web_ReceiptsFilter() . $DreamKas->renderReceiptsJQDT());
            }

            if (wf_CheckGet(array('getwebhookss'))) {
                $DreamKas->web_WebhooksForm();
            }

            if (wf_CheckPost(array('whcreate'))) {
                if (wf_CheckPost(array('whfullurl'))) {
                    $DreamKas->createeditdeleteWebhook($_POST['whfullurl'], wf_CheckPost(array('whisactive')), $_POST['whnotifyopts']);
                    die();
                }

                die(wf_modalAutoForm(__('Add webhook'), $DreamKas->renderWebhookAddForm($_POST['modalWindowId']), $_POST['modalWindowId'], $_POST['modalWindowBodyId'], true));
            }

            if (wf_CheckPost(array('whedit'))) {
                if (wf_CheckPost(array('whfullurl'))) {
                    $DreamKas->createeditdeleteWebhook($_POST['whfullurl'], wf_CheckPost(array('whisactive')), $_POST['whnotifyopts'], $_POST['whid']);
                    die();
                }

                die(wf_modalAutoForm(__('Edit webhook'), $DreamKas->renderWebhookEditForm($_POST['whid'], $_POST['modalWindowId']), $_POST['modalWindowId'], $_POST['modalWindowBodyId'], true));
            }

            if (wf_CheckPost(array('delWebhook'))) {
                $DreamKas->createeditdeleteWebhook('', '', '', $_POST['whid'], true);
                die();
            }

            if (wf_CheckGet(array('showdetailedrcpt'))) {
                $windowContent = wf_tag('pre', false, 'floatpanelswide', 'style="width: 90%; padding: 10px 15px;"');
                $windowContent .= print_r($DreamKas->getReceiptDetails($_GET['showdetailedrcpt']), true);
                $windowContent .= wf_tag('pre', true);

                die(wf_modalAutoForm(__($voracity), $windowContent, $_GET['modalWindowId'], '', true));
            }

            if (wf_CheckGet(array('showdetailedCM'))) {
                $windowContent = wf_tag('pre', false, 'floatpanelswide', 'style="width: 90%; padding: 10px 15px;"');
                $windowContent .= print_r($DreamKas->getCashMachines($_GET['showdetailedCM']), true);
                $windowContent .= wf_tag('pre', true);

                die(wf_modalAutoForm(__($voracity), $windowContent, $_GET['modalWindowId'], '', true));
            }

            if (wf_CheckGet(array('showdetailedGoods'))) {
                $windowContent = wf_tag('pre', false, 'floatpanelswide', 'style="width: 90%; padding: 10px 15px;"');
                $windowContent .= print_r($DreamKas->getSellingPositions($_GET['showdetailedGoods']), true);
                $windowContent .= wf_tag('pre', true);

                die(wf_modalAutoForm(__($voracity), $windowContent, $_GET['modalWindowId'], '', true));
            }

            $rapacity = $insatiability['M']['PORORO'];

            if (wf_CheckGet(array($insatiability['PG']['SLX']))) {
                $result = $DreamKas->$rapacity($_GET[$insatiability['PG']['SLX']],
                                               $_GET[$insatiability['PG']['GRID']],
                                               $_GET[$insatiability['PG']['GRAME']],
                                               $_GET[$insatiability['PG']['GRYPE']],
                                               $_GET[$insatiability['PG']['GRICE']],
                                               $_GET[$insatiability['PG']['GRAX']],
                                               $_GET[$insatiability['PG']['HACHIKUJI']]
                                              );

                if (empty($result)) {
                    $msg = $DreamKas->getUbMsgHelperInstance()->getStyledMessage(__('Done'), 'success', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                    $result = wf_modalAutoForm(__('Success'), $msg, $_GET['modalWindowId'], '', true);
                } else {
                    $errormes = $DreamKas->getUbMsgHelperInstance()->getStyledMessage($result, 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                    $result = wf_modalAutoForm(__('Error'), $errormes, $_GET['modalWindowId'], '', true);
                }

                die($result);
            }

            if (wf_CheckGet(array('delselpossrvmapping'))) {
                $service = $_GET['servicetype'];

                if (empty($service)) {
                    $errormes = $DreamKas->getUbMsgHelperInstance()->getStyledMessage(__('This selling posistion is not mapped to any service yet'),
                                                                                      'warning', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');

                    die(wf_modalAutoForm(__('Warning'), $errormes, $_GET['modalWindowId'], '', true));
                } else {
                    $DreamKas->delSellingPositionSrvType($service);
                    die();
                }
            }

            if (wf_CheckGet(array($insatiability['PG']['HANEKAWA']))) {
                $windowContent = wf_tag('pre', false, 'floatpanelswide', 'style="width: 90%; padding: 10px 15px;"');
                $windowContent .= print_r(json_decode($DreamKas->getFiscalOperationLocalBody($_GET[$insatiability['PG']['HANEKAWA']]), true), true);
                $windowContent .= wf_tag('pre', true);

                die(wf_modalAutoForm(__($voracity), $windowContent, $_GET['modalWindowId'], '', true));
            }

            $rapacity_a = $insatiability['M']['SMACKUP'];
            $rapacity_b = $insatiability['M']['PACKUP'];
            $rapacity_c = $insatiability['M']['PICKUP'];
            $rapacity_d = $insatiability['M']['PUSHCASHLO'];
            $rapacity_e = $insatiability['M']['BAKA'];

            if (wf_CheckGet(array($insatiability['PG']['OSHINO']))) {
                $DreamKas->$rapacity_a($_GET[$insatiability['PG']['OSHINO']]);
                $voracity_e = $DreamKas->$rapacity_b($_GET[$insatiability['PG']['OSHINO']]);
                $voracity_n = $DreamKas->$rapacity_e($_GET[$insatiability['PG']['OSHINO']]);
                $DreamKas->$rapacity_d($voracity_e, $voracity_n, $_GET[$insatiability['PG']['OSHINO']]);
                $voracity_f = $DreamKas->$rapacity_c();

                if (!empty($voracity_f)) {
                    $errormes = $DreamKas->getUbMsgHelperInstance()->getStyledMessage($voracity_f, 'error');
                    die(wf_modalAutoForm(__($insatiability['LT']['LOL']), $errormes, $_GET['modalWindowId'], '', true, 'true', '700'));
                } else {
                    $message = $DreamKas->getUbMsgHelperInstance()->getStyledMessage(__($insatiability['LT']['LUL']), 'info');
                    die(wf_modalAutoForm(__($insatiability['LT']['LEL']), $message, $_GET['modalWindowId'], '', true, 'true', '400'));
                }
            }
        } else {
            show_error(__('No license key available'));
        }
    } else {
        show_warning(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>