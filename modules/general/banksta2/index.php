<?php
if (cfr('BANKSTA2')) {
    if ($ubillingConfig->getAlterParam('BANKSTA2_ENABLED')) {
        show_window(__('Banksta processing'), Banksta2::web_MainButtonsControls());

        if (ubRouting::checkGet('bankstalist')) {
            show_window(__('Previously loaded bank statements'), Banksta2::renderBStatementsJQDT());
        }

        zb_BillingStats(true);

        if (ubRouting::checkGet('fmpajax')) {
            Banksta2::renderFMPListJSON();
        }

        if (ubRouting::checkGet('bslistajax')) {
            Banksta2::renderBStatementsListJSON();
        }

        if (ubRouting::checkGet('presets')) {
            Banksta2::web_FMPForm();
        }

        $Banksta = new Banksta2;

        if (ubRouting::checkGet('refreshfmpselector')) {
            die($Banksta->getMappingPresetsSelector(ubRouting::get('fmpselectorid'), ubRouting::get('fmpselectorclass')));
        }

        if (ubRouting::checkGet('showhash')) {
            if (ubRouting::checkPost('bankstaeditrowid')){

                if (ubRouting::checkPost('recallrowprocessing')) {
                    $Banksta->setBankstaRecUnCanceled(ubRouting::post('bankstaeditrowid'));
                }

                if (ubRouting::checkPost('cancelrowprocessing')) {
                    $Banksta->setBankstaRecCanceled(ubRouting::post('bankstaeditrowid'));
                }

                if (ubRouting::checkPost('newbankstacontract')) {
                    $Banksta->setBankstaRecContract(ubRouting::post('bankstaeditrowid'), ubRouting::post('newbankstacontract'));
                }

                if (ubRouting::checkPost('newbankstarvtype')) {
                    $Banksta->setBankstaRecSrvType(ubRouting::post('bankstaeditrowid'), ubRouting::post('newbankstarvtype'));
                }

                $Banksta->getProcessedBSRecsCached(true);
                $Banksta->getUsersDataCached(true);
            }

            if (ubRouting::checkPost('bankstaneedpaymentspush')) {
                $Banksta->pushStatementPayments(ubRouting::post('bankstaneedpaymentspush'), wf_getBoolFromVar(ubRouting::post('bankstaneedrefiscalize'), true));
            }

            $fileInfoArray = $Banksta->getFileInfoByHash(ubRouting::get('showhash'));
            $fileInfo = __('Date') . ': ' . $fileInfoArray['date'] . wf_nbsp(2) . '|' . wf_nbsp(2) .
                        __('Filename') . ': ' . $fileInfoArray['filename'] . wf_nbsp(2) . '|' . wf_nbsp(2) .
                        __('Total rows') . ': ' . $fileInfoArray['rowcount'] . wf_nbsp(2) . '|' . wf_nbsp(2) .
                        __('Admin') . ': ' . $fileInfoArray['admin'];

            /*            __('Processed rows') . ': ' . $fileInfoArray['processed_cnt'] . wf_nbsp(2) . '|' . wf_nbsp(2) .
                        __('Canceled rows') . ': ' . $fileInfoArray['canceled_cnt'] . wf_nbsp(2) . '|' . wf_nbsp(2) .*/

            show_window($fileInfo, $Banksta->web_BSProcessingForm(ubRouting::get('showhash')));
        }

        if (ubRouting::checkGet('showdetailed')) {
            $windowContent = wf_tag('pre', false, 'floatpanelswide', 'style="width: 90%; padding: 10px 15px;"');
            $windowContent.= print_r($Banksta->getBankstaRecDetails(ubRouting::get('showdetailed')), true);
            $windowContent.= wf_tag('pre', true);

            die(wf_modalAutoForm(__('Detailed info'), $windowContent, ubRouting::get('detailsWinID'), '', true));
        }

        if (ubRouting::checkPost('delStatement') and ubRouting::checkPost('hash')) {
            $Banksta->deleteBankStatement(ubRouting::post('hash'));
            die();
        }

        // create fields mapping preset
        if (ubRouting::checkPost('fmpcreate')) {
            if (ubRouting::checkPost('fmpname')) {
                $newFMPName = ubRouting::post('fmpname');
                $foundId = $Banksta->checkFMPNameExists($newFMPName);

                if (empty($foundId)) {
                    if (ubRouting::checkGet('fmpquickadd')) {
                        $Banksta->addFieldsMappingPreset($newFMPName,
                                                         ubRouting::post('bsrealname_col', 'int'), ubRouting::post('bsaddress_col', 'int'), ubRouting::post('bspaysum_col', 'int'),
                                                         ubRouting::post('bspaypurpose_col', 'int'), ubRouting::post('bspaydate_col', 'int'), ubRouting::post('bspaytime_col', 'int'),
                                                         ubRouting::post('bscontract_col', 'int'),
                                                         (ubRouting::checkPost('bspaymincoins') ? 1 : 0),
                                                         (ubRouting::checkPost('bstryguesscontract') ? 1 : 0),
                                                         ubRouting::post('bscontractdelimstart', 'mres'), ubRouting::post('bscontractdelimend', 'mres'),
                                                         ubRouting::post('bscontractminlen', 'int'), ubRouting::post('bscontractmaxlen', 'int'),
                                                         ubRouting::post('bssrvtype'),
                                                         ubRouting::post('bsinetdelimstart', 'mres'), ubRouting::post('bsinetdelimend', 'mres'),
                                                         ubRouting::post('bsinetkeywords', 'mres'), (ubRouting::checkPost('bsinetkeywordsnoesc') ? 1 : 0),
                                                         ubRouting::post('bsukvdelimstart', 'mres'), ubRouting::post('bsukvdelimend', 'mres'),
                                                         ubRouting::post('bsukvkeywords', 'mres'), (ubRouting::checkPost('bsukvkeywordsnoesc') ? 1 : 0),
                                                         (ubRouting::checkPost('bsskiprow') ? 1 : 0),
                                                         ubRouting::post('bsskiprow_col', 'int'), ubRouting::post('bsskiprowkeywords', 'mres'),
                                                         (ubRouting::checkPost('bsskiprowkeywordsnoesc') ? 1 : 0),
                                                         (ubRouting::checkPost('bsreplacestrs') ? 1 : 0),
                                                         ubRouting::post('bscolsreplacestrs', 'int'), ubRouting::post('bsstrstoreplace', 'mres'),
                                                         ubRouting::post('bsstrstoreplacewith', 'mres'), ubRouting::post('bsreplacementscnt', 'int'),
                                                         (ubRouting::checkPost('bsreplacekeywordsnoesc') ? 1 : 0),
                                                         (ubRouting::checkPost('bsremovestrs') ? 1 : 0),
                                                         ubRouting::post('bscolremovestrs', 'int'), ubRouting::post('bsstrstoremove', 'mres'),
                                                         (ubRouting::checkPost('bsremovekeywordsnoesc') ? 1 : 0),
                                                         ubRouting::post('bspaymtypeid', 'int'), ubRouting::post('bssrvidents_col', 'int'),
                                                         (ubRouting::checkPost('bssrvidentspreff') ? 1 : 0)
                        );
                    }
                    else {
                        $Banksta->addFieldsMappingPreset($newFMPName,
                                                         ubRouting::post('fmpcolrealname', 'int'), ubRouting::post('fmpcoladdr', 'int'), ubRouting::post('fmpcolpaysum', 'int'),
                                                         ubRouting::post('fmpcolpaypurpose', 'int'), ubRouting::post('fmpcolpaydate', 'int'), ubRouting::post('fmpcolpaytime', 'int'),
                                                         ubRouting::post('fmpcolcontract', 'int'),
                                                         (ubRouting::checkPost('fmppaymincoins') ? 1 : 0),
                                                         (ubRouting::checkPost('fmptryguesscontract') ? 1 : 0),
                                                         ubRouting::post('fmpcontractdelimstart', 'mres'), ubRouting::post('fmpcontractdelimend', 'mres'),
                                                         ubRouting::post('fmpcontractminlen', 'int'), ubRouting::post('fmpcontractmaxlen', 'int'),
                                                         ubRouting::post('fmpsrvtype'),
                                                         ubRouting::post('fmpinetdelimstart', 'mres'), ubRouting::post('fmpinetdelimend', 'mres'),
                                                         ubRouting::post('fmpinetkeywords', 'mres'), (ubRouting::checkPost('fmpinetkeywordsnoesc') ? 1 : 0),
                                                         ubRouting::post('fmpukvdelimstart', 'mres'), ubRouting::post('fmpukvdelimend', 'mres'),
                                                         ubRouting::post('fmpukvkeywords', 'mres'), (ubRouting::checkPost('fmpukvkeywordsnoesc') ? 1 : 0),
                                                         (ubRouting::checkPost('fmpskiprow') ? 1 : 0),
                                                         ubRouting::post('fmpcolskiprow', 'int'), ubRouting::post('fmpskiprowkeywords', 'mres'),
                                                         (ubRouting::checkPost('fmpskiprokeywordsnoesc') ? 1 : 0),
                                                         (ubRouting::checkPost('fmpreplacestrs') ? 1 : 0),
                                                         ubRouting::post('fmpcolsreplacestrs', 'int'), ubRouting::post('fmpstrstoreplace', 'mres'),
                                                         ubRouting::post('fmpstrstoreplacewith', 'mres'), ubRouting::post('fmpstrsreplacecount', 'int'),
                                                         (ubRouting::checkPost('fmpreplacekeywordsnoesc') ? 1 : 0),
                                                         (ubRouting::checkPost('fmpremovestrs') ? 1 : 0),
                                                         ubRouting::post('fmpcolsremovestrs', 'int'), ubRouting::post('fmpstrstoremove', 'mres'),
                                                         (ubRouting::checkPost('fmpremovekeywordsnoesc') ? 1 : 0),
                                                         ubRouting::post('fmppaymtypeid', 'int'), ubRouting::post('fmpcolsrvidents', 'int'),
                                                         (ubRouting::checkPost('fmpsrvidentspreff') ? 1 : 0)
                        );
                    }
                    die();
                } else {
                    $errormes = $Banksta->getUbMsgHelperInstance()->getStyledMessage(__('Preset with such name already exists with ID: ') . $foundId,
                                                                                        'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                    die(wf_modalAutoForm(__('Error'), $errormes, ubRouting::post('errfrmid'), '', true));
                }
            }

            die(wf_modalAutoForm(__('Add fields mapping preset'), $Banksta->renderFMPAddForm(ubRouting::post('modalWindowId')), ubRouting::post('modalWindowId'), ubRouting::post('modalWindowBodyId'), true));
        }

        // manipulate fields mapping preset
        if (ubRouting::checkPost('fmpid')) {
            $fmpID = ubRouting::post('fmpid');
            $fmpEdit = ubRouting::post('fmpedit');
            $fmpClone = ubRouting::post('fmpclone');

            // edit/clone fields mapping preset
            if ($fmpEdit or $fmpClone) {
                 if (ubRouting::checkPost('fmpname')) {
                     $newFMPName = ubRouting::post('fmpname');
                     $foundId = ($fmpClone) ? $Banksta->checkFMPNameExists($newFMPName) : $Banksta->checkFMPNameExists($newFMPName, $fmpID);

                     if (empty($foundId)) {
                         if ($fmpEdit) {
                             $Banksta->editFieldsMappingPreset($fmpID, $newFMPName,
                                                               ubRouting::post('fmpcolrealname', 'int'), ubRouting::post('fmpcoladdr', 'int'), ubRouting::post('fmpcolpaysum', 'int'),
                                                               ubRouting::post('fmpcolpaypurpose', 'int'), ubRouting::post('fmpcolpaydate', 'int'), ubRouting::post('fmpcolpaytime', 'int'),
                                                               ubRouting::post('fmpcolcontract', 'int'),
                                                               (ubRouting::checkPost('fmppaymincoins') ? 1 : 0),
                                                               (ubRouting::checkPost('fmptryguesscontract') ? 1 : 0),
                                                               ubRouting::post('fmpcontractdelimstart', 'mres'), ubRouting::post('fmpcontractdelimend', 'mres'),
                                                               ubRouting::post('fmpcontractminlen', 'int'), ubRouting::post('fmpcontractmaxlen', 'int'),
                                                               ubRouting::post('fmpsrvtype'),
                                                               ubRouting::post('fmpinetdelimstart', 'mres'), ubRouting::post('fmpinetdelimend', 'mres'),
                                                               ubRouting::post('fmpinetkeywords', 'mres'), (ubRouting::checkPost('fmpinetkeywordsnoesc') ? 1 : 0),
                                                               ubRouting::post('fmpukvdelimstart', 'mres'), ubRouting::post('fmpukvdelimend', 'mres'),
                                                               ubRouting::post('fmpukvkeywords', 'mres'), (ubRouting::checkPost('fmpukvkeywordsnoesc') ? 1 : 0),
                                                               (ubRouting::checkPost('fmpskiprow') ? 1 : 0),
                                                               ubRouting::post('fmpcolskiprow', 'int'), ubRouting::post('fmpskiprowkeywords', 'mres'),
                                                               (ubRouting::checkPost('fmpskiprokeywordsnoesc') ? 1 : 0),
                                                               (ubRouting::checkPost('fmpreplacestrs') ? 1 : 0),
                                                               ubRouting::post('fmpcolsreplacestrs', 'int'), ubRouting::post('fmpstrstoreplace', 'mres'),
                                                               ubRouting::post('fmpstrstoreplacewith', 'mres'), ubRouting::post('fmpstrsreplacecount', 'int'),
                                                               (ubRouting::checkPost('fmpreplacekeywordsnoesc') ? 1 : 0),
                                                               (ubRouting::checkPost('fmpremovestrs') ? 1 : 0),
                                                               ubRouting::post('fmpcolsremovestrs', 'int'), ubRouting::post('fmpstrstoremove', 'mres'),
                                                               (ubRouting::checkPost('fmpremovekeywordsnoesc') ? 1 : 0),
                                                               ubRouting::post('fmppaymtypeid', 'int'), ubRouting::post('fmpcolsrvidents', 'int'),
                                                               (ubRouting::checkPost('fmpsrvidentspreff') ? 1 : 0)
                             );
                         } elseif ($fmpClone) {
                             $Banksta->addFieldsMappingPreset($newFMPName,
                                                              ubRouting::post('fmpcolrealname', 'int'), ubRouting::post('fmpcoladdr', 'int'), ubRouting::post('fmpcolpaysum', 'int'),
                                                              ubRouting::post('fmpcolpaypurpose', 'int'), ubRouting::post('fmpcolpaydate', 'int'), ubRouting::post('fmpcolpaytime', 'int'),
                                                              ubRouting::post('fmpcolcontract', 'int'),
                                                              (ubRouting::checkPost('fmppaymincoins') ? 1 : 0),
                                                              (ubRouting::checkPost('fmptryguesscontract') ? 1 : 0),
                                                              ubRouting::post('fmpcontractdelimstart', 'mres'), ubRouting::post('fmpcontractdelimend', 'mres'),
                                                              ubRouting::post('fmpcontractminlen', 'int'), ubRouting::post('fmpcontractmaxlen', 'int'),
                                                              ubRouting::post('fmpsrvtype'),
                                                              ubRouting::post('fmpinetdelimstart', 'mres'), ubRouting::post('fmpinetdelimend', 'mres'),
                                                              ubRouting::post('fmpinetkeywords', 'mres'), (ubRouting::checkPost('fmpinetkeywordsnoesc') ? 1 : 0),
                                                              ubRouting::post('fmpukvdelimstart', 'mres'), ubRouting::post('fmpukvdelimend', 'mres'),
                                                              ubRouting::post('fmpukvkeywords', 'mres'), (ubRouting::checkPost('fmpukvkeywordsnoesc') ? 1 : 0),
                                                              (ubRouting::checkPost('fmpskiprow') ? 1 : 0),
                                                              ubRouting::post('fmpcolskiprow', 'int'), ubRouting::post('fmpskiprowkeywords', 'mres'),
                                                              (ubRouting::checkPost('fmpskiprokeywordsnoesc') ? 1 : 0),
                                                              (ubRouting::checkPost('fmpreplacestrs') ? 1 : 0),
                                                              ubRouting::post('fmpcolsreplacestrs', 'int'), ubRouting::post('fmpstrstoreplace', 'mres'),
                                                              ubRouting::post('fmpstrstoreplacewith', 'mres'), ubRouting::post('fmpstrsreplacecount', 'int'),
                                                              (ubRouting::checkPost('fmpreplacekeywordsnoesc') ? 1 : 0),
                                                              (ubRouting::checkPost('fmpremovestrs') ? 1 : 0),
                                                              ubRouting::post('fmpcolsremovestrs', 'int'), ubRouting::post('fmpstrstoremove', 'mres'),
                                                              (ubRouting::checkPost('fmpremovekeywordsnoesc') ? 1 : 0),
                                                              ubRouting::post('fmppaymtypeid', 'int'), ubRouting::post('fmpcolsrvidents', 'int'),
                                                              (ubRouting::checkPost('fmpsrvidentspreff') ? 1 : 0)
                             );
                         }

                         die();
                     } else {
                         $errormes = $Banksta->getUbMsgHelperInstance()->getStyledMessage(__('Preset with such name already exists with ID: ') . $foundId,
                             'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                         die(wf_modalAutoForm(__('Error'), $errormes, ubRouting::post('errfrmid'), '', true));
                     }
                 }

                die(wf_modalAutoForm(__('Edit fields mapping preset'), $Banksta->renderFMPEditForm($fmpID, ubRouting::post('modalWindowId'), $fmpClone), ubRouting::post('modalWindowId'), ubRouting::post('modalWindowBodyId'), true));
            }

            // delete fields mapping preset
            if (ubRouting::checkPost('delFMP')) {
                $Banksta->deleteFieldsMappingPreset($fmpID);
                die();
            }

            // get fields mapping preset data JSON
            if (ubRouting::checkPost('getfmpdata')) {
                die($Banksta->getFMPDataJSON($fmpID, $Banksta->dbPresetsFlds2PreprocForm));
            }
        }

        //show upload form
        if (ubRouting::checkGet('uploadform')) {
            show_window(__('Payments import from bank statement file'), $Banksta->web_FileUploadForm());
        }

        if (ubRouting::checkGet('proceedstatementimport')) {
            $statementFileData = unserialize(base64_decode(zb_StorageGet('BANKSTA2_STATEMENT_FILEDATA')));
            $Banksta->processBankStatement(unserialize(base64_decode(zb_StorageGet('BANKSTA2_STATEMENT_DATA'))), $statementFileData);
            rcms_redirect($Banksta::URL_BANKSTA2_PROCESSING . $statementFileData['hash']);
        }

        if (ubRouting::checkPost('import_rawdata')) {
            $importOptions = array();
            $skipLastChecks = (ubRouting::checkPost('bsskiplastcheck')) ? 1 : 0;

            foreach ($Banksta->dbPresetsFlds2PreprocForm as $item => $value) {
                $importOptions[$item] = (ubRouting::checkPost($value)) ? ubRouting::post($value) : 0;
            }

            $lastCheksData = $Banksta->preprocessBStatement(ubRouting::post('import_rawdata'), $importOptions, $skipLastChecks);

            if (!$skipLastChecks) {
                $Banksta->web_LastChecksForm($lastCheksData);
            } else {
                rcms_redirect($Banksta::URL_BANKSTA2_PROCEED_STMT_IMP);
            }
        }

        if (ubRouting::checkPost('bankstatementuploaded')) {
            //upload file and show preprocessing form
            $importFileData  = $Banksta->uploadFile();
            $delimiter       = ubRouting::post('delimiter');
            $encoding        = ubRouting::post('encoding');
            $useDBFColNames  = (ubRouting::checkPost('usedbfcolnames')) ? ubRouting::post('usedbfcolnames') : false;
            $skipRowsCount   = (ubRouting::checkPost('skiprowscount')) ? ubRouting::post('skiprowscount') : 0;

            $errormes = '';
            $errormes = $Banksta->preprocessImportFile($importFileData['savedname'], $delimiter, $encoding, $useDBFColNames, $skipRowsCount);

            if (empty($errormes)) {
                zb_StorageSet('BANKSTA2_RAWDATA', base64_encode(serialize($Banksta->preprocessedFileData)));
                zb_StorageSet('BANKSTA2_STATEMENT_FILEDATA', base64_encode(serialize($importFileData)));
            } else {
                show_error($errormes);
            }
        }

        if (ubRouting::checkGet('fieldmapping')) {
            $Banksta->web_FieldsMappingForm(zb_StorageGet('BANKSTA2_RAWDATA'));
        }
    } else {
        show_warning(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>