<?php
// check for right of current admin on this module
if (cfr('ANNIHILATION')) {
   
    function zb_AnnihilateUser($login) {
        global $billing;
        $alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
        $user_ip=zb_UserGetIP($login);
        $user_aptdata=zb_AddressGetAptData($login);
        @$user_aptid=$user_aptdata['aptid'];
        zb_AddressDeleteApartment($user_aptid);
        zb_AddressOrphanUser($login);
        zb_UserDeleteEmail($login);
        zb_UserDeleteNotes($login);
        zb_UserDeletePhone($login);
        zb_UserDeleteRealName($login);
        zb_UserDeleteSpeedOverride($login);
        if (!$alter_conf['STRICT_CONTRACTS_PROTECT']) {
        zb_UserDeleteContract($login);
        }
        zb_VserviceCashClear($login);
        log_register("DELETE VCASH ".$login);
        cf_FlushAllUserCF($login);
        zb_FlushAllUserTags($login);
		vlan_delete_host($login);
        multinet_delete_host($user_ip);
        multinet_rebuild_all_handlers();
        //destroy stargazer user
        $billing->deleteuser($login);
        log_register("StgUser DELETE ".$login);
    }
    
    function web_AnnihilateFormShow($login) {
        $alladdress=zb_AddressGetFulladdresslist();
        $form='
            '.__('Be careful, this module permanently deletes user and all data associated with it. Opportunities to raise from the dead no longer.').' <br>
            '.__('To ensure that we have seen the seriousness of your intentions to enter the word сonfirm the field below.').'
            <br>
            <br>
            <form action="" method="POST">
            <input type="text" name="confirmation" autocomplete="off" > 
            <input type="hidden" name="anihilation" value="true">
            <br>
            <br>
            <input type="submit" value="'.__('I really want to stop suffering User').'">
            </form>
            ';
        show_window(__('Deleting user').' '.@$alladdress[$login].' ('.$login.')', $form);
    }
    
    if (isset($_GET['username'])) {
        
    $login=$_GET['username'];    
    web_AnnihilateFormShow($login);
    //check for delete confirmation
    if (isset ($_POST['anihilation'])) {
        if (isset($_POST['confirmation'])) {
            if ($_POST['confirmation']=='confirm') {
                zb_AnnihilateUser($login);
                rcms_redirect("?module=index");
            }
        }
    }
    
    show_window('',web_UserControls($login));
    }
    
    
} else {
      show_error(__('You cant control this module'));
}

?>
