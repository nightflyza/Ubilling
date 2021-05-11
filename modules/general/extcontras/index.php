<?php
if (cfr('EXTCONTRAS')) {
    if ($ubillingConfig->getAlterParam('EXTCONTRAS_FINANCE_ON')) {
        $ExtContras = new ExtContras();

        show_window(__('External counterparties: finances'), $ExtContras->renderMainControls());
file_put_contents('zxcv', '');
file_put_contents('axcv', '');
        if (ubRouting::checkGet($ExtContras::ROUTE_PROFILE_JSON)){
            $ExtContras->profileRenderListJSON();
        }

        if (ubRouting::checkGet($ExtContras::ROUTE_CONTRACT_JSON)) {
            $ExtContras->contractRenderListJSON();
        }

        if (ubRouting::checkGet($ExtContras::ROUTE_PERIOD_JSON)){
            $ExtContras->periodRenderListJSON();
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

        if (ubRouting::checkGet($ExtContras::URL_DICTPERIODS)) {
            show_window(__('Periods dictionary'),
                        $ExtContras->periodWebForm(false)
                        . wf_delimiter() . $ExtContras->periodRenderJQDT()
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
    } else {
        show_warning(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>