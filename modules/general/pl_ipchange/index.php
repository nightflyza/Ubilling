<?php
if (cfr('PLIPCHANGE')) {
    
    
    if (isset($_GET['username'])) {
        $login=mysql_real_escape_string($_GET['username']);
        $current_ip=zb_UserGetIP($login);
        $current_mac=zb_MultinetGetMAC($current_ip);
        
        function web_IPChangeFormService() {
            global $current_ip;
            $result='';
            $result.='<form action="" method="POST">';
            $result.=multinet_service_selector().' '.__('New IP service');
            $result.='<br><br> <input type="submit" value="'.__('Save').'">';
            $result.='</form>';
            
            return($result);
        }
        
        function zb_IPChange($current_ip,$current_mac,$new_multinet_id,$new_free_ip,$login) {
            global $billing;
            $billing->setip($login,$new_free_ip);
            multinet_delete_host($current_ip);
            multinet_add_host($new_multinet_id, $new_free_ip, $current_mac);
            multinet_rebuild_all_handlers();
            multinet_RestartDhcp();
            }
        
        if (isset ($_POST['serviceselect'])) {
            $debug_data='';
            $debug_data.='current IP: '.$current_ip.'<br>';
            $debug_data.='current MAC: '.$current_mac.'<br>';
            $debug_data.='new service ID: '.$_POST['serviceselect'].'<br>';
            $new_multinet_id=multinet_get_service_networkid($_POST['serviceselect']);
            $debug_data.='new net ID: '.$new_multinet_id.'<br>';
            @$new_free_ip=multinet_get_next_freeip('nethosts', 'ip', $new_multinet_id);
            if (empty ($new_free_ip)) {
            $alert='
            <script type="text/javascript">
                alert("'.__('Error').': '.__('No free IP available in selected pool').'");
            </script>
            ';
                print($alert);
                rcms_redirect("?module=multinet");
                die();
            }
            $debug_data.='new free IP: '.$new_free_ip.'<br>';
            deb($debug_data);
            zb_IPChange($current_ip, $current_mac, $new_multinet_id, $new_free_ip,$login);
            log_register("CHANGE MultiNetIP (".$login.") FROM ".$current_ip." ON ".$new_free_ip);
            rcms_redirect("?module=pl_ipchange&username=".$login);
        } else {
            show_window(__('Current user IP'),$current_ip);
            show_window(__('Change user IP'),web_IPChangeFormService());  
        }
        
        show_window('',  web_UserControls($login));
        
    }

} else {
      show_error(__('You cant control this module'));
}

?>
