<?php
if (cfr('EXTCONTRAS')) {
    if ($ubillingConfig->getAlterParam('EXTCONTRAS_FINANCE_ON')) {
        $ExtContras = new ExtContras();

        show_window(__('External counterparties: finances'), $ExtContras->renderMainControls());

file_put_contents('zxcv', '');
file_put_contents('axcv', '');

        if (ubRouting::checkPost($ExtContras::ROUTE_FORCECACHE_UPD)) {
            $ExtContras->refreshCacheForced();
            die($ExtContras->renderWebMsg(__('Info'), __('Cache data updated succesfuly'), 'info'));
        }

        if (ubRouting::checkGet($ExtContras::ROUTE_CONTRAS_JSON)){
            $ExtContras->extcontrasRenderListJSON();
        }

        if (ubRouting::checkGet($ExtContras::ROUTE_PROFILE_JSON)){
            $ExtContras->profileRenderListJSON();
        }

        if (ubRouting::checkGet($ExtContras::ROUTE_CONTRACT_JSON)) {
            $ExtContras->contractRenderListJSON();
        }

        if (ubRouting::checkGet($ExtContras::ROUTE_ADDRESS_JSON)) {
            $ExtContras->addressRenderListJSON();
        }

        if (ubRouting::checkGet($ExtContras::ROUTE_PERIOD_JSON)){
            $ExtContras->periodRenderListJSON();
        }

        if (ubRouting::checkGet($ExtContras::ROUTE_INVOICES_JSON)){
            $ExtContras->invoiceRenderListJSON();
        }

        if (ubRouting::checkPost($ExtContras::URL_EXTCONTRAS_COLORS)) {
            $ExtContras->setTableGridColorOpts();
        }

        if (ubRouting::checkGet($ExtContras::URL_EXTCONTRAS_COLORS)) {
            show_window(__('Counterparties table coloring settings'), $ExtContras->extcontrasColorSettings());
        }

        if (ubRouting::checkGet($ExtContras::URL_EXTCONTRAS)) {
            show_window(__('Counterparties list') . wf_nbsp(4)
                        . wf_Link($ExtContras::URL_ME . '&' . $ExtContras::URL_EXTCONTRAS_COLORS . '=true', wf_img_sized('skins/color-picker.png', __('Coloring settings config'), '22', '22', 'vertical-align: middle;')),
                        $ExtContras->extcontrasWebForm(false)
                        . wf_delimiter() . $ExtContras->extcontrasRenderJQDT()
            );
        }

        if (ubRouting::checkGet($ExtContras::URL_DICTPROFILES)) {
            show_window(__('Counterparties profiles dictionary'),
                        $ExtContras->profileWebForm(false)
                        . wf_delimiter() . $ExtContras->profileRenderJQDT(ubRouting::get($ExtContras::MISC_MARKROW_URL))
                       );
        }

        if (ubRouting::checkGet($ExtContras::URL_DICTCONTRACTS)) {
            show_window(__('Counterparties contracts dictionary'),
                        $ExtContras->contractWebForm(false)
                        . wf_delimiter() . $ExtContras->contractRenderJQDT()
                       );
        }

        if (ubRouting::checkGet($ExtContras::URL_DICTADDRESS)) {
            show_window(__('Counterparties addresses dictionary'),
                $ExtContras->addressWebForm(false)
                . wf_delimiter() . $ExtContras->addressRenderJQDT()
            );
        }

        if (ubRouting::checkGet($ExtContras::URL_DICTPERIODS)) {
            show_window(__('Periods dictionary'),
                        $ExtContras->periodWebForm(false)
                        . wf_delimiter() . $ExtContras->periodRenderJQDT()
                       );
        }

        if (ubRouting::checkGet($ExtContras::URL_INVOICES)) {
            show_window(__('Invoices'),
                        $ExtContras->invoiceWebForm(false)
                        . wf_delimiter() . $ExtContras->invoiceRenderJQDT()
            );
        }

        if (ubRouting::checkPost($ExtContras::ROUTE_PROFILE_ACTS)) {
            $dataArray = array($ExtContras::DBFLD_PROFILE_NAME      => ubRouting::post($ExtContras::CTRL_PROFILE_NAME),
                               $ExtContras::DBFLD_PROFILE_CONTACT   => ubRouting::post($ExtContras::CTRL_PROFILE_CONTACT),
                               $ExtContras::DBFLD_PROFILE_EDRPO     => ubRouting::post($ExtContras::CTRL_PROFILE_EDRPO),
                               $ExtContras::DBFLD_PROFILE_MAIL      => ubRouting::post($ExtContras::CTRL_PROFILE_MAIL)
                              );

            $chkUniqArray = $ExtContras->createCheckUniquenessArray($ExtContras::DBFLD_PROFILE_NAME, '=',
                                                                      ubRouting::post($ExtContras::CTRL_PROFILE_NAME));

            $showResult = $ExtContras->processCRUDs($dataArray, $ExtContras::TABLE_ECPROFILES, $ExtContras::CTRL_PROFILE_NAME,
                                                    'profileWebForm',true, $chkUniqArray,
                                                    'Profile');
            die($showResult);
        }

        if (ubRouting::checkPost($ExtContras::ROUTE_CONTRACT_ACTS)) {
            $autoprlngChk = ubRouting::post($ExtContras::CTRL_CTRCT_AUTOPRLNG, 'fi', FILTER_VALIDATE_BOOLEAN);
            $autoprlngChk = (empty($autoprlngChk) ? 0 : 1);

            $dataArray = array($ExtContras::DBFLD_CTRCT_CONTRACT    => ubRouting::post($ExtContras::CTRL_CTRCT_CONTRACT),
                               $ExtContras::DBFLD_CTRCT_DTSTART     => ubRouting::post($ExtContras::CTRL_CTRCT_DTSTART),
                               $ExtContras::DBFLD_CTRCT_DTEND       => ubRouting::post($ExtContras::CTRL_CTRCT_DTEND),
                               $ExtContras::DBFLD_CTRCT_SUBJECT     => ubRouting::post($ExtContras::CTRL_CTRCT_SUBJECT),
                               $ExtContras::DBFLD_CTRCT_FULLSUM     => ubRouting::post($ExtContras::CTRL_CTRCT_FULLSUM),
                               $ExtContras::DBFLD_CTRCT_NOTES       => ubRouting::post($ExtContras::CTRL_CTRCT_NOTES),
                               $ExtContras::DBFLD_CTRCT_AUTOPRLNG   => $autoprlngChk
                              );

            $chkUniqArray = $ExtContras->createCheckUniquenessArray($ExtContras::DBFLD_CTRCT_CONTRACT, '=',
                                                                    ubRouting::post($ExtContras::CTRL_CTRCT_CONTRACT));

            $showResult = $ExtContras->processCRUDs($dataArray, $ExtContras::TABLE_ECCONTRACTS, $ExtContras::CTRL_CTRCT_CONTRACT,
                                                    'contractWebForm',true, $chkUniqArray,
                                                    'Contract');
            die($showResult);
        }

        if (ubRouting::checkPost($ExtContras::ROUTE_ADDRESS_ACTS)) {
            $dataArray = array($ExtContras::DBFLD_ADDRESS_ADDR      => ubRouting::post($ExtContras::CTRL_ADDRESS_ADDR),
                               $ExtContras::DBFLD_ADDRESS_SUM       => ubRouting::post($ExtContras::CTRL_ADDRESS_SUM),
                               $ExtContras::DBFLD_ADDRESS_CTNOTES   => ubRouting::post($ExtContras::CTRL_ADDRESS_CTNOTES),
                               $ExtContras::DBFLD_ADDRESS_NOTES     => ubRouting::post($ExtContras::CTRL_ADDRESS_NOTES)
                            );

            $chkUniqArray = $ExtContras->createCheckUniquenessArray($ExtContras::DBFLD_ADDRESS_ADDR, '=',
                                                                    ubRouting::post($ExtContras::CTRL_ADDRESS_ADDR));

            $showResult = $ExtContras->processCRUDs($dataArray, $ExtContras::TABLE_ECADDRESS, $ExtContras::CTRL_ADDRESS_ADDR,
                                                    'addressWebForm', true, $chkUniqArray,
                                                    'Address');
            die($showResult);
        }

        if (ubRouting::checkPost($ExtContras::ROUTE_PERIOD_ACTS)) {
            $dataArray = array($ExtContras::DBFLD_PERIOD_NAME => ubRouting::post($ExtContras::CTRL_PERIOD_NAME));

            $chkUniqArray = $ExtContras->createCheckUniquenessArray($ExtContras::DBFLD_PERIOD_NAME, '=',
                                                                    ubRouting::post($ExtContras::CTRL_PERIOD_NAME));

            $showResult = $ExtContras->processCRUDs($dataArray, $ExtContras::TABLE_ECPERIODS, $ExtContras::CTRL_PERIOD_NAME,
                                                    'periodWebForm', true, $chkUniqArray,
                                                    'Period');
           die($showResult);
        }

        if (ubRouting::checkPost($ExtContras::ROUTE_INVOICES_ACTS)) {
            $invoIncoming = (ubRouting::post($ExtContras::CTRL_INVOICES_IN_OUT) == 'incoming') ? 1 : 0;
            $invoOutgoing = (ubRouting::post($ExtContras::CTRL_INVOICES_IN_OUT) == 'outgoing') ? 1 : 0;

            $dataArray = array($ExtContras::DBFLD_INVOICES_CONTRASID    => ubRouting::post($ExtContras::CTRL_INVOICES_CONTRASID),
                               $ExtContras::DBFLD_INVOICES_INTERNAL_NUM => ubRouting::post($ExtContras::CTRL_INVOICES_INTERNAL_NUM),
                               $ExtContras::DBFLD_INVOICES_INVOICE_NUM  => ubRouting::post($ExtContras::CTRL_INVOICES_INVOICE_NUM),
                               $ExtContras::DBFLD_INVOICES_DATE         => ubRouting::post($ExtContras::CTRL_INVOICES_DATE),
                               $ExtContras::DBFLD_INVOICES_SUM          => ubRouting::post($ExtContras::CTRL_INVOICES_SUM),
                               $ExtContras::DBFLD_INVOICES_SUM_VAT      => ubRouting::post($ExtContras::CTRL_INVOICES_SUM_VAT),
                               $ExtContras::DBFLD_INVOICES_NOTES        => ubRouting::post($ExtContras::CTRL_INVOICES_NOTES),
                               $ExtContras::DBFLD_INVOICES_INCOMING     => $invoIncoming,
                               $ExtContras::DBFLD_INVOICES_OUTGOING     => $invoOutgoing
                              );

            $chkUniqArray = $ExtContras->createCheckUniquenessArray($ExtContras::DBFLD_INVOICES_INVOICE_NUM, '=',
                                                                    ubRouting::post($ExtContras::CTRL_INVOICES_INVOICE_NUM));

            $showResult = $ExtContras->processCRUDs($dataArray, $ExtContras::TABLE_ECINVOICES, $ExtContras::CTRL_INVOICES_INVOICE_NUM,
                                                    'invoiceWebForm', true, $chkUniqArray,
                                                    'Invoice');
            die($showResult);
        }

        if (ubRouting::checkPost($ExtContras::ROUTE_CONTRAS_ACTS)) {
            $dataArray = array($ExtContras::DBFLD_EXTCONTRAS_PROFILE_ID  => ubRouting::post($ExtContras::CTRL_EXTCONTRAS_PROFILE_ID),
                               $ExtContras::DBFLD_EXTCONTRAS_CONTRACT_ID => ubRouting::post($ExtContras::CTRL_EXTCONTRAS_CONTRACT_ID),
                               $ExtContras::DBFLD_EXTCONTRAS_ADDRESS_ID  => ubRouting::post($ExtContras::CTRL_EXTCONTRAS_ADDRESS_ID),
                               $ExtContras::DBFLD_EXTCONTRAS_PERIOD_ID   => ubRouting::post($ExtContras::CTRL_EXTCONTRAS_PERIOD_ID),
                               $ExtContras::DBFLD_EXTCONTRAS_PAYDAY      => ubRouting::post($ExtContras::CTRL_EXTCONTRAS_PAYDAY)
                            );

            //$chkUniqArray = $ExtContras->createCheckUniquenessArray($ExtContras::DBFLD_INVOICES_INVOICE_NUM, '=',
            //                                                        ubRouting::post($ExtContras::CTRL_INVOICES_INVOICE_NUM));

            $showResult = $ExtContras->processCRUDs($dataArray, $ExtContras::TABLE_EXTCONTRAS, $ExtContras::CTRL_EXTCONTRAS_PAYDAY,
                                                    'extcontrasWebForm', false, array(),
                                                    'External counterparty');
            die($showResult);
        }
    } else {
        show_warning(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>