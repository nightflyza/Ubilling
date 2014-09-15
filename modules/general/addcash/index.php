<?php
if ( cfr('CASH') ) {
    if ( isset($_GET['username']) ) {
        $alter = $ubillingConfig->getAlter();
        $login = vf($_GET['username']);
        
        // Change finance state if need:
        if ( isset($_POST['newcash']) ) {
            // Init
            $cash = $_POST['newcash'];
            $operation = vf($_POST['operation']);
            $cashtype  = vf($_POST['cashtype']);
            $note = ( isset($_POST['newpaymentnote']) ) ? mysql_real_escape_string($_POST['newpaymentnote']) : '';

            // Empty cash hotfix:
            if ( $cash != '' ) {
                if (zb_checkMoney($cash)) {
                if ( isset($alter['SIGNUP_PAYMENTS']) && !empty($alter['SIGNUP_PAYMENTS']) ) {
                    zb_CashAddWithSignup($login, $cash, $operation, $cashtype, $note);
                } else {
                    zb_CashAdd($login, $cash, $operation, $cashtype, $note);
                }
                rcms_redirect("?module=addcash&username=" . $login);
                } else {
                    show_window('',  wf_modalOpened(__('Error'), __('Wrong format of a sum of money to pay'), '400', '200'));
                    log_register('BALANCEADDFAIL ('.$login.') WRONG SUMM `'.$cash.'`');
                }
            } else {
                show_window('', wf_modalOpened(__('Error'), __('You have not completed the required amount of money to deposit into account. We hope next time you will be more attentive.'), '400', '150'));
                log_register('BALANCEADDFAIL ('.$login.') EMPTY SUMM `'.$cash.'`');
            }
        }

        $current_balance = zb_UserGetStargazerData($login);
        $current_balance = $current_balance['Cash'];
        $useraddress = zb_UserGetFullAddress($login) . ' (' . $login . ')';

        // Profile:
        $profile=new UserProfile($login);
        show_window(__('User profile'), $profile->render());
        
        // Edit money form construct:
        $user_data    = zb_UserGetStargazerData($login);
        $user_tariff  = $user_data['Tariff'];
        $tariff_price = zb_TariffGetPrice($user_tariff);
        $fieldnames   = array('fieldname1' => __('Current Cash state'), 'fieldname2' => __('New cash'));
        $fieldkey     = 'newcash';
        
        $form  = '';
        $form .= web_EditorCashDataForm($fieldnames, $fieldkey, $useraddress, $current_balance, $tariff_price);

        // Check is user corporate?
        if ( $alter['USER_LINKING_ENABLED'] ) {
            if ( $alter['USER_LINKING_CASH'] ) {
                if ( cu_IsChild($login) ) {
                    $allchildusers = cu_GetAllLinkedUsers();
                    $parent_link   = $allchildusers[$login];
                    rcms_redirect("?module=corporate&userlink=" . $parent_link . "&control=cash");
                }

                if ( cu_IsParent($login) ) {
                    $allparentusers = cu_GetAllParentUsers();
                    $parent_link    = $allparentusers[$login];
                    rcms_redirect("?module=corporate&userlink=" . $parent_link . "&control=cash");
                }
            }
        }

        //payments deletion
            if (wf_CheckGet(array('paymentdelete'))) {
                    $deletePaymentId=vf($_GET['paymentdelete'],3);
                    $deletingAdmins=array();
                    $iCanDeletePayments=false;
                    $currentAdminLogin=whoami();
                    //extract admin logins
                    if (!empty($alter['CAN_DELETE_PAYMENTS'])) {
                        $deletingAdmins= explode(',', $alter['CAN_DELETE_PAYMENTS']);
                        $deletingAdmins= array_flip($deletingAdmins);
                    }
                    

                    if (isset($deletingAdmins[$currentAdminLogin])) {
                        $iCanDeletePayments=true;
                    }

                  if ($iCanDeletePayments) {

                     $queryDeletion="DELETE from `payments` WHERE `id`='".$deletePaymentId."' ;";
                     nr_query($queryDeletion);
                     log_register("PAYMENT DELETE [".$deletePaymentId."] (".$login.")");
                     rcms_redirect('?module=addcash&username='.$login.'#profileending');
                  } else {
                      log_register("PAYMENT UNAUTH DELETION ATTEMPT [".$deletePaymentId."] (".$login.")");
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
