<?php
if (cfr('TARIFFEDIT')) {

if (isset ($_GET['username'])) {
    $login=vf($_GET['username']);
       // change tariff  if need
       if (isset ($_POST['newtariff'])) {
        $alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
        $tariff=$_POST['newtariff'];
        if (!isset($_POST['nextmonth'])) {
        $billing->settariff($login,$tariff);
        log_register('CHANGE Tariff ('.$login.') ON '.$tariff);
        //optional user reset
        if ($alter_conf['TARIFFCHGRESET']) {
        $billing->resetuser($login);
        log_register('RESET User ('.$login.')');
        }
        
        } else {
            $billing->settariffnm($login,$tariff);
            log_register('CHANGE TariffNM ('.$login.') ON '.$tariff);
        }
    }

    $current_tariff=zb_UserGetStargazerData($login);
    $current_tariff=$current_tariff['Tariff'];
    $useraddress=zb_UserGetFullAddress($login).' ('.$login.')';

    
 //check is user corporate?
$alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
if ($alter_conf['USER_LINKING_ENABLED']) {
    if ($alter_conf['USER_LINKING_TARIFF']) {
        if (cu_IsChild($login)) {
            $allchildusers=cu_GetAllLinkedUsers();
            $parent_link=$allchildusers[$login];
            rcms_redirect("?module=corporate&userlink=".$parent_link."&control=tariff");
        }
        
        if (cu_IsParent($login)) {
            $allparentusers=cu_GetAllParentUsers();
            $parent_link=$allparentusers[$login];
            rcms_redirect("?module=corporate&userlink=".$parent_link."&control=tariff");
        }
        
    }
}
    

// Edit form construct
$fieldname=__('Current tariff');
$fieldkey='newtariff';
//old style tariff selector
if (!isset ($_GET['oldform'])) {
    $form=web_EditorTariffFormWithoutLousy($fieldname, $fieldkey, $useraddress, $current_tariff);
} else {
    $form=web_EditorTariffForm($fieldname, $fieldkey, $useraddress, $current_tariff);
}
 

$form.=wf_Link('?module=tariffedit&username='.$login, 'Popular tariff selector', false, 'ubButton');
$form.=wf_Link('?module=tariffedit&username='.$login.'&oldform=true', 'Full tariff selector', false, 'ubButton');
$form.='<br><br>';

$form.=web_UserControls($login);
// show form
show_window(__('Edit tariff'), $form);
}

} else {
      show_error(__('You cant control this module'));
}

?>
