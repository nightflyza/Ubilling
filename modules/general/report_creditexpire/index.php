<?php
if (cfr('REPORTCREXP')) {

   
    function zb_GetAllCreditExpireUsers() {
        $query="SELECT `login`,`Tariff`,`Cash`,`Credit`,`CreditExpire` from `users` WHERE `CreditExpire`!='0'";
        $result=simple_queryall($query);
        return ($result);
    } 
    
    function web_ShowAllCrExpireUsers() {
       $allusers=zb_GetAllCreditExpireUsers();
       $allrealnames=zb_UserGetAllRealnames();
       $alladdress=zb_AddressGetFulladdresslist();
       $result='<table width="100%" class="sortable" border="0">';
       $result.='
                   <tr class="row1">
                   <td>'.__('Login').'</td>
                   <td>'.__('Tariff').'</td>
                   <td>'.__('Cash').'</td>
                   <td>'.__('Credit').'</td>
                   <td>'.__('Credit expire').'</td>
                   <td>'.__('Address').'</td>
                   <td>'.__('Real name').'</td>
                    <td>'.__('Actions').'</td>
                   </tr>
                   ';
       if (!empty ($allusers)) {
           foreach ($allusers as $io=>$eachuser) {
               $result.='
                   <tr class="row3">
                   <td>'.$eachuser['login'].'</td>
                   <td>'.$eachuser['Tariff'].'</td>
                   <td>'.$eachuser['Cash'].'</td>
                   <td>'.$eachuser['Credit'].'</td>
                   <td>'.date("Y-m-d",$eachuser['CreditExpire']).'</td>
                   <td>'.@$alladdress[$eachuser['login']].'</td>
                   <td>'.@$allrealnames[$eachuser['login']].'</td>
                   <td>
                   <a href="?module=userprofile&username='.$eachuser['login'].'">'.  web_profile_icon().'</a>
                   <a href="?module=creditexpireedit&username='.$eachuser['login'].'"><img src="skins/icon_calendar.gif" border="0" title="'.__('Credit expire').'"></a>
                   </td>
                   </tr>
                   ';
           }
       }
       $result.='</table>';
       
       return($result);
    }

    show_window(__('Users with their credits expires'),web_ShowAllCrExpireUsers());

} else {
      show_error(__('You cant control this module'));
}

?>
