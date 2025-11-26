<?php
if (cfr('CREDIT')) {
if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);
       // change credit expire if need
       if (isset ($_POST['newcreditexpire'])) {
        $creditexpire=$_POST['newcreditexpire'];
        $billing->setcreditexpire($login,$creditexpire);
        log_register('USER CREDITEXPIRE CHANGE (' . $login . ') ON `' . $creditexpire . '`');
        rcms_redirect("?module=creditexpireedit&username=".$login);
    }

      $current_creditexpire=zb_UserGetStargazerData($login);
    $current_creditexpire=$current_creditexpire['CreditExpire'];
    if ($current_creditexpire) {
    $current_creditexpire=date("Y-m-d",$current_creditexpire);
    } else {
        $current_creditexpire=__('No');
    }
    $useraddress=zb_UserGetFullAddress($login).' ('.$login.')';


// Edit form construct
$fieldnames=array('fieldname1'=>__('Current credit expire'),'fieldname2'=>__('New credit expire'));
$fieldkey='newcreditexpire';
$form=web_EditorDateDataForm($fieldnames, $fieldkey, $useraddress, $current_creditexpire);
$form.=web_UserControls($login);

show_window(__('Edit credit expire'), $form);
}

} else {
      show_error(__('You cant control this module'));
}

?>
