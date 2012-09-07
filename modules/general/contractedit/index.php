<?php
if (cfr('CONTRACT')) {
$alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);
       // change contract if need
       if (isset ($_POST['newcontract'])) {
        $contract=$_POST['newcontract'];
        //strict unique check
        if ($alter_conf['STRICT_CONTRACTS_UNIQUE']) {
            $allcontracts=zb_UserGetAllContracts();
            if (isset($allcontracts[$contract])) {
                show_window(__('Error'),__('This contract is already used'));
            } else {
                zb_UserChangeContract($login, $contract);
                rcms_redirect("?module=contractedit&username=".$login);
            }
        } else {
            zb_UserChangeContract($login, $contract);
            rcms_redirect("?module=contractedit&username=".$login);
        }
        
    }

    $current_contract=zb_UserGetContract($login);
    $useraddress=zb_UserGetFullAddress($login).' ('.$login.')';


// Edit form construct
$fieldnames=array('fieldname1'=>__('Current contract'),'fieldname2'=>__('New contract'));
$fieldkey='newcontract';
$form=web_EditorStringDataFormCredit($fieldnames, $fieldkey, $useraddress, $current_contract);
$form.=web_UserControls($login);

show_window(__('Edit contract'), $form);
}

} else {
      show_error(__('You cant control this module'));
}

?>
