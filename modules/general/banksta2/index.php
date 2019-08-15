<?php
if (cfr('BANKSTA2')) {
    if ($ubillingConfig->getAlterParam('BANKSTA2_ENABLED')) {
        $Banksta = new Banksta2;

        show_window(__('Banksta processing'), $Banksta->web_MainButtonsControls());

        if (wf_CheckGet(array('bankstalist'))) {
            show_window(__('Previously loaded bank statements'), $Banksta->renderBStatementsJQDT());
        }

        if (wf_CheckGet(array('fmpajax'))) {
            $Banksta->renderFMPListJSON();
        }

        if (wf_CheckGet(array('bslistajax'))) {
            $Banksta->renderBStatementsListJSON();
        }

        if (wf_CheckGet(array('presets'))) {
            $Banksta->web_FMPForm();
        }

        if (wf_CheckGet(array('refreshfmpselector'))) {
            die($Banksta->getMappingPresetsSelector($_GET['fmpselectorid'], $_GET['fmpselectorclass']));
        }

        if (wf_CheckGet(array('showhash'))) {
            if (wf_CheckPost(array('bankstaeditrowid'))){

                if (wf_CheckPost(array('recallrowprocessing'))) {
                    $Banksta->setBankstaRecUnCanceled($_POST['bankstaeditrowid']);
                }

                if (wf_CheckPost(array('cancelrowprocessing'))) {
                    $Banksta->setBankstaRecCanceled($_POST['bankstaeditrowid']);
                }

                if (wf_CheckPost(array('newbankstacontract'))) {
                    $Banksta->setBankstaRecContract($_POST['bankstaeditrowid'], $_POST['newbankstacontract']);
                }

                if (wf_CheckPost(array('newbankstarvtype'))) {
                    $Banksta->setBankstaRecSrvType($_POST['bankstaeditrowid'], $_POST['newbankstarvtype']);
                }
            }

            if (wf_CheckPost(array('bankstaneedpaymentspush'))) {
                $Banksta->pushStatementPayments($_POST['bankstaneedpaymentspush'], wf_getBoolFromVar($_POST['bankstaneedrefiscalize'], true));
            }

            $fileInfoArray = $Banksta->getFileInfoByHash($_GET['showhash']);
            $fileInfo = __('Date') . ': ' . $fileInfoArray['date'] . wf_nbsp(2) . '|' . wf_nbsp(2) .
                        __('Filename') . ': ' . $fileInfoArray['filename'] . wf_nbsp(2) . '|' . wf_nbsp(2) .
                        __('Total rows') . ': ' . $fileInfoArray['rowcount'] . wf_nbsp(2) . '|' . wf_nbsp(2) .
                        __('Admin') . ': ' . $fileInfoArray['admin'];

            /*            __('Processed rows') . ': ' . $fileInfoArray['processed_cnt'] . wf_nbsp(2) . '|' . wf_nbsp(2) .
                        __('Canceled rows') . ': ' . $fileInfoArray['canceled_cnt'] . wf_nbsp(2) . '|' . wf_nbsp(2) .*/

            show_window($fileInfo, $Banksta->web_BSProcessingForm($_GET['showhash']));
        }

        if (wf_CheckGet(array('showdetailed'))) {
            $windowContent = wf_tag('pre', false, 'floatpanelswide', 'style="width: 90%; padding: 10px 15px;"');
            $windowContent.= print_r($Banksta->getBankstaRecDetails($_GET['showdetailed']), true);
            $windowContent.= wf_tag('pre', true);

            die(wf_modalAutoForm(__('Detailed info'), $windowContent, $_GET['detailsWinID'], '', true));
        }

        if (wf_CheckPost(array('delStatement')) and wf_CheckPost(array('hash'))) {
            $Banksta->deleteBankStatement($_POST['hash']);
            die();
        }

        // create fields mapping preset
        if (wf_CheckPost(array('fmpcreate'))) {
            if (wf_CheckPost(array('fmpname'))) {
                $newFMPName = $_POST['fmpname'];
                $foundId = $Banksta->checkFMPNameExists($newFMPName);

                if (empty($foundId)) {
                    if (wf_CheckGet(array('fmpquickadd'))) {
                        $Banksta->addFieldsMappingPreset($newFMPName, $_POST['bsrealname_col'], $_POST['bsaddress_col'], $_POST['bspaysum_col'],
                                                         $_POST['bspaypurpose_col'], $_POST['bspaydate_col'], $_POST['bspaytime_col'], $_POST['bscontract_col'],
                                                        (wf_CheckPost(array('bstryguesscontract'))) ? 1 : 0,
                                                         mysql_real_escape_string($_POST['bscontractdelimstart']), mysql_real_escape_string($_POST['bscontractdelimend']),
                                                         $_POST['bscontractminlen'], $_POST['bscontractmaxlen'], $_POST['bssrvtype'],
                                                         mysql_real_escape_string($_POST['bsinetdelimstart']), mysql_real_escape_string($_POST['bsinetdelimend']),
                                                         mysql_real_escape_string($_POST['bsinetkeywords']), mysql_real_escape_string($_POST['bsukvdelimstart']),
                                                         mysql_real_escape_string($_POST['bsukvdelimend']), mysql_real_escape_string($_POST['bsukvkeywords']),
                                             (wf_CheckPost(array('bsskiprow'))) ? 1 : 0,
                                                         mysql_real_escape_string($_POST['bsskiprow_col']), mysql_real_escape_string($_POST['bsskiprowkeywords'])
                                                        );
                    } else {
                        $Banksta->addFieldsMappingPreset($newFMPName, $_POST['fmpcolrealname'], $_POST['fmpcoladdr'], $_POST['fmpcolpaysum'],
                                                         $_POST['fmpcolpaypurpose'], $_POST['fmpcolpaydate'], $_POST['fmpcolpaytime'], $_POST['fmpcolcontract'],
                                                        (wf_CheckPost(array('fmptryguesscontract'))) ? 1 : 0,
                                                         mysql_real_escape_string($_POST['fmpcontractdelimstart']), mysql_real_escape_string($_POST['fmpcontractdelimend']),
                                                         $_POST['fmpcontractminlen'], $_POST['fmpcontractmaxlen'], $_POST['fmpsrvtype'],
                                                         mysql_real_escape_string($_POST['fmpinetdelimstart']), mysql_real_escape_string($_POST['fmpinetdelimend']),
                                                         mysql_real_escape_string($_POST['fmpinetkeywords']), mysql_real_escape_string($_POST['fmpukvdelimstart']),
                                                         mysql_real_escape_string($_POST['fmpukvdelimend']), mysql_real_escape_string($_POST['fmpukvkeywords']),
                                                         (wf_CheckPost(array('fmpskiprow'))) ? 1 : 0,
                                                         mysql_real_escape_string($_POST['fmpcolskiprow']), mysql_real_escape_string($_POST['fmpskiprowkeywords'])
                                                        );
                    }
                    die();
                } else {
                    $errormes = $Banksta->getUbMsgHelperInstance()->getStyledMessage(__('Preset with such name already exists with ID: ') . $foundId,
                                                                                        'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                    die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                }
            }

            die(wf_modalAutoForm(__('Add fields mapping preset'), $Banksta->renderFMPAddForm($_POST['modalWindowId']), $_POST['modalWindowId'], $_POST['modalWindowBodyId'], true));
        }

        // manipulate fields mapping preset
        if (wf_CheckPost(array('fmpid'))) {
            $fmpID = $_POST['fmpid'];

            // edit fields mapping preset
            if (wf_CheckPost(array('fmpedit'))) {
                 if (wf_CheckPost(array('fmpname'))) {
                     $newFMPName = $_POST['fmpname'];
                     $foundId = $Banksta->checkFMPNameExists($newFMPName, $fmpID);

                     if (empty($foundId)) {
                         $Banksta->editFieldsMappingPreset($fmpID, $newFMPName, $_POST['fmpcolrealname'], $_POST['fmpcoladdr'], $_POST['fmpcolpaysum'],
                                                           $_POST['fmpcolpaypurpose'], $_POST['fmpcolpaydate'], $_POST['fmpcolpaytime'], $_POST['fmpcolcontract'],
                                            (wf_CheckPost(array('fmptryguesscontract'))) ? 1 : 0,
                                                           $_POST['fmpcontractdelimstart'], $_POST['fmpcontractdelimend'],
                                                           $_POST['fmpcontractminlen'], $_POST['fmpcontractmaxlen'], $_POST['fmpsrvtype'],
                                                           $_POST['fmpinetdelimstart'], $_POST['fmpinetdelimend'], $_POST['fmpinetkeywords'],
                                                           $_POST['fmpukvdelimstart'], $_POST['fmpukvdelimend'], $_POST['fmpukvkeywords'],
                                                (wf_CheckPost(array('fmpskiprow'))) ? 1 : 0,
                                                           $_POST['fmpcolskiprow'], $_POST['fmpskiprowkeywords']
                                                          );
                         die();
                     } else {
                         $errormes = $Banksta->getUbMsgHelperInstance()->getStyledMessage(__('Preset with such name already exists with ID: ') . $foundId,
                             'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                         die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                     }
                 }

                die(wf_modalAutoForm(__('Edit fields mapping preset'), $Banksta->renderFMPEditForm($fmpID, $_POST['modalWindowId']), $_POST['modalWindowId'], $_POST['modalWindowBodyId'], true));
            }

            // delete fields mapping preset
            if (wf_CheckPost(array('delFMP'))) {
                $Banksta->deleteFieldsMappingPreset($fmpID);
                die();
            }

            // get fields mapping preset data JSON
            if (wf_CheckPost(array('getfmpdata'))) {
                die($Banksta->getFMPDataJSON($fmpID, $Banksta->dbPresetsFlds2PreprocForm));
            }
        }

        //show upload form
        if (wf_CheckGet(array('uploadform'))) {
            show_window(__('Payments import from bank statement file'), $Banksta->web_FileUploadForm());
        }

        if (wf_CheckGet(array('proceedstatementimport'))) {
            $statementFileData = unserialize(base64_decode(zb_StorageGet('BANKSTA2_STATEMENT_FILEDATA')));
            $Banksta->processBankStatement(unserialize(base64_decode(zb_StorageGet('BANKSTA2_STATEMENT_DATA'))), $statementFileData);
            rcms_redirect($Banksta::URL_BANKSTA2_PROCESSING . $statementFileData['hash']);
        }

        if (wf_CheckPost(array('import_rawdata'))) {
            $importOptions = array();
            $skipLastChecks = (wf_CheckPost(array('bsskiplastcheck'))) ? 1 : 0;

            foreach ($Banksta->dbPresetsFlds2PreprocForm as $item => $value) {
                $importOptions[$item] = (wf_CheckPost(array($value))) ? $_POST[$value] : 0;
            }

            $lastCheksData = $Banksta->preprocessBStatement($_POST['import_rawdata'], $importOptions, $skipLastChecks);

            if (!$skipLastChecks) {
                $Banksta->web_LastChecksForm($lastCheksData);
            } else {
                rcms_redirect($Banksta::URL_BANKSTA2_PROCEED_STMT_IMP);
            }
        }

        if (wf_CheckPost(array('bankstatementuploaded'))) {
            //upload file and show preprocessing form
            $importFileData  = $Banksta->uploadFile();
            $delimiter       = $_POST['delimiter'];
            $encoding        = $_POST['encoding'];
            $useDBFColNames  = (wf_CheckPost(array('usedbfcolnames'))) ? $_POST['usedbfcolnames'] : false;
            $skipRowsCount = (wf_CheckPost(array('skiprowscount'))) ? $_POST['skiprowscount'] : 0;
            $errormes = '';

            $errormes = $Banksta->preprocessImportFile($importFileData['savedname'], $delimiter, $encoding, $useDBFColNames, $skipRowsCount);

            if (empty($errormes)) {
                zb_StorageSet('BANKSTA2_RAWDATA', base64_encode(serialize($Banksta->preprocessedFileData)));
                zb_StorageSet('BANKSTA2_STATEMENT_FILEDATA', base64_encode(serialize($importFileData)));
            } else {
                show_error($errormes);
            }
        }

        if (wf_CheckGet(array('fieldmapping'))) {
            $Banksta->web_FieldsMappingForm(zb_StorageGet('BANKSTA2_RAWDATA'));
        }
    } else {
        show_warning(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>