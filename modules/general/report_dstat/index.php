<?php
if (cfr('REPORTDSTAT')) {

   
    function zb_GetAllDstatUsers() {
        $query="SELECT `login`,`Tariff` from `users` WHERE `DisabledDetailStat`!='1'";
        $result=simple_queryall($query);
        return ($result);
    } 
    
    function web_ShowAllDstatUsers () {
       $allusers=zb_GetAllDstatUsers();
       $allrealnames=zb_UserGetAllRealnames();
       $alladdress=zb_AddressGetFulladdresslist();
       $result='<table width="100%" class="sortable" border="0">';
       $result.='
                   <tr class="row1">
                   <td>'.__('Login').'</td>
                   <td>'.__('Tariff').'</td>
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
                   <td>'.@$alladdress[$eachuser['login']].'</td>
                   <td>'.@$allrealnames[$eachuser['login']].'</td>
                   <td>
                   <a href="?module=userprofile&username='.$eachuser['login'].'">'.  web_profile_icon().'</a>
                   <a href="?module=dstatedit&username='.$eachuser['login'].'"><img src="skins/icon_stats.gif" border="0" title="'.__('Detailed stats').'"></a>
                   </td>
                   </tr>
                   ';
           }
       }
       $result.='</table>';
       
       return($result);
    }

    show_window(__('Users for which detailed statistics enabled'),web_ShowAllDstatUsers());

} else {
      show_error(__('You cant control this module'));
}

?>
