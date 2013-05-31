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
$form=  web_EditorStringDataFormContract($fieldnames, $fieldkey, $useraddress, $current_contract);


show_window(__('Edit contract'), $form);

//contract date editing
//no crm mode required now
//if ($alter_conf['CRM_MODE']) {
   $allcontractdates=zb_UserContractDatesGetAll();
   if (isset($allcontractdates[$current_contract])) {
       $currentContractDate=$allcontractdates[$current_contract];
   } else {
       $currentContractDate='';
   }
   
   //someone creates new contractdate or changes old
   if (wf_CheckPost(array('newcontractdate'))) {
       if (empty($currentContractDate)) {
           zb_UserContractDateCreate($current_contract, $_POST['newcontractdate']);
       } else {
           zb_UserContractDateSet($current_contract, $_POST['newcontractdate']);
       }
       //back to fresh form
       rcms_redirect("?module=contractedit&username=".$login);
   }
   
   
   //editing form
   show_window(__('User contract date'),web_UserContractDateChangeForm($current_contract, $currentContractDate));
       
//}
   //end of checking crm mode for contract date


show_window('',web_UserControls($login));


}

} else {
      show_error(__('You cant control this module'));
}

?>
