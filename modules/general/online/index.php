<?php
if($system->checkForRight('ONLINE')) {
function stg_show_fulluserlist2() {
    $query="SELECT * from `users`";
    $query_fio="SELECT * from `realname`";
    $allusers=simple_queryall($query);
    $allfioz=simple_queryall($query_fio);
    $alter_conf=rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
    $fioz=array();
    if (!empty ($allfioz)) {
        foreach ($allfioz as $ia=>$eachfio) {
            $fioz[$eachfio['login']]=$eachfio['realname'];
          }
    }
   
   $detect_address=zb_AddressGetFulladdresslist();
    if ($alter_conf['USER_LINKING_ENABLED']) {
         $alllinkedusers=cu_GetAllLinkedUsers();
         $allparentusers=cu_GetAllParentUsers();
    }
   
   $totaltraff_i=0;
   $totaltraff_m=0;
   $totaltraff=0;
   $ucount=0;
   $inacacount=0;
   $tcredit=0;
   $tcash=0;
   
   // LAT column
   if ($alter_conf['ONLINE_LAT']) {
       $lat_col_head='<td>'.__('LAT').'</td>';
       $act_offset=1;
   } else {
       $lat_col_head='';
       $act_offset=0;
   }
   //online stars
   if ($alter_conf['DN_ONLINE_DETECT']) {
       $true_online_header='<td>'.__('Users online').'</td>';
       $true_online_selector=' col_'.(5+$act_offset).': "select",';
   } else {
       $true_online_header='';
       $true_online_selector='';
   }
   //extended filters
   if ($alter_conf['ONLINE_FILTERS_EXT']) {
       $extfilters=' <a href="javascript:showfilter();">'.__('Extended filters').'</a>';
   } else {
       $extfilters='';
   }
 
   
   $result=$extfilters;
   $result.='<table width="100%" class="sortable" id="onlineusers">';
   $result.='
  <tr class="row1">
  <td>'.__('Full address').'</td>
  <td>'.__('Real Name').'</td>
  <td>IP</ip></td>
  <td>'.__('Tariff').'</td>
  '.$lat_col_head.'
  <td>'.__('Active').'</td>
  '.$true_online_header.'
  <td>'.__('Traffic').'</td>
  <td>'.__('Balance').'</td>
  <td>'.__('Credit').'</td>
  
  </tr>';
   if (!empty ($allusers)) {
   foreach ($allusers as $io=>$eachuser) {
     $tinet=0;
     $ucount++;
     $cash=$eachuser['Cash'];
     $credit=$eachuser['Credit'];
     for ($classcounter=0;$classcounter<=9;$classcounter++) {
         $dc='D'.$classcounter.'';
         $uc='U'.$classcounter.'';
         $tinet=$tinet+($eachuser[$dc]+$eachuser[$uc]);
  
     }
     $totaltraff=$totaltraff+$tinet;
     $tcredit=$tcredit+$credit;
     $tcash=$tcash+$cash;
     
     $act=web_green_led().' '.__('Yes');
     //finance check
     if ($cash<'-'.$credit) {
     $act=web_red_led().' '.__('No');
     $inacacount++;
     }
     
     if ($alter_conf['ONLINE_LAT']) {
         $user_lat='<td>'.date("Y-m-d H:i:s",$eachuser['LastActivityTime']).'</td>';
     } else {
         $user_lat='';
     }
     
     //online check
     if ($alter_conf['DN_ONLINE_DETECT']) {
       if (file_exists(DATA_PATH.'dn/'.$eachuser['login'])) {
           $online_flag=1;
       } else {
           $online_flag=0;
       }
       $online_cell='<td sorttable_customkey="'.$online_flag.'">'.web_bool_star($online_flag,true).'</td>';
     } else {
         $online_cell='';
         $online_flag=0;
     }
     
     if ($alter_conf['ONLINE_LIGHTER']) {
         $lighter='onmouseover="this.className = \'row2\';" onmouseout="this.className = \'row3\';" ';
     } else {
         $lighter='';
     }
     
     //user linking indicator 
     if ($alter_conf['USER_LINKING_ENABLED']) {
  
         //is user child? 
         if (isset($alllinkedusers[$eachuser['login']])) {
             $corporate='<a href="?module=corporate&userlink='.$alllinkedusers[$eachuser['login']].'">'.web_corporate_icon().'</a>';
         } else {
             $corporate='';
         }
         
           //is  user parent?
          if (isset($allparentusers[$eachuser['login']])) {
              $corporate='<a href="?module=corporate&userlink='.$allparentusers[$eachuser['login']].'">'.web_corporate_icon('Corporate parent').'</a>';
          } 
         
     } else {
       $corporate='';  
     }
     
     $result.='
        <tr class="row3" '.$lighter.'>
         <td>
     <a href="?module=traffstats&username='.$eachuser['login'].'">'.  web_stats_icon().'</a>
     <a href="?module=userprofile&username='.$eachuser['login'].'">'.  web_profile_icon().'</a>
      '.$corporate.'
         '.@$detect_address[$eachuser['login']].'</td>
         <td>'.@$fioz[$eachuser['login']].'</td>
         <td sorttable_customkey="'.ip2int($eachuser['IP']).'">'.$eachuser['IP'].'</td>
         <td>'.$eachuser['Tariff'].'</td>
         '.$user_lat.'
         <td>'.$act.'</td>
         '.$online_cell.'
         <td sorttable_customkey="'.$tinet.'">'.stg_convert_size($tinet).'</td>
         <td>'.round($eachuser['Cash'],2).'</td>
         <td>'.round($eachuser['Credit'],2).'</td>

         </tr>
        ';
    }
    }
    $result.='
    </table>
    <table width="100%">
    <tr class="row1">
         <td>'.__('Total').': '.$ucount.'</td>
         <td>'.__('Active users').' '.($ucount-$inacacount).' / '.__('Inactive users').' '.$inacacount.'</td>
         <td>'.__('Traffic').': '.stg_convert_size($totaltraff).'</td>
         <td>'.__('Total').': '.round($tcash,2).'</td>
         <td>'.__('Credit total').': '.$tcredit.'</td>
         </tr>
        ';
    //extended filters again
    if ($alter_conf['ONLINE_FILTERS_EXT']) {
        $filtercode='
            <script language="javascript" type="text/javascript">
            //<![CDATA[
        function showfilter() {
          var onlinefilters = {
		btn: false,
		col_'.(4+$act_offset).': "select",
               '.$true_online_selector.'
		btn_text: ">"
	}
	setFilterGrid("onlineusers",0,onlinefilters);
        }
        //]]>
        </script>';
    } else {
        $filtercode='';
    }
    
    $result.='</table>'.$filtercode;
return ($result);
}


show_window(__('Users online'),stg_show_fulluserlist2());

}
else {
	show_error(__('Access denied'));
}
?>