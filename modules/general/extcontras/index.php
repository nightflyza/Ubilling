<?php
if (cfr('EXTCONTRAS')) {
    if ($ubillingConfig->getAlterParam('EXTCONTRAS_FINANCE_ON')) {
        $ExtContras = new ExtContras();

        show_window(__('External counterparties: finances'), $ExtContras->renderMainControls());

        if (ubRouting::checkPost($ExtContras::ROUTE_FORCECACHE_UPD)) {
            $ExtContras->refreshCacheForced();
            die($ExtContras->renderWebMsg(__('Info'), __('Cache data updated succesfuly'), 'info'));
        }

        if (ubRouting::checkGet($ExtContras::ROUTE_CONTRAS_JSON)){
            $whereRaw = '';

            if (ubRouting::checkPost($ExtContras::MISC_WEBFILTER_DATE_START)) {
                $whereRaw.= "`" . $ExtContras::TABLE_ECCONTRACTS . '`.`' . $ExtContras::DBFLD_CTRCT_DTSTART . "` >= '" . ubRouting::post($ExtContras::MISC_WEBFILTER_DATE_START) . "'";
            }

            if (ubRouting::checkPost($ExtContras::MISC_WEBFILTER_DATE_END)) {
                $whereRaw.= (empty($whereRaw) ? '' : ' AND ');
                $whereRaw.= "`" . $ExtContras::TABLE_ECCONTRACTS . '`.`' . $ExtContras::DBFLD_CTRCT_DTSTART . "` <= '" . ubRouting::post($ExtContras::MISC_WEBFILTER_DATE_END) . "' + INTERVAL 1 DAY";
            }

            if (ubRouting::checkPost($ExtContras::MISC_WEBFILTER_PAYDAY)) {
                $whereRaw.= (empty($whereRaw) ? '' : ' AND ');
                $whereRaw.= "`" . $ExtContras::TABLE_EXTCONTRAS  . '`.`' . $ExtContras::DBFLD_EXTCONTRAS_PAYDAY . "` = " . ubRouting::post($ExtContras::MISC_WEBFILTER_PAYDAY);
            }

            $ExtContras->extcontrasRenderListJSON($whereRaw);
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
            $whereRaw = '';

            if (ubRouting::checkPost($ExtContras::MISC_WEBFILTER_DATE_START)) {
                $whereRaw.= "`" . $ExtContras::DBFLD_INVOICES_DATE . "` >= '" . ubRouting::post($ExtContras::MISC_WEBFILTER_DATE_START) . "'";
            }

            if (ubRouting::checkPost($ExtContras::MISC_WEBFILTER_DATE_END)) {
                $whereRaw.= (empty($whereRaw) ? '' : ' AND ');
                $whereRaw.= "`" . $ExtContras::DBFLD_INVOICES_DATE . "` <= '" . ubRouting::post($ExtContras::MISC_WEBFILTER_DATE_END) . "' + INTERVAL 1 DAY";
            }

            $ExtContras->invoiceRenderListJSON($whereRaw);
        }


        if (ubRouting::checkGet($ExtContras::ROUTE_2LVL_CNTRCTS_DETAIL)) {
            if (ubRouting::checkPost($ExtContras::DBFLD_EXTCONTRAS_PROFILE_ID)) {
                $detailsFilter = '&' . $ExtContras::DBFLD_EXTCONTRAS_PROFILE_ID . '=' . ubRouting::post($ExtContras::DBFLD_EXTCONTRAS_PROFILE_ID);
                die($ExtContras->ecRender2ndLvlContractsJQDT('', ubRouting::get($ExtContras::MISC_MARKROW_URL), $detailsFilter, false)
                    . wf_delimiter(0));
            }
        }

        if (ubRouting::checkGet($ExtContras::ROUTE_2LVL_CNTRCTS_JSON)) {
            $whereRaw = '';

            if (ubRouting::checkGet($ExtContras::DBFLD_EXTCONTRAS_PROFILE_ID)) {
                $whereRaw.= "`" . $ExtContras::TABLE_EXTCONTRAS . "`.`" . $ExtContras::DBFLD_EXTCONTRAS_PROFILE_ID . "` = " . ubRouting::get($ExtContras::DBFLD_EXTCONTRAS_PROFILE_ID);
            }

            $ExtContras->ecRender2ndLvlContractsListJSON($whereRaw);
        }


        if (ubRouting::checkGet($ExtContras::ROUTE_FINOPS_DETAILS_CNTRCTS)) {
            if (ubRouting::checkPost($ExtContras::DBFLD_COMMON_ID)) {
                $detailsFilterFinops = '&' . $ExtContras::DBFLD_COMMON_ID . '=' . ubRouting::post($ExtContras::DBFLD_COMMON_ID)
                                       . '&' . $ExtContras::DBFLD_EXTCONTRAS_CONTRACT_ID . '=' . ubRouting::post($ExtContras::DBFLD_EXTCONTRAS_CONTRACT_ID);
                $detailsFilterAddr   = $detailsFilterFinops . '&' . $ExtContras::DBFLD_EXTCONTRAS_ADDRESS_ID . '=' . ubRouting::post($ExtContras::DBFLD_EXTCONTRAS_ADDRESS_ID);

                die(wf_Plate(wf_tag('h3', false, 'glamour', 'style="margin-top: 10px; width: 95%;"') . __('Addresses') . wf_tag('h3', true)
                    . $ExtContras->ecRender2ndLvlAddressJQDT('', ubRouting::get($ExtContras::MISC_MARKROW_URL), $detailsFilterAddr, false))
                    . wf_Plate(wf_tag('h3', false, 'glamour', 'style="margin-top: 25px; width: 95%;"') . __('Financial operations') . wf_tag('h3', true)
                    . $ExtContras->finopsRenderJQDT('', ubRouting::get($ExtContras::MISC_MARKROW_URL), $detailsFilterFinops, false))
                    . wf_CleanDiv() . wf_delimiter(0));
            }
        }


        if (ubRouting::checkGet($ExtContras::ROUTE_3LVL_ADDR_JSON)) {
            $whereRaw = '';

            if (ubRouting::checkGet($ExtContras::DBFLD_COMMON_ID)) {

                $whereRaw.= "`" . $ExtContras::TABLE_EXTCONTRAS . "`.`" . $ExtContras::DBFLD_EXTCONTRAS_PROFILE_ID . "` = " . ubRouting::get($ExtContras::DBFLD_COMMON_ID)
                            . " AND `" . $ExtContras::TABLE_EXTCONTRAS . "`.`" . $ExtContras::DBFLD_EXTCONTRAS_CONTRACT_ID . "` = " . ubRouting::get($ExtContras::DBFLD_EXTCONTRAS_CONTRACT_ID)
                            . " AND `" . $ExtContras::TABLE_EXTCONTRAS . "`.`" . $ExtContras::DBFLD_EXTCONTRAS_ADDRESS_ID . "` = " . ubRouting::get($ExtContras::DBFLD_EXTCONTRAS_ADDRESS_ID);
            }

            $ExtContras->ecRender2ndLvlAddressListJSON($whereRaw);
        }

        if (ubRouting::checkGet($ExtContras::ROUTE_FINOPS_DETAILS_ADDRESS)) {
            if (ubRouting::checkPost($ExtContras::DBFLD_COMMON_ID)) {
                $detailsFilterFinops = '&' . $ExtContras::DBFLD_COMMON_ID . '=' . ubRouting::post($ExtContras::DBFLD_COMMON_ID)
                                       . '&' . $ExtContras::DBFLD_EXTCONTRAS_CONTRACT_ID . '=' . ubRouting::post($ExtContras::DBFLD_EXTCONTRAS_CONTRACT_ID)
                                       . '&' . $ExtContras::DBFLD_EXTCONTRAS_ADDRESS_ID . '=' . ubRouting::post($ExtContras::DBFLD_EXTCONTRAS_ADDRESS_ID);

                die(wf_Plate(wf_tag('h3', false, 'glamour', 'style="margin-top: 25px; width: 95%;"') . __('Financial operations') . wf_tag('h3', true)
                    . $ExtContras->finopsRenderJQDT('', ubRouting::get($ExtContras::MISC_MARKROW_URL), $detailsFilterFinops, false))
                    . wf_CleanDiv() . wf_delimiter(0));
            }
        }


        if (ubRouting::checkGet($ExtContras::ROUTE_FINOPS_JSON)) {
            $whereRaw = '';

            if (ubRouting::checkGet($ExtContras::DBFLD_COMMON_ID)) {
                if (ubRouting::checkGet($ExtContras::DBFLD_EXTCONTRAS_CONTRACT_ID)
                    and ! ubRouting::checkGet($ExtContras::DBFLD_EXTCONTRAS_ADDRESS_ID)) {

                    $whereRaw .= "`" . $ExtContras::TABLE_ECMONEY . "`.`"
                                 . $ExtContras::DBFLD_MONEY_PROFILEID . "` = " . ubRouting::get($ExtContras::DBFLD_COMMON_ID)
                                 . " AND `" . $ExtContras::TABLE_ECMONEY . "`.`"
                                 . $ExtContras::DBFLD_EXTCONTRAS_CONTRACT_ID . "` = " . ubRouting::get($ExtContras::DBFLD_EXTCONTRAS_CONTRACT_ID);

                } elseif (ubRouting::checkGet($ExtContras::DBFLD_EXTCONTRAS_CONTRACT_ID)
                          and ubRouting::checkGet($ExtContras::DBFLD_EXTCONTRAS_ADDRESS_ID)) {

                    $whereRaw .= "`" . $ExtContras::TABLE_ECMONEY . "`.`"
                                 . $ExtContras::DBFLD_MONEY_PROFILEID . "` = " . ubRouting::get($ExtContras::DBFLD_COMMON_ID)
                                 . " AND `" . $ExtContras::TABLE_ECMONEY . "`.`"
                                 . $ExtContras::DBFLD_EXTCONTRAS_CONTRACT_ID . "` = " . ubRouting::get($ExtContras::DBFLD_EXTCONTRAS_CONTRACT_ID)
                                 . " AND `" . $ExtContras::TABLE_ECMONEY . "`.`"
                                 . $ExtContras::DBFLD_EXTCONTRAS_ADDRESS_ID . "` = " . ubRouting::get($ExtContras::DBFLD_EXTCONTRAS_ADDRESS_ID);
                }
            } else {
                if (ubRouting::checkPost($ExtContras::MISC_WEBFILTER_DATE_START)) {
                    $whereRaw.= "`" . $ExtContras::DBFLD_MONEY_DATE . "` >= '" . ubRouting::post($ExtContras::MISC_WEBFILTER_DATE_START) . "'";
                }

                if (ubRouting::checkPost($ExtContras::MISC_WEBFILTER_DATE_END)) {
                    $whereRaw.= (empty($whereRaw) ? '' : ' AND ');
                    $whereRaw.= "`" . $ExtContras::DBFLD_MONEY_DATE . "` <= '" . ubRouting::post($ExtContras::MISC_WEBFILTER_DATE_END) . "' + INTERVAL 1 DAY";
                }
            }

            $ExtContras->finopsRenderListJSON($whereRaw);
        }


        if (ubRouting::checkPost($ExtContras::URL_EXTCONTRAS_COLORS)) {
            $ExtContras->setTableGridColorOpts();
        }

        if (ubRouting::checkGet($ExtContras::URL_EXTCONTRAS_COLORS)) {
            show_window(__('Counterparties table coloring settings'), $ExtContras->extcontrasColorSettings());
        }

        if (ubRouting::checkGet($ExtContras::URL_EXTCONTRAS)) {
            show_window(__('Counterparties list') . wf_nbsp(4)
                        . wf_Link($ExtContras::URL_ME . '&' . $ExtContras::URL_EXTCONTRAS_COLORS . '=true',
                                  wf_img_sized('skins/color-picker.png', __('Coloring settings config'),
                                         '22', '22', 'vertical-align: middle;'),
                                  false, 'ubButton', 'style="display: inline; padding: 3px 7px; vertical-align: middle;"'),
                        wf_Plate($ExtContras->extcontrasWebForm(false), '', '', '', 'margin-right: 30px;')
                        . $ExtContras->extcontrasFilterWebForm() . wf_CleanDiv() . wf_delimiter(0)
                        . $ExtContras->extcontrasRenderMainJQDT()
                        );
        }

        if (ubRouting::checkGet($ExtContras::URL_DICTPROFILES)) {
            show_window(__('Counterparties profiles dictionary'),
                        $ExtContras->profileWebForm(false)
                        . wf_delimiter() . $ExtContras->profileRenderJQDT('', ubRouting::get($ExtContras::MISC_MARKROW_URL))
                       );
        }

        if (ubRouting::checkGet($ExtContras::URL_DICTCONTRACTS)) {
            show_window(__('Counterparties contracts dictionary'),
                        $ExtContras->contractWebForm(false)
                        . wf_delimiter() . $ExtContras->contractRenderJQDT('', ubRouting::get($ExtContras::MISC_MARKROW_URL))
                       );
        }

        if (ubRouting::checkGet($ExtContras::URL_DICTADDRESS)) {
            show_window(__('Counterparties addresses dictionary'),
                        $ExtContras->addressWebForm(false)
                        . wf_delimiter() . $ExtContras->addressRenderJQDT('', ubRouting::get($ExtContras::MISC_MARKROW_URL))
                        );
        }

        if (ubRouting::checkGet($ExtContras::URL_DICTPERIODS)) {
            show_window(__('Periods dictionary'),
                        $ExtContras->periodWebForm(false)
                        . wf_delimiter() . $ExtContras->periodRenderJQDT('', ubRouting::get($ExtContras::MISC_MARKROW_URL))
                       );
        }

        if (ubRouting::checkGet($ExtContras::URL_INVOICES)) {
            show_window(__('Invoices'),
                        wf_Plate($ExtContras->invoiceWebForm(false), '', '', '', 'margin-right: 30px;')
                        . $ExtContras->invoiceFilterWebForm() . wf_CleanDiv() . wf_delimiter(0)
                        . $ExtContras->invoiceRenderJQDT('', ubRouting::get($ExtContras::MISC_MARKROW_URL))
                        );
        }

        if (ubRouting::checkGet($ExtContras::URL_FINOPERATIONS)) {
            show_window(__('Financial operations'),
                wf_Plate($ExtContras->finopsWebForm(false), '', '', '', 'margin-right: 30px;')
                . $ExtContras->finopsFilterWebForm() . wf_CleanDiv() . wf_delimiter(0)
                . $ExtContras->finopsRenderJQDT('', ubRouting::get($ExtContras::MISC_MARKROW_URL))
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

        if (ubRouting::checkPost($ExtContras::ROUTE_FINOPS_ACTS)) {
            $moneyDateValue = curdatetime();

            if (ubRouting::checkPost($ExtContras::ROUTE_ACTION_CREATE)) {
                $moneyDateField = $ExtContras::DBFLD_MONEY_DATE;
            } else {
                $moneyDateField = $ExtContras::DBFLD_MONEY_DATE_EDIT;
            }

            if (ubRouting::checkPost($ExtContras::ROUTE_ACTION_PREFILL)) {
                $prefillData = ubRouting::post($ExtContras::MISC_PREFILL_DATA);
                $createModality = true;
            } else {
                $prefillData = array();
                $createModality = false;
            }

            $finopIncoming = (ubRouting::post($ExtContras::CTRL_MONEY_INOUT) == 'incoming') ? 1 : 0;
            $finopOutgoing = (ubRouting::post($ExtContras::CTRL_MONEY_INOUT) == 'outgoing') ? 1 : 0;

            $dataArray = array($ExtContras::DBFLD_MONEY_PROFILEID   => ubRouting::post($ExtContras::CTRL_MONEY_PROFILEID),
                               $ExtContras::DBFLD_MONEY_CNTRCTID    => ubRouting::post($ExtContras::CTRL_MONEY_CNTRCTID),
                               $ExtContras::DBFLD_MONEY_ADDRESSID   => ubRouting::post($ExtContras::CTRL_MONEY_ADDRESSID),
                               $ExtContras::DBFLD_MONEY_INVOICEID   => ubRouting::post($ExtContras::CTRL_MONEY_INVOICEID),
                               $ExtContras::DBFLD_MONEY_ACCRUALID   => ubRouting::post($ExtContras::CTRL_MONEY_ACCRUALID),
                               $ExtContras::DBFLD_MONEY_PURPOSE     => ubRouting::post($ExtContras::CTRL_MONEY_PURPOSE),
                               $ExtContras::DBFLD_MONEY_SMACCRUAL   => ubRouting::post($ExtContras::CTRL_MONEY_SUMACCRUAL),
                               $ExtContras::DBFLD_MONEY_SMPAYMENT   => ubRouting::post($ExtContras::CTRL_MONEY_SUMPAYMENT),
                               $ExtContras::DBFLD_MONEY_PAYNOTES    => ubRouting::post($ExtContras::CTRL_MONEY_PAYNOTES),
                               $ExtContras::DBFLD_MONEY_INCOMING    => $finopIncoming,
                               $ExtContras::DBFLD_MONEY_OUTGOING    => $finopOutgoing,
                               $moneyDateField => $moneyDateValue
                              );

            $showResult = $ExtContras->processCRUDs($dataArray, $ExtContras::TABLE_ECMONEY, $ExtContras::CTRL_MONEY_PURPOSE,
                'finopsWebForm', false, array(),
                'Financial operation', $prefillData, $createModality);
            die($showResult);
        }
    } else {
        show_warning(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>