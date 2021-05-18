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

        if (ubRouting::checkGet($ExtContras::URL_DICTPROFILES)) {
            show_window(__('Counterparties profiles dictionary'),
                        $ExtContras->profileWebForm(false)
                        . wf_delimiter() . $ExtContras->profileRenderJQDT()
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

            $showResult = $ExtContras->processCRUDs('profileWebForm', $dataArray,'Profile',
                                                    $ExtContras::CTRL_PROFILE_NAME,
                                                    $ExtContras::TABLE_ECPROFILES,
                                                    $ExtContras::DBFLD_PROFILE_NAME,
                                                    true
                                                   );
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

            $showResult = $ExtContras->processCRUDs('contractWebForm', $dataArray,'Contract',
                                                    $ExtContras::CTRL_CTRCT_CONTRACT,
                                                    $ExtContras::TABLE_ECCONTRACTS,
                                                    $ExtContras::DBFLD_CTRCT_CONTRACT,
                                                    true
                                                   );
            die($showResult);
        }

        if (ubRouting::checkPost($ExtContras::ROUTE_ADDRESS_ACTS)) {
            $dataArray = array($ExtContras::DBFLD_ADDRESS_ADDR      => ubRouting::post($ExtContras::CTRL_ADDRESS_ADDR),
                               $ExtContras::DBFLD_ADDRESS_SUM       => ubRouting::post($ExtContras::CTRL_ADDRESS_SUM),
                               $ExtContras::DBFLD_ADDRESS_CTNOTES   => ubRouting::post($ExtContras::CTRL_ADDRESS_CTNOTES),
                               $ExtContras::DBFLD_ADDRESS_NOTES     => ubRouting::post($ExtContras::CTRL_ADDRESS_NOTES)
                            );

            $showResult = $ExtContras->processCRUDs('addressWebForm', $dataArray,'Address',
                                                    $ExtContras::CTRL_ADDRESS_ADDR,
                                                    $ExtContras::TABLE_ECADDRESS,
                                                    $ExtContras::DBFLD_ADDRESS_ADDR,
                                                    true
                                                   );
            die($showResult);
        }

        if (ubRouting::checkPost($ExtContras::ROUTE_PERIOD_ACTS)) {
            $dataArray = array($ExtContras::DBFLD_PERIOD_NAME => ubRouting::post($ExtContras::CTRL_PERIOD_NAME));

            $showResult = $ExtContras->processCRUDs('periodWebForm', $dataArray, 'Period',
                                                    $ExtContras::CTRL_PERIOD_NAME,
                                                    $ExtContras::TABLE_ECPERIODS,
                                                    $ExtContras::DBFLD_PERIOD_NAME,
                                                    true
                                                   );
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

            $showResult = $ExtContras->processCRUDs('invoiceWebForm', $dataArray,'Invoice',
                                                    $ExtContras::CTRL_INVOICES_INVOICE_NUM,
                                                    $ExtContras::TABLE_ECINVOICES,
                                                    $ExtContras::DBFLD_INVOICES_INVOICE_NUM,
                                                    true
                                                   );
            die($showResult);
        }
    } else {
        show_warning(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>