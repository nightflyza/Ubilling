<?php

if (cfr('CASH')) {
    if (isset($_GET['username'])) {
        $alter = $ubillingConfig->getAlter();
        $login = vf($_GET['username']);

        // Change finance state if need:
        if (isset($_POST['newcash'])) {
            // Init
            $cash = $_POST['newcash'];
            $operation = vf($_POST['operation']);
            $cashtype = vf($_POST['cashtype']);
            $note = ( isset($_POST['newpaymentnote']) ) ? mysql_real_escape_string($_POST['newpaymentnote']) : '';

            // Empty cash hotfix:
            if ($cash != '') {
                if (zb_checkMoney($cash)) {
                    $whoami = whoami();
                    $employeeId = ts_GetEmployeeByLogin($whoami);
                    $employeeData = stg_get_employee_data($employeeId);
                    $employeeLimit = @$employeeData['amountLimit'];
                    $lastDKErrorParam = '';

                    if (!cfr('ROOT') and ! empty($employeeLimit)) {
                        $query = "SELECT sum(`summ`) as `summa` FROM `payments` WHERE MONTH(`date`) = MONTH(NOW()) AND YEAR(`date`) = YEAR(NOW()) AND admin = '" . $whoami . "' AND `summ`>0";
                        $summa = simple_query($query);
                        $summa = $summa['summa'];
                        if ($employeeLimit - $summa >= $cash) {
                            if (isset($alter['SIGNUP_PAYMENTS']) && !empty($alter['SIGNUP_PAYMENTS'])) {
                                zb_CashAddWithSignup($login, $cash, $operation, $cashtype, $note);
                            } else {
                                zb_CashAdd($login, $cash, $operation, $cashtype, $note);
                            }

                            if ($operation == 'add' and $ubillingConfig->getAlterParam('DREAMKAS_ENABLED') and wf_CheckPost(array('dofiscalizepayment'))) {
                                $lastDKError = doDreamkasFiscalize($login, $cash);

                                if (!empty($lastDKError)) {
                                    $lastDKErrorParam = '&lastdkerror=' . urlencode($lastDKError);
                                }
                            }

                            rcms_redirect("?module=addcash&username=" . $login . $lastDKErrorParam);
                        } else {
                            show_window('', wf_modalOpened(__('Error'), __('Payment amount exceeded per month') . wf_tag('br') . __('You can top up for the amount of:') . ' ' . __($employeeLimit - $summa), '400', '200'));
                            log_register('BALANCEADDFAIL (' . $login . ') AMOUNT LIMIT `' . mysql_real_escape_string($employeeLimit - $summa) . '` TRY ADD SUMM `' . $cash . '`');
                        }
                    } else {
                        if (isset($alter['SIGNUP_PAYMENTS']) && !empty($alter['SIGNUP_PAYMENTS'])) {
                            zb_CashAddWithSignup($login, $cash, $operation, $cashtype, $note);
                        } else {
                            zb_CashAdd($login, $cash, $operation, $cashtype, $note);
                        }

                        if ($operation == 'add' and $ubillingConfig->getAlterParam('DREAMKAS_ENABLED') and wf_CheckPost(array('dofiscalizepayment'))) {
                            $lastDKError = doDreamkasFiscalize($login, $cash);

                            if (!empty($lastDKError)) {
                                $lastDKErrorParam = '&lastdkerror=' . urlencode($lastDKError);
                            }
                        }

                        rcms_redirect("?module=addcash&username=" . $login . $lastDKErrorParam);
                    }
                } else {
                    show_window('', wf_modalOpened(__('Error'), __('Wrong format of a sum of money to pay'), '400', '200'));
                    log_register('BALANCEADDFAIL (' . $login . ') WRONG SUMM `' . $cash . '`');
                }
            } else {
                show_window('', wf_modalOpened(__('Error'), __('You have not completed the required amount of money to deposit into account. We hope next time you will be more attentive.'), '400', '150'));
                log_register('BALANCEADDFAIL (' . $login . ') EMPTY SUMM `' . $cash . '`');
            }
        }

        // Profile:
        $profile = new UserProfile($login);
        show_window(__('User profile'), $profile->render());

        $user_data = $profile->extractUserData();
        $current_balance = $user_data['Cash'];
        $useraddress = $profile->extractUserAddress() . ' (' . $login . ')';
        $userContract = $profile->extractUserContract();
        if (!empty($userContract)) {
            $userContract = ' (' . $profile->extractUserContract() . ')';
        }
        $userrealname = $profile->extractUserRealName() . $userContract;

        // Edit money form construct:
        $user_tariff = $user_data['Tariff'];
        $tariff_price = zb_TariffGetPrice($user_tariff);
        if (@$alter['BABLOGUESSING']) {
            $tariff_price+=zb_VservicesGetUserPrice($login);
        }
        $fieldnames = array('fieldname1' => __('Current Cash state'), 'fieldname2' => __('New cash'));
        $fieldkey = 'newcash';

        $form = '';
        $form.= wf_FormDisabler();
        $form.= web_EditorCashDataForm($fieldnames, $fieldkey, $useraddress, $current_balance, $tariff_price, $userrealname);

        // Check is user corporate?
        if ($alter['USER_LINKING_ENABLED']) {
            if ($alter['USER_LINKING_CASH']) {
                if (cu_IsChild($login)) {
                    $allchildusers = cu_GetAllLinkedUsers();
                    $parent_link = $allchildusers[$login];
                    rcms_redirect("?module=corporate&userlink=" . $parent_link . "&control=cash");
                }

                if (cu_IsParent($login)) {
                    $allparentusers = cu_GetAllParentUsers();
                    $parent_link = $allparentusers[$login];
                    rcms_redirect("?module=corporate&userlink=" . $parent_link . "&control=cash");
                }
            }
        }

        //payments deletion
        if (wf_CheckGet(array('paymentdelete'))) {
            $deletePaymentId = vf($_GET['paymentdelete'], 3);
            $deletingAdmins = array();
            $iCanDeletePayments = false;
            $currentAdminLogin = whoami();
            //extract delete admin logins
            if (!empty($alter['CAN_DELETE_PAYMENTS'])) {
                $deletingAdmins = explode(',', $alter['CAN_DELETE_PAYMENTS']);
                $deletingAdmins = array_flip($deletingAdmins);
            }

            $iCanDeletePayments = (isset($deletingAdmins[$currentAdminLogin])) ? true : false;
            //right check
            if ($iCanDeletePayments) {
                $queryDeletion = "DELETE from `payments` WHERE `id`='" . $deletePaymentId . "' ;";
                nr_query($queryDeletion);
                log_register("PAYMENT DELETE [" . $deletePaymentId . "] (" . $login . ")");
                rcms_redirect('?module=addcash&username=' . $login . '#profileending');
            } else {
                log_register("PAYMENT UNAUTH DELETION ATTEMPT [" . $deletePaymentId . "] (" . $login . ")");
            }
        }

        //payments date editing
        if (wf_CheckPost(array('editpaymentid', 'newpaymentdate', 'cashtype', 'paymentdata'))) {
            $editPaymentId = vf($_POST['editpaymentid'], 3);
            $newPaymentDate = $_POST['newpaymentdate'];
            $cachTypeId = vf($_POST['cashtype'], 3);
            $PaymentNote = trim(@$_POST['paymentnote']);

            $paymentDataBase = $_POST['paymentdata'];
            $paymentData = base64_decode($paymentDataBase);
            $paymentData = unserialize($paymentData);

            $PaymentTimestamp = strtotime($paymentData['date']);
            $oldPaymentDate = date("Y-m-d", $PaymentTimestamp);
            $oldPaymentTime = date("H:i:s", $PaymentTimestamp);

            $oldPaymentCacheTypeID = $paymentData['cashtypeid'];

            $oldPaymentNote = $paymentData['note'];

            $newPaymentDateTime = $newPaymentDate . ' ' . $oldPaymentTime;
            $editingAdmins = array();
            $iCanEditPayments = false;
            $currentAdminLogin = whoami();
            //extract edit admin logins
            if (!empty($alter['CAN_EDIT_PAYMENTS'])) {
                $editingAdmins = explode(',', $alter['CAN_EDIT_PAYMENTS']);
                $editingAdmins = array_flip($editingAdmins);
            }

            $iCanEditPayments = (isset($editingAdmins[$currentAdminLogin])) ? true : false;
            //right check
            if ($iCanEditPayments) {
                // Check what need update
                if ($newPaymentDate != $oldPaymentDate) {
                    if (zb_checkDate($newPaymentDate)) {
                        simple_update_field('payments', 'date', $newPaymentDateTime, "WHERE `id`='" . $editPaymentId . "'");
                        log_register("PAYMENT EDIT DATE [" . $editPaymentId . "] (" . $login . ") FROM `" . $oldPaymentDate . "` ON `" . $newPaymentDate . "`");
                        rcms_redirect('?module=addcash&username=' . $login);
                    } else {
                        show_error(__('Wrong date format'));
                        log_register("PAYMENT EDIT DATE FAIL [" . $editPaymentId . "] (" . $login . ")");
                    }
                }
                if ($cachTypeId != $oldPaymentCacheTypeID) {
                    if ($cachTypeId) {
                        simple_update_field('payments', 'cashtypeid', $cachTypeId, "WHERE `id`='" . $editPaymentId . "'");
                        log_register("PAYMENT EDIT CACHTYPEID [" . $editPaymentId . "] (" . $login . ") FROM `" . $oldPaymentCacheTypeID . "` ON `" . $cachTypeId . "`");
                        rcms_redirect('?module=addcash&username=' . $login);
                    } else {
                        show_error(__('Something went wrong'));
                        log_register("PAYMENT EDIT CACHTYPEID FAIL [" . $editPaymentId . "] (" . $login . ")");
                    }
                }
                if ($PaymentNote != $oldPaymentNote) {
                    simple_update_field('payments', 'note', $PaymentNote, "WHERE `id`='" . $editPaymentId . "'");
                    log_register("PAYMENT EDIT NOTE [" . $editPaymentId . "] (" . $login . ") FROM `" . $oldPaymentNote . "` ON `" . $PaymentNote . "`");
                    rcms_redirect('?module=addcash&username=' . $login);
                }
            } else {
                log_register("PAYMENT UNAUTH EDITING ATTEMPT [" . $editPaymentId . "] (" . $login . ")");
            }
        }

        $errorWindow = '';

        if (wf_CheckGet(array('lastdkerror'))) {
            $messages = new UbillingMessageHelper();
            $errorMessage = $messages->getStyledMessage(urldecode($_GET['lastdkerror']), 'error');
            $errorWindow = wf_modalAutoForm(__('Fiscalization error'), $errorMessage, '', '', true, 'true', '700');
        }

        // Show form
        show_window(__('Money'), $errorWindow . $form);
        // Previous payments show:
        show_window(__('Previous payments'), web_PaymentsByUser($login));
    }
} else {
    show_error(__('You cant control this module'));
}

function doDreamkasFiscalize($login, $cash) {
    $cahsMachineID = $_POST['drscashmachines'];
    $taxType = $_POST['drstaxtypes'];
    $paymentType = $_POST['drspaymtypes'];
    $userMobile = zb_UserGetMobile($login);
    $userEmail = zb_UserGetEmail($login);

    $sellPosIDsPrices = array($_POST['drssellpos'] => array('price' => ($cash * 100)));
    $userContacts = array('email' => $userEmail, 'phone' => $userMobile);

    $DreamKas = new DreamKas();
    $preparedCheckJSON = $DreamKas->prepareCheckFiscalData($cahsMachineID, $taxType, $paymentType, $sellPosIDsPrices, $userContacts);
    $DreamKas->fiscalizeCheck($preparedCheckJSON);

    return ($DreamKas->getLastErrorMessage());
}
?>
