<?php

if (cfr('ZBSMAN')) {
    
   function zb_GetUserStatsDeniedAll() {
       $access_raw=  zb_StorageGet('ZBS_DENIED');
       $result=array();
       if (!empty($access_raw)) {
           $access_raw=  base64_decode($access_raw);
           $access_raw= unserialize($access_raw);
           $result=$access_raw;
       } else {
           //first access
           $newarray=  serialize($result);
           $newarray= base64_encode($newarray);
           zb_StorageSet('ZBS_DENIED', $newarray);
       }
       return ($result);
    }
    
    function zb_SetUserStatsDenied($login) {
       $access=  zb_GetUserStatsDeniedAll();
       if (!empty($login)) {
           $access[$login]='NOP';
           $newarray=  serialize($access);
           $newarray= base64_encode($newarray);
           zb_StorageSet('ZBS_DENIED', $newarray);
           log_register("ZBSMAN SET DENIED (".$login.")");
       }
    }

    function zb_SetUserStatsUnDenied($login) {
      $access=  zb_GetUserStatsDeniedAll();
       if (!empty($login)) {
           if (isset($access[$login])) {
           unset($access[$login]);
           $newarray=  serialize($access);
           $newarray= base64_encode($newarray);
           zb_StorageSet('ZBS_DENIED', $newarray);
           log_register("ZBSMAN SET ALLOWED (".$login.")");
           }
       }
    }
    
       function zb_GetHelpdeskDeniedAll() {
       $access_raw=  zb_StorageGet('ZBS_HELP_DENIED');
       $result=array();
       if (!empty($access_raw)) {
           $access_raw=  base64_decode($access_raw);
           $access_raw= unserialize($access_raw);
           $result=$access_raw;
       } else {
           //first access
           $newarray=  serialize($result);
           $newarray= base64_encode($newarray);
           zb_StorageSet('ZBS_HELP_DENIED', $newarray);
       }
       return ($result);
    }
    
    function zb_SetHelpdeskDenied($login) {
       $access= zb_GetHelpdeskDeniedAll();
       if (!empty($login)) {
           $access[$login]='NOP';
           $newarray=  serialize($access);
           $newarray= base64_encode($newarray);
           zb_StorageSet('ZBS_HELP_DENIED', $newarray);
           log_register("ZBSMAN SET HELPDESKDENIED (".$login.")");
       }
    }

    function zb_SetHelpdeskUnDenied($login) {
      $access=  zb_GetHelpdeskDeniedAll();
       if (!empty($login)) {
           if (isset($access[$login])) {
           unset($access[$login]);
           $newarray=  serialize($access);
           $newarray= base64_encode($newarray);
           zb_StorageSet('ZBS_HELP_DENIED', $newarray);
           log_register("ZBSMAN SET ALLOWED (".$login.")");
           }
       }
    }
    
    
    function web_ZbsManEditForm($login) {
        $access=  zb_GetUserStatsDeniedAll();
        $helpdesk= zb_GetHelpdeskDeniedAll();
        
        if (isset($access[$login])) {
            $checked_us=true;
        } else {
            $checked_us=false;
        }
        
        if (isset($helpdesk[$login])) {
            $checked_hd=true;
        } else {
            $checked_hd=false;
        }
        
        $inputs=  wf_CheckInput('access_denied', __('Userstats access denied for this user'), true, $checked_us);
        $inputs.= wf_CheckInput('helpdesk_denied', __('Helpdesk access denied for this user'), true, $checked_hd);
        $inputs.= wf_HiddenInput('zbsman_change', 'true');
        $inputs.= wf_Submit(__('Save'));
        
        $result=  wf_Form('', "POST", $inputs, 'glamour');
        
        return ($result);
    }
    
    function web_ZbsManUserLists() {
         $access=  zb_GetUserStatsDeniedAll();
         $access=  array_keys($access);
         $helpdesk= zb_GetHelpdeskDeniedAll();
         $helpdesk= array_keys($helpdesk);
         
         if (!empty($access)) {
             show_window(__('Users that cant access Userstats'),web_UserArrayShower($access));
         }
         
         if (!empty($helpdesk)) {
             show_window(__('Users that cant access ticketing service'),web_UserArrayShower($helpdesk));
         }
         
    }
    
    
    /*
     * Controller part
     */
    
    if (isset($_GET['username'])) {
        $login=  DB_real_escape_string($_GET['username']);
        
        
        if (wf_CheckPost(array('zbsman_change'))) {
        //set user denied
        if (wf_CheckPost(array('access_denied'))) {
            zb_SetUserStatsDenied($login);
            rcms_redirect("?module=pl_zbsman&username=".$login);
        } else {
            zb_SetUserStatsUnDenied($login);
            rcms_redirect("?module=pl_zbsman&username=".$login);
            
        }
        
        //set user helpdesk denied
        if (wf_CheckPost(array('helpdesk_denied'))) {
            zb_SetHelpdeskDenied($login);
            rcms_redirect("?module=pl_zbsman&username=".$login);
        } else {
            zb_SetHelpdeskUnDenied($login);
            rcms_redirect("?module=pl_zbsman&username=".$login);
            
        }
        
        
        }
        
        //interface
        show_window(__('Userstats access controls'),web_ZbsManEditForm($login));
        web_ZbsManUserLists();
        show_window('', web_UserControls($login));
       
        
        
        
    } else {
        show_error(__('Strange exeption'));
    }
    
    
} else {
     show_error(__('You cant control this module'));
}
?>
