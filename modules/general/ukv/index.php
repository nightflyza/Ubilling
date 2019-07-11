<?php

if ($ubillingConfig->getAlterParam('UKV_ENABLED')) {
    if (cfr('UKV')) {

        set_time_limit(0);

        //creating base system object
        $ukv = new UkvSystem();

        /*
         * controller section
         */

        //fast ajax render
        if (wf_CheckGet(array('ajax'))) {
            $ukv->ajaxUsers();
        }

        /*
         * some views here
         */

        //show global management panel
        show_window('', $ukv->panel());

        //renders tariffs list with controls
        if (wf_CheckGet(array('tariffs'))) {

            //tariffs editing
            if (wf_CheckPost(array('edittariff'))) {
                $ukv->tariffSave($_POST['edittariff'], $_POST['edittariffname'], $_POST['edittariffprice']);
                rcms_redirect(UkvSystem::URL_TARIFFS_MGMT);
            }

            //tariffs creation
            if (wf_CheckPost(array('createtariff'))) {
                $ukv->tariffCreate($_POST['createtariffname'], $_POST['createtariffprice']);
                rcms_redirect(UkvSystem::URL_TARIFFS_MGMT);
            }

            //tariffs deletion
            if (wf_CheckGet(array('tariffdelete'))) {
                $ukv->tariffDelete($_GET['tariffdelete']);
                rcms_redirect(UkvSystem::URL_TARIFFS_MGMT);
            }

            //show tariffs lister
            show_window(__('Available tariffs'), $ukv->renderTariffs());
        }

        //full users listing
        if (wf_CheckGet(array('users', 'userslist'))) {
            show_window(__('Available users'), $ukv->renderUsers());
            zb_BillingStats(true);
        }

        //users registration
        if (wf_CheckGet(array('users', 'register'))) {
            if (wf_CheckPost(array('userregisterprocessing'))) {
                if (wf_CheckPost(array('citysel', 'streetsel', 'buildsel'))) {
                    //all needed fields is filled - processin registration
                    $createdUserId = $ukv->userCreate();
                    rcms_redirect(UkvSystem::URL_USERS_PROFILE . $createdUserId);
                } else {
                    show_window(__('Error'), __('All fields marked with an asterisk are mandatory'));
                }
            }
            //show new user registration form
            show_window(__('User registration'), $ukv->userRegisterForm());
        }

        //user profile show
        if (wf_CheckGet(array('users', 'showuser'))) {

            //user editing processing
            if (wf_CheckPost(array('usereditprocessing'))) {
                $ukv->userSave();
                rcms_redirect(UkvSystem::URL_USERS_PROFILE . $_POST['usereditprocessing']);
            }

            //user cable seal editing processing
            if (wf_CheckPost(array('usercablesealprocessing'))) {
                $ukv->userCableSealSave();
                rcms_redirect(UkvSystem::URL_USERS_PROFILE . $_POST['usercablesealprocessing']);
            }

            //user deletion processing
            if (wf_CheckPost(array('userdeleteprocessing', 'deleteconfirmation'))) {
                if ($_POST['deleteconfirmation'] == 'confirm') {
                    $ukv->userDelete($_POST['userdeleteprocessing']);
                    rcms_redirect(UkvSystem::URL_USERS_LIST);
                } else {
                    log_register('UKV USER DELETE TRY ((' . $_POST['userdeleteprocessing'] . '))');
                }
            }

            //manual payments processing
            if (wf_CheckPost(array('manualpaymentprocessing', 'paymentsumm', 'paymenttype'))) {
                $paymentNotes = '';
                //normal payment
                if ($_POST['paymenttype'] == 'add') {
                    $paymentVisibility = 1;
                }
                //balance correcting
                if ($_POST['paymenttype'] == 'correct') {
                    $paymentVisibility = 0;
                }
                //mock payment
                if ($_POST['paymenttype'] == 'mock') {
                    $paymentVisibility = 1;
                    $paymentNotes.='MOCK:';
                }
                //set payment notes
                if (wf_CheckPost(array('paymentnotes'))) {
                    $paymentNotes.=$_POST['paymentnotes'];
                }

                if ($ukv->isMoney($_POST['paymentsumm'])) {
                    if ($_POST['paymenttype'] != 'mock') {
                        $ukv->userAddCash($_POST['manualpaymentprocessing'], $_POST['paymentsumm'], $paymentVisibility, $_POST['paymentcashtype'], $paymentNotes);

                        if ($ubillingConfig->getAlterParam('DREAMKAS_ENABLED') and wf_CheckPost(array('dofiscalizepayment'))) {
                            $cahsMachineID = $_POST['drscashmachines'];
                            $taxType = $_POST['drstaxtypes'];
                            $paymentType = $_POST['drspaymtypes'];
                            $userData = $ukv->getUserData($_POST['manualpaymentprocessing']);

                            $userMobile = (empty($userData)) ? '' : $userData['mobile'];
                            $userEmail = '';

                            $sellPosIDsPrices = array($_POST['drssellpos'] => array('price' => ($_POST['paymentsumm'] * 100)));
                            $userContacts = array('email' => $userEmail, 'phone' => $userMobile);

                            $DreamKas = new DreamKas();
                            $preparedCheckJSON = $DreamKas->prepareCheckFiscalData($cahsMachineID, $taxType, $paymentType, $sellPosIDsPrices, $userContacts);
                            $DreamKas->fiscalizeCheck($preparedCheckJSON);
                            $lastDKError = $DreamKas->getLastErrorMessage();
                        }
                    } else {
                        $ukv->logPayment($_POST['manualpaymentprocessing'], $_POST['paymentsumm'], $paymentVisibility, $_POST['paymentcashtype'], $paymentNotes);
                    }

                    $lastDKErrorParam = '';

                    if (isset($lastDKError) and !empty($lastDKError)) {
                        $lastDKErrorParam = '&lastdkerror=' . urlencode($lastDKError);
                    }

                    rcms_redirect(UkvSystem::URL_USERS_PROFILE . $_POST['manualpaymentprocessing'] . $lastDKErrorParam);
                } else {
                    show_window('', wf_modalOpened(__('Error'), __('Wrong format of a sum of money to pay'), '400', '200'));
                    log_register('UKV BALANCEADDFAIL ((' . $_POST['manualpaymentprocessing'] . ')) WRONG SUMM `' . $_POST['paymentsumm'] . '`');
                }
            }

            $errorWindow = '';

            if (wf_CheckGet(array('lastdkerror'))) {
                $errorMessage = $ukv->getUbMessagesInstance()->getStyledMessage(urldecode($_GET['lastdkerror']), 'error');
                $errorWindow = wf_modalAutoForm(__('Fiscalization error'), $errorMessage, '', '', true, 'true', '700');
                /*$errorWindow.= wf_tag('script', false, '', 'type="text/javascript"');
                $errorWindow.= '
                                 $(document).ready(function() {
                                    var curURL = window.location;

                                    if (~curURL.indexOf("&lastdkerror")) {
	                                    var newURL = curURL.substr(0, curURL.indexOf("&lastdkerror"));
	                                    alert(newURL);
	                                    window.location.href = newURL;
                                    }
                                 });
                               ';
                $errorWindow.= wf_tag('script', true);*/
            }

            show_window(__('User profile'), $ukv->userProfile($_GET['showuser']) . $errorWindow);
        }

        // bank statements processing
        if (wf_CheckGet(array('banksta'))) {
            //banksta upload 
            if (wf_CheckPost(array('uploadukvbanksta'))) {
                $bankstaUploaded = $ukv->bankstaDoUpload();
                if (!empty($bankstaUploaded)) {
                    if (wf_CheckPost(array('ukvbankstatype'))) {
                        if ($_POST['ukvbankstatype'] == 'oschad') {
                            $processedBanksta = $ukv->bankstaPreprocessing($bankstaUploaded);
                            rcms_redirect(UkvSystem::URL_BANKSTA_PROCESSING . $processedBanksta);
                        }

                        if ($_POST['ukvbankstatype'] == 'oschadterm') {
                            $processedBanksta = $ukv->bankstaPreprocessingTerminal($bankstaUploaded);
                            rcms_redirect(UkvSystem::URL_BANKSTA_PROCESSING . $processedBanksta);
                        }

                        if ($_POST['ukvbankstatype'] == 'privatbankdbf') {
                            $processedBanksta = $ukv->bankstaPreprocessingPrivatDbf($bankstaUploaded);
                            rcms_redirect(UkvSystem::URL_BANKSTA_PROCESSING . $processedBanksta);
                        }
                    } else {
                        show_error(__('Strange exeption') . ' NO_BANKSTA_TYPE');
                    }
                }
            } else {

                if (wf_CheckGet(array('showhash'))) {
                    //changing some contract into the banksta
                    if (wf_CheckPost(array('bankstacontractedit', 'newbankcontr'))) {
                        $ukv->bankstaSetContract($_POST['bankstacontractedit'], $_POST['newbankcontr']);
                        if (isset($_POST['lockbankstarow'])) {
                            //locking some row if needed
                            $ukv->bankstaSetProcessed($_POST['bankstacontractedit']);
                            log_register('UKV BANKSTA [' . $_POST['bankstacontractedit'] . '] LOCKED');
                        }
                        rcms_redirect(UkvSystem::URL_BANKSTA_PROCESSING . $_GET['showhash']);
                    }

                    //push cash to users if is needed
                    if (wf_CheckPost(array('bankstaneedpaymentspush'))) {
                        $ukv->bankstaPushPayments();
                        rcms_redirect(UkvSystem::URL_BANKSTA_MGMT);
                    }

                    show_window(__('Bank statement processing'), $ukv->bankstaProcessingForm($_GET['showhash']));
                } else {
                    if (wf_CheckGet(array('showdetailed'))) {
                        //show banksta row detailed info
                        show_window(__('Bank statement'), $ukv->bankstaGetDetailedRowInfo($_GET['showdetailed']));
                    } else {
                        //show upload form
                        show_window(__('Import bank statement'), $ukv->bankstaLoadForm());
                        //ajax data source list 
                        if (wf_CheckGet(array('ajbslist'))) {
                            $ukv->bankstaRenderAjaxList();
                        }
                        //and available bank statements

                        show_window(__('Previously loaded bank statements'), $ukv->bankstaRenderList());
                    }
                }
            }
        }

        //reports processing
        if (wf_CheckGet(array('reports'))) {

            if (wf_CheckGet(array('showreport'))) {
                $reportName = vf($_GET['showreport']);
                if (ispos($reportName, 'report')) {
                    if (method_exists($ukv, $reportName)) {
                        //call method
                        $ukv->$reportName();
                    } else {
                        show_error(__('Non existent method'));
                    }
                } else {
                    show_error(__('Strange exeption'));
                }
            }
        }
    } else {
        show_error(__('Permission denied'));
    }
} else {
    show_error(__('This module is disabled'));
}
?>