<?php
if (cfr('CASH')) {

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);
   // change finance state if need
       if (isset ($_POST['newcash'])) {
        //init
        $cash=$_POST['newcash'];
        $operation=vf($_POST['operation']);
        $cashtype=vf($_POST['cashtype']);
        $note='';
        if (isset($_POST['newpaymentnote'])) {
            $note=mysql_real_escape_string($_POST['newpaymentnote']);
        }
        
        //empty cash hotfix
        if ($cash!='') {
         zb_CashAdd($login, $cash, $operation, $cashtype, $note);
         rcms_redirect("?module=addcash&username=".$login);
        } else {
            show_window('',wf_modalOpened(__('Error'), __('You have not completed the required amount of money to deposit into account. We hope next time you will be more attentive.'), '400', '150'));
        }
        
    }

    $current_balance=zb_UserGetStargazerData($login);
    $current_balance=$current_balance['Cash'];
    $useraddress=zb_UserGetFullAddress($login).' ('.$login.')';

// profile
show_window(__('User profile'),web_ProfileShow($login));
// Edit money form construct
$user_data=  zb_UserGetStargazerData($login);
$user_tariff=$user_data['Tariff'];
$tariff_price=  zb_TariffGetPrice($user_tariff);
$fieldnames=array('fieldname1'=>__('Current Cash state'),'fieldname2'=>__('New cash'));
$fieldkey='newcash';
$form='';
$form.=web_EditorCashDataForm($fieldnames, $fieldkey, $useraddress, $current_balance, $tariff_price);

//check is user corporate?
$alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
if ($alter_conf['USER_LINKING_ENABLED']) {
    if ($alter_conf['USER_LINKING_CASH']) {
        if (cu_IsChild($login)) {
            $allchildusers=cu_GetAllLinkedUsers();
            $parent_link=$allchildusers[$login];
            rcms_redirect("?module=corporate&userlink=".$parent_link."&control=cash");
        }
        
        if (cu_IsParent($login)) {
            $allparentusers=cu_GetAllParentUsers();
            $parent_link=$allparentusers[$login];
            rcms_redirect("?module=corporate&userlink=".$parent_link."&control=cash");
        }
        
    }
}

//$form.=web_UserControls($login);
// show form
show_window(__('Money'), $form);
// previouse payments
show_window(__('Previous payments'),  web_PaymentsByUser($login));
}

} else {
      show_error(__('You cant control this module'));
}

?>
