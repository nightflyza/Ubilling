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

        if (ubRouting::checkGet($ExtContras::URL_DICTPROFILES)) {
            /*show_window(__('Counterparties profiles dictionary'),
                        $ExtContras->renderAjaxDynWinButton($ExtContras::URL_ME,
                                                            array($ExtContras::ROUTE_PROFILE_ACTS => 'true'),
                                                            __('Create counterparty profile'), web_add_icon(),
                                                            'ubButton')
                        . wf_delimiter() . $ExtContras->profileRenderJQDT()
                       );
            */
            show_window(__('Counterparties profiles dictionary'),
                        $ExtContras->profileWebForm(false)
                        . wf_delimiter() . $ExtContras->profileRenderJQDT()
                       );
        }

        if (ubRouting::checkGet($ExtContras::URL_DICTPERIODS)) {
            show_window(__('Periods dictionary'),
                        $ExtContras->renderAjaxDynWinButton($ExtContras::URL_ME,
                                                            array($ExtContras::ROUTE_PERIOD_ACTS => 'true'),
                                                            __('Create period'), web_add_icon(),
                                                            'ubButton')
                       );
        }

        // todo: try to make this routine below reusable - make a separate method which called when
        // todo: $ExtContras::ROUTE_****_ACTS is present and covers all rec checks, edit&append routines
        // todo: and web forms displaying
        if (ubRouting::checkPost($ExtContras::ROUTE_PROFILE_ACTS)) {

            $dataArray = array($ExtContras::DBFLD_PROFILE_NAME => ubRouting::post($ExtContras::CTRL_PROFILE_NAME),
                               $ExtContras::DBFLD_PROFILE_CONTACT => ubRouting::post($ExtContras::CTRL_PROFILE_CONTACT),
                               $ExtContras::DBFLD_PROFILE_EDRPO => ubRouting::post($ExtContras::CTRL_PROFILE_EDRPO),
                               $ExtContras::DBFLD_PROFILE_MAIL => ubRouting::post($ExtContras::CTRL_PROFILE_MAIL)
                              );
file_put_contents('zxcv', print_r($_POST, true) . "\n", FILE_APPEND);
            $showResult = $ExtContras->processCRUDs('profileWebForm', $dataArray,'Profile',
                                                    $ExtContras::CTRL_PROFILE_NAME,
                                                    $ExtContras::TABLE_ECPROFILES,
                                                    $ExtContras::DBFLD_PROFILE_NAME
                                                   );
file_put_contents('axcv', $showResult . "\n\n", FILE_APPEND);
            //if (!empty($showResult)) {
            die($showResult);
            //}
/*            if(ubRouting::checkPost($ExtContras::ROUTE_EDIT_REC_ID)) {
                $recID      = ubRouting::post($ExtContras::ROUTE_EDIT_REC_ID);
                $recEdit    = ubRouting::checkPost($ExtContras::ROUTE_EDIT_ACTION, false);
                $recClone   = ubRouting::checkPost($ExtContras::ROUTE_CLONE_ACTION, false);

                if ($recEdit or $recClone) {
                    if (ubRouting::checkPost($ExtContras::CTRL_PROFILE_NAME)) {
                        $profName = ubRouting::post($ExtContras::CTRL_PROFILE_NAME);

                        if ($recClone) {
                            $foundProfID = $ExtContras->checkRecExists($ExtContras::TABLE_ECPROFILES,
                                                                       $ExtContras::DBFLD_PROFILE_NAME,
                                                                       $profName);
                        } else {
                            $foundProfID = $ExtContras->checkRecExists($ExtContras::TABLE_ECPROFILES,
                                                                       $ExtContras::DBFLD_PROFILE_NAME,
                                                                       $profName, $recID);
                        }

                        if (empty($foundProfID)) {
                            if ($recEdit) {
                                $ExtContras->profileCreadit(ubRouting::post($ExtContras::ROUTE_EDIT_REC_ID));
                            } elseif ($recClone) {
                                $ExtContras->profileCreadit();
                            }
                        } else {
                            die($ExtContras->renderErrorMsg(__('Error'), __('Profile with such name already exists with ID: ') . $foundProfID));
                        }
                    }

                    die($ExtContras->profileWebForm($recEdit, $recClone, $recID));
                }
            } elseif (ubRouting::checkPost($ExtContras::ROUTE_CREATE_ACTION)) {
                $ExtContras->profileCreadit();
            } else {
                die($ExtContras->profileWebForm());
            }*/

            //ubRouting::nav($ExtContras::URL_ME . '&' . $ExtContras::URL_DICTPROFILES . '=true');
        }

        if (ubRouting::checkPost($ExtContras::ROUTE_PERIOD_ACTS)) {
            $showResult = $ExtContras->processCRUDs('periodWebForm', 'periodCreadit',
                                                    'Period', $ExtContras::CTRL_PERIOD_NAME,
                                                    $ExtContras::TABLE_ECPERIODS,
                                                    $ExtContras::DBFLD_PERIOD_NAME
                                                   );

            if (!empty($showResult)) {
                die($showResult);
            }

            ubRouting::nav($ExtContras::URL_ME . '&' . $ExtContras::URL_DICTPERIODS . '=true');
        }

