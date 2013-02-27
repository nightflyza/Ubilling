<?php
if (cfr('PLCHECKER')) {
    
    function zb_plcheckfield($table,$login) {
        $login=vf($login);
        $table=vf($table);
        $query="SELECT `id` from `".$table."` where `login`='".$login."'";
        $result= simple_queryall($query);
        $return=!empty ($result);
        return ($return);
        }
    
    function zb_plchecknethost($login) {
        $login=vf($login);
        $ip=zb_UserGetIP($login);
        $query="SELECT `id` from `nethosts` where `ip`='".$ip."'";
        $result= simple_queryall($query);
        $return=!empty ($result);
        return ($return);
        }
        
     function web_plfixerform($login,$field,$flag) {
         $result='';
         if (!$flag) {
         $result.='
             <form action="" method="POST">
             <input type="hidden" name="fixme" value="'.$field.'">
             <input type="submit" value="'.__('Fix').'">
             </form>
             ';    
         }
         return($result);
     }
     
     function zb_plfixer($login,$field) {
         if ($field=='emails') {
             zb_UserCreateEmail($login, '');
         }
         if ($field=='contracts') {
             zb_UserCreateContract($login, '');
         }
          if ($field=='phones') {
              zb_UserCreatePhone($login, '', '');
         }
          if ($field=='realname') {
              zb_UserCreateRealName($login, '');
         }
          if ($field=='userspeeds') {
              zb_UserCreateSpeedOverride($login, '0');
         }
          if ($field=='nethosts') {
              rcms_redirect("?module=pl_ipchange&username=".$login);
         }
         rcms_redirect("?module=pl_checker&username=".$login);
     }
        
    function web_plchecker($login) {
        $login=vf($login);
        $result='<table width="100%" border="0">';
        $emails=zb_plcheckfield('emails',$login);
        $contracts=zb_plcheckfield('contracts', $login);
        $phones=zb_plcheckfield('phones', $login);
        $realname=zb_plcheckfield('realname', $login);
        $userspeeds=zb_plcheckfield('userspeeds', $login);
        $nethosts=zb_plchecknethost($login);
        $result.='
            <tr class="row1">
                <td>'. __('Current value').'</td>
                <td>'.__('Parameter').'</td>
                <td>'.__('Actions').'</td>
            <tr>
             ';
        $result.='
            <tr class="row3">
                <td>'.  web_bool_led($emails).'</td>
                <td>'.__('Email').'</td>
                <td>'.  web_plfixerform($login, 'emails', $emails).'</td>
            </tr>
             <tr class="row3">
                <td>'.  web_bool_led($contracts).'</td>
                <td>'.__('Contract').'</td>
                <td>'.  web_plfixerform($login, 'contracts', $contracts).'</td>
             </tr>
                <tr class="row3">
                <td>'.  web_bool_led($phones).'</td>
                <td>'.__('Phone').'/'.__('Mobile').'</td>
                <td>'.  web_plfixerform($login, 'phones', $phones).'</td>
            </tr>
            <tr class="row3">
                <td>'.  web_bool_led($realname).'</td>
                <td>'.__('Real Name').'</td>
                <td>'.  web_plfixerform($login, 'realname', $realname).'</td>
            </tr>
            <tr class="row3">
                <td>'.  web_bool_led($userspeeds).'</td>
                <td>'.__('Speed override').'</td>
                <td>'.  web_plfixerform($login, 'userspeeds', $userspeeds).'</td>
            </tr>
            <tr class="row3">
                <td>'.  web_bool_led($nethosts).'</td>
                <td>'.__('Network').'</td>
                <td>'.  web_plfixerform($login, 'nethosts', $nethosts).'</td>
            </tr>
            ';
        $result.='</table>';
        $result.=web_UserControls($login);
        return($result);
    }

    if (isset($_GET['username'])) {
        $login=$_GET['username'];
        
            if (isset($_POST['fixme'])) {
                zb_plfixer($login, $_POST['fixme']);
            }
        
        show_window(__('User integrity checker'),web_plchecker($login));
        
    }

} else {
      show_error(__('You cant control this module'));
}

?>
