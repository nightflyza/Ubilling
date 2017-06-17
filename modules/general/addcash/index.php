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
                    if (isset($alter['SIGNUP_PAYMENTS']) && !empty($alter['SIGNUP_PAYMENTS'])) {
                        zb_CashAddWithSignup($login, $cash, $operation, $cashtype, $note);
                    } else {
                        zb_CashAdd($login, $cash, $operation, $cashtype, $note);
                    }
                    rcms_redirect("?module=addcash&username=" . $login);
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

        // Edit money form construct:
        $user_tariff = $user_data['Tariff'];
        $tariff_price = zb_TariffGetPrice($user_tariff);
        $fieldnames = array('fieldname1' => __('Current Cash state'), 'fieldname2' => __('New cash'));
        $fieldkey = 'newcash';

        $form = '';
        $form.= wf_FormDisabler();
        $form .= web_EditorCashDataForm($fieldnames, $fieldkey, $useraddress, $current_balance, $tariff_price);
        


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
        if (wf_CheckPost(array('editpaymentid', 'newpaymentdate', 'oldpaymentdate', 'oldpaymenttime'))) {
            $editPaymentId = vf($_POST['editpaymentid'], 3);
            $newPaymentDate = $_POST['newpaymentdate'];
            $oldPaymentDate = $_POST['oldpaymentdate'];
            $oldPaymentTime = $_POST['oldpaymenttime'];
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
                if (zb_checkDate($newPaymentDate)) {
                    simple_update_field('payments', 'date', $newPaymentDateTime, "WHERE `id`='" . $editPaymentId . "'");
                    log_register("PAYMENT EDIT DATE [" . $editPaymentId . "] (" . $login . ") FROM `" . $oldPaymentDate . "` ON `" . $newPaymentDate . "`");
                    rcms_redirect('?module=addcash&username=' . $login);
                } else {
                    show_error(__('Wrong date format'));
                    log_register("PAYMENT EDIT DATE FAIL [" . $editPaymentId . "] (" . $login . ")");
                }
            } else {
                log_register("PAYMENT UNAUTH EDITING ATTEMPT [" . $editPaymentId . "] (" . $login . ")");
            }
        }

        // Show form
        show_window(__('Money'), $form);
        // Previous payments show:
        show_window(__('Previous payments'), web_PaymentsByUser($login));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
