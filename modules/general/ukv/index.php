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
        if (ubRouting::checkGet('ajax')) {
            $ukv->ajaxUsers();
        }

        /*
         * some views here
         */

        //show global management panel
        show_window('', $ukv->panel());

        //renders tariffs list with controls
        if (ubRouting::checkGet('tariffs')) {

            //tariffs editing
            if (ubRouting::checkPost('edittariff')) {
                $ukv->tariffSave(ubRouting::post('edittariff'), ubRouting::post('edittariffname'), ubRouting::post('edittariffprice'));
                ubRouting::nav(UkvSystem::URL_TARIFFS_MGMT);
            }

            //tariffs creation
            if (ubRouting::checkPost('createtariff')) {
                $ukv->tariffCreate(ubRouting::post('createtariffname'), ubRouting::post('createtariffprice'));
                ubRouting::nav(UkvSystem::URL_TARIFFS_MGMT);
            }

            //tariffs deletion
            if (ubRouting::checkGet('tariffdelete')) {
                $ukv->tariffDelete(ubRouting::get('tariffdelete'));
                ubRouting::nav(UkvSystem::URL_TARIFFS_MGMT);
            }

            //show tariffs lister
            show_window(__('Available tariffs'), $ukv->renderTariffs());
        }

        //full users listing
        if (ubRouting::checkGet(array('users', 'userslist'))) {
            show_window(__('Available users'), $ukv->renderUsers());
            zb_BillingStats(true);
        }

        //users registration
        if (ubRouting::checkGet(array('users', 'register'))) {
            if (ubRouting::checkPost('userregisterprocessing')) {
                if (ubRouting::checkPost(array('citysel', 'streetsel', 'buildsel'))) {
                    //all needed fields is filled - processin registration
                    $createdUserId = $ukv->userCreate();
                    ubRouting::nav(UkvSystem::URL_USERS_PROFILE . $createdUserId);
                } else {
                    show_window(__('Error'), __('All fields marked with an asterisk are mandatory'));
                }
            }
            //show new user registration form
            show_window(__('User registration'), $ukv->userRegisterForm());
        }

        //user profile show
        if (ubRouting::checkGet(array('users', 'showuser'))) {

            //user editing processing
            if (ubRouting::checkPost('usereditprocessing')) {
                $ukv->userSave();
                ubRouting::nav(UkvSystem::URL_USERS_PROFILE . ubRouting::post('usereditprocessing'));
            }

            //user cable seal editing processing
            if (ubRouting::checkPost('usercablesealprocessing')) {
                $ukv->userCableSealSave();
                ubRouting::nav(UkvSystem::URL_USERS_PROFILE . ubRouting::post('usercablesealprocessing'));
            }

            //user deletion processing
            if (ubRouting::checkPost(array('userdeleteprocessing', 'deleteconfirmation'))) {
                if (ubRouting::post('deleteconfirmation') == 'confirm') {
                    $ukv->userDelete(ubRouting::post('userdeleteprocessing'));
                    ubRouting::nav(UkvSystem::URL_USERS_LIST);
                } else {
                    log_register('UKV USER DELETE TRY ((' . ubRouting::post('userdeleteprocessing') . '))');
                }
            }

            //manual payments processing
            if (ubRouting::checkPost(array('manualpaymentprocessing', 'paymentsumm', 'paymenttype'))) {
                $paymentNotes = '';
                //normal payment
                if (ubRouting::post('paymenttype') == 'add') {
                    $paymentVisibility = 1;
                }
                //balance correcting
                if (ubRouting::post('paymenttype') == 'correct') {
                    $paymentVisibility = 0;
                }
                //mock payment
                if (ubRouting::post('paymenttype') == 'mock') {
                    $paymentVisibility = 1;
                    $paymentNotes .= 'MOCK:';
                }
                //set payment notes
                if (ubRouting::checkPost('paymentnotes')) {
                    $paymentNotes .= ubRouting::post('paymentnotes');
                }

                if ($ukv->isMoney(ubRouting::post('paymentsumm'))) {
                    if (ubRouting::post('paymenttype') != 'mock') {
                        $ukv->userAddCash(ubRouting::post('manualpaymentprocessing'), ubRouting::post('paymentsumm'), $paymentVisibility, ubRouting::post('paymentcashtype'), $paymentNotes);

                        if ($ubillingConfig->getAlterParam('DREAMKAS_ENABLED') and ubRouting::checkPost(array('dofiscalizepayment'))) {
                            $greed = new Avarice();
                            $insatiability = $greed->runtime('DREAMKAS');

                            if (!empty($insatiability)) {
                                $DreamKas = new DreamKas();

                                $rapacity_a = $insatiability['M']['KICKUP'];
                                $rapacity_b = $insatiability['M']['PICKUP'];
                                $rapacity_c = $insatiability['M']['PUSHCASHLO'];
                                $rapacity_d = $insatiability['M']['ONONOKI'];

                                $voracity_a = ubRouting::post($insatiability['PG']['SHINOBU']);
                                $voracity_b = ubRouting::post($insatiability['PG']['KOYOMI']);
                                $voracity_c = ubRouting::post($insatiability['PG']['HITAGI']);
                                $voracity_d = $DreamKas->$rapacity_d(ubRouting::post('manualpaymentprocessing'), $ukv);

                                $voracity_d = (empty($voracity_d)) ? '' : $voracity_d[$insatiability['AK']['ARARAGI']];
                                $voracity_e = '';

                                $voracity_f = array(ubRouting::post($insatiability['PG']['NADEKO']) => array($insatiability['AK']['TSUKIHI'] => (ubRouting::post('paymentsumm') * 100)));
                                $voracity_g = array($insatiability['AK']['MAYOI'] => $voracity_e, $insatiability['AK']['OUGI'] => $voracity_d);

                                $voracity_h = $DreamKas->$rapacity_a($voracity_a, $voracity_b, $voracity_c, $voracity_f, $voracity_g);
                                $DreamKas->$rapacity_c($voracity_h);
                                $voracity_i = $DreamKas->$rapacity_b();

                                if ($ubillingConfig->getAlterParam('DREAMKAS_NOTIFICATIONS_ENABLED')) {
                                    $DreamKas->putNotificationData2Cache($voracity_i);
                                    $voracity_i = '';
                                }
                            } else {
                                $voracity_i = 'Dreamkas: ' . __('No license key available');
                            }
                        }
                    } else {
                        $ukv->logPayment(ubRouting::post('manualpaymentprocessing'), ubRouting::post('paymentsumm'), $paymentVisibility, ubRouting::post('paymentcashtype'), $paymentNotes);
                    }

                    $lastDKErrorParam = '';

                    if (isset($voracity_i) and !empty($voracity_i)) {
                        $lastDKErrorParam = '&lastdkerror=' . urlencode($voracity_i);
                    }

                    ubRouting::nav(UkvSystem::URL_USERS_PROFILE . ubRouting::post('manualpaymentprocessing') . $lastDKErrorParam);
                } else {
                    show_window('', wf_modalOpened(__('Error'), __('Wrong format of a sum of money to pay'), '400', '200'));
                    log_register('UKV BALANCEADDFAIL ((' . ubRouting::post('manualpaymentprocessing') . ')) WRONG SUMM `' . ubRouting::post('paymentsumm') . '`');
                }
            }

            $errorWindow = '';

            if (ubRouting::checkGet('lastdkerror')) {
                $errorMessage = $ukv->getUbMessagesInstance()->getStyledMessage(urldecode(ubRouting::get('lastdkerror')), 'error');
                $errorWindow = wf_modalAutoForm(__('Fiscalization error'), $errorMessage, '', '', true, 'true', '700');
            }

            //payments deletion
            if (ubRouting::checkGet(array('deletepaymentid'))) {
                $ukv->paymentDelete(ubRouting::get('deletepaymentid'), ubRouting::get('showuser'));
                ubRouting::nav(UkvSystem::URL_USERS_PROFILE . ubRouting::get('showuser'));
            }

            //user profile rendering
            if (!ubRouting::checkGet('lifestory')) {
                show_window(__('User profile'), $ukv->userProfile(ubRouting::get('showuser')) . $errorWindow);
            } else {
                //or lifestory view
                show_window('', wf_BackLink($ukv::URL_USERS_PROFILE . ubRouting::get('showuser')));
                show_window(__('User lifestory'), $ukv->userLifeStoryForm(ubRouting::get('showuser')));
            }
        }

        // bank statements processing
        if (ubRouting::checkGet('banksta')) {
            //banksta upload 
            if (ubRouting::checkPost('uploadukvbanksta')) {
                $processMan = new StarDust('UKV_BSUPL');
                if ($processMan->notRunning()) {
                    $processMan->start();
                    $bankstaUploaded = $ukv->bankstaDoUpload();
                    if (!empty($bankstaUploaded)) {
                        if (ubRouting::checkPost(array('ukvbankstatype'))) {
                            if (ubRouting::post('ukvbankstatype') == 'oschad') {
                                $processedBanksta = $ukv->bankstaPreprocessing($bankstaUploaded);
                                ubRouting::nav(UkvSystem::URL_BANKSTA_PROCESSING . $processedBanksta);
                            }

                            if (ubRouting::post('ukvbankstatype') == 'oschadterm') {
                                $processedBanksta = $ukv->bankstaPreprocessingTerminal($bankstaUploaded);
                                ubRouting::nav(UkvSystem::URL_BANKSTA_PROCESSING . $processedBanksta);
                            }

                            if (ubRouting::post('ukvbankstatype') == 'privatbankdbf') {
                                $processedBanksta = $ukv->bankstaPreprocessingPrivatDbf($bankstaUploaded);
                                ubRouting::nav(UkvSystem::URL_BANKSTA_PROCESSING . $processedBanksta);
                            }
                        } else {
                            show_error(__('Strange exeption') . ' NO_BANKSTA_TYPE');
                        }
                    }
                    $processMan->stop();
                } else {
                    show_error(__('Upload') . ': ' . __('Already running'));
                }
            } else {

                if (ubRouting::checkGet('showhash')) {
                    //changing some contract into the banksta
                    if (ubRouting::checkPost(array('bankstacontractedit', 'newbankcontr'))) {
                        $ukv->bankstaSetContract(ubRouting::post('bankstacontractedit'), ubRouting::post('newbankcontr'));
                        if (ubRouting::checkPost('lockbankstarow', false)) {
                            //locking some row if needed
                            $ukv->bankstaSetProcessed(ubRouting::post('bankstacontractedit'));
                            log_register('UKV BANKSTA [' . ubRouting::post('bankstacontractedit') . '] LOCKED');
                        }
                        ubRouting::nav(UkvSystem::URL_BANKSTA_PROCESSING . ubRouting::get('showhash'));
                    }

                    //push cash to users if is needed
                    if (ubRouting::checkPost('bankstaneedpaymentspush')) {
                        $processMan = new StarDust('UKV_BSPROCSNG');
                        if ($processMan->notRunning()) {
                            $processMan->start();
                            $ukv->bankstaPushPayments();
                            $processMan->stop();
                            ubRouting::nav(UkvSystem::URL_BANKSTA_MGMT);
                        } else {
                            show_error(__('Bank statements') . ': ' . __('Already running'));
                        }
                    }

                    show_window(__('Bank statement processing'), $ukv->bankstaProcessingForm(ubRouting::get('showhash')));
                } else {
                    if (ubRouting::checkGet(array('showdetailed'))) {
                        //show banksta row detailed info
                        show_window(__('Bank statement'), $ukv->bankstaGetDetailedRowInfo(ubRouting::get('showdetailed')));
                    } else {
                        //show upload form
                        show_window(__('Import bank statement'), $ukv->bankstaLoadForm());
                        //ajax data source list 
                        if (ubRouting::checkGet(array('ajbslist'))) {
                            $ukv->bankstaRenderAjaxList();
                        }
                        //and available bank statements
                        show_window(__('Previously loaded bank statements'), $ukv->bankstaRenderList());
                    }
                }
            }
        }

        //reports processing
        if (ubRouting::checkGet('reports')) {
            if (ubRouting::checkGet('showreport')) {
                $reportName = ubRouting::get('showreport', 'callback', 'vf');
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