/*        if (wf_CheckGet(array('bankstalist'))) {
            show_window(__('Previously loaded bank statements'), $Banksta->renderBStatementsJQDT());
        }

        zb_BillingStats(true);

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
/*
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
                                                         mysql_real_escape_string($_POST['bsskiprow_col']), mysql_real_escape_string($_POST['bsskiprowkeywords']),
                                                        (wf_CheckPost(array('bsreplacestrs'))) ? 1 : 0,
                                                         mysql_real_escape_string($_POST['bscolsreplacestrs']),  mysql_real_escape_string($_POST['bsstrstoreplace']),
                                                         mysql_real_escape_string($_POST['bsstrstoreplacewith']),  mysql_real_escape_string($_POST['bsreplacementscnt']),
                                                        (wf_CheckPost(array('bsremovestrs'))) ? 1 : 0,
                                                         mysql_real_escape_string($_POST['bscolremovestrs']),  mysql_real_escape_string($_POST['bsstrstoremove']),
                                                         $_POST['bspaymtypeid'], $_POST['bssrvidents_col'], (wf_CheckPost(array('bssrvidentspreff'))) ? 1 : 0
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
                                                         mysql_real_escape_string($_POST['fmpcolskiprow']), mysql_real_escape_string($_POST['fmpskiprowkeywords']),
                                                        (wf_CheckPost(array('fmpreplacestrs'))) ? 1 : 0,
                                                         mysql_real_escape_string($_POST['fmpcolsreplacestrs']),  mysql_real_escape_string($_POST['fmpstrstoreplace']),
                                                         mysql_real_escape_string($_POST['fmpstrstoreplacewith']),  mysql_real_escape_string($_POST['fmpstrsreplacecount']),
                                                        (wf_CheckPost(array('fmpremovestrs'))) ? 1 : 0,
                                                         mysql_real_escape_string($_POST['fmpcolsremovestrs']),  mysql_real_escape_string($_POST['fmpstrstoremove']),
                                                         $_POST['fmppaymtypeid'], $_POST['fmpcolsrvidents'], (wf_CheckPost(array('fmpsrvidentspreff'))) ? 1 : 0
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
            $fmpEdit = wf_CheckPost(array('fmpedit'));
            $fmpClone = wf_CheckPost(array('fmpclone'));

            // edit/clone fields mapping preset
            if ($fmpEdit or $fmpClone) {
                 if (wf_CheckPost(array('fmpname'))) {
                     $newFMPName = $_POST['fmpname'];
                     $foundId = ($fmpClone) ? $Banksta->checkFMPNameExists($newFMPName) : $Banksta->checkFMPNameExists($newFMPName, $fmpID);

                     if (empty($foundId)) {
                         if ($fmpEdit) {
                             $Banksta->editFieldsMappingPreset($fmpID, $newFMPName, $_POST['fmpcolrealname'], $_POST['fmpcoladdr'], $_POST['fmpcolpaysum'],
                                                               $_POST['fmpcolpaypurpose'], $_POST['fmpcolpaydate'], $_POST['fmpcolpaytime'], $_POST['fmpcolcontract'],
                                                               (wf_CheckPost(array('fmptryguesscontract'))) ? 1 : 0,
                                                               $_POST['fmpcontractdelimstart'], $_POST['fmpcontractdelimend'],
                                                               $_POST['fmpcontractminlen'], $_POST['fmpcontractmaxlen'], $_POST['fmpsrvtype'],
                                                               $_POST['fmpinetdelimstart'], $_POST['fmpinetdelimend'], $_POST['fmpinetkeywords'],
                                                               $_POST['fmpukvdelimstart'], $_POST['fmpukvdelimend'], $_POST['fmpukvkeywords'],
                                                               (wf_CheckPost(array('fmpskiprow'))) ? 1 : 0,
                                                               $_POST['fmpcolskiprow'], $_POST['fmpskiprowkeywords'],
                                                               (wf_CheckPost(array('fmpreplacestrs'))) ? 1 : 0,
                                                               $_POST['fmpcolsreplacestrs'], $_POST['fmpstrstoreplace'], $_POST['fmpstrstoreplacewith'], $_POST['fmpstrsreplacecount'],
                                                               (wf_CheckPost(array('fmpremovestrs'))) ? 1 : 0,
                                                               $_POST['fmpcolsremovestrs'], $_POST['fmpstrstoremove'],
                                                               $_POST['fmppaymtypeid'], $_POST['fmpcolsrvidents'], (wf_CheckPost(array('fmpsrvidentspreff'))) ? 1 : 0
                             );
                         } elseif ($fmpClone) {
                             $Banksta->addFieldsMappingPreset($newFMPName, $_POST['fmpcolrealname'], $_POST['fmpcoladdr'], $_POST['fmpcolpaysum'],
                                                              $_POST['fmpcolpaypurpose'], $_POST['fmpcolpaydate'], $_POST['fmpcolpaytime'], $_POST['fmpcolcontract'],
                                                              (wf_CheckPost(array('fmptryguesscontract'))) ? 1 : 0,
                                                              $_POST['fmpcontractdelimstart'], $_POST['fmpcontractdelimend'],
                                                              $_POST['fmpcontractminlen'], $_POST['fmpcontractmaxlen'], $_POST['fmpsrvtype'],
                                                              $_POST['fmpinetdelimstart'], $_POST['fmpinetdelimend'], $_POST['fmpinetkeywords'],
                                                              $_POST['fmpukvdelimstart'], $_POST['fmpukvdelimend'], $_POST['fmpukvkeywords'],
                                                              (wf_CheckPost(array('fmpskiprow'))) ? 1 : 0,
                                                              $_POST['fmpcolskiprow'], $_POST['fmpskiprowkeywords'],
                                                              (wf_CheckPost(array('fmpreplacestrs'))) ? 1 : 0,
                                                              $_POST['fmpcolsreplacestrs'], $_POST['fmpstrstoreplace'], $_POST['fmpstrstoreplacewith'], $_POST['fmpstrsreplacecount'],
                                                              (wf_CheckPost(array('fmpremovestrs'))) ? 1 : 0,
                                                              $_POST['fmpcolsremovestrs'], $_POST['fmpstrstoremove'],
                                                              $_POST['fmppaymtypeid'], $_POST['fmpcolsrvidents'], (wf_CheckPost(array('fmpsrvidentspreff'))) ? 1 : 0
                             );
                         }

                         die();
                     } else {
                         $errormes = $Banksta->getUbMsgHelperInstance()->getStyledMessage(__('Preset with such name already exists with ID: ') . $foundId,
                             'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
                         die(wf_modalAutoForm(__('Error'), $errormes, $_POST['errfrmid'], '', true));
                     }
                 }

                die(wf_modalAutoForm(__('Edit fields mapping preset'), $Banksta->renderFMPEditForm($fmpID, $_POST['modalWindowId'], $fmpClone), $_POST['modalWindowId'], $_POST['modalWindowBodyId'], true));
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
*/
    } else {
        show_warning(__('This module is disabled'));
    }
} else {
    show_error(__('Access denied'));
}
?>