<?php
function web_UserControls($login) {
    $controls='
        <a href="?module=userprofile&username='.$login.'"><img src="skins/icon_user_big.gif" width="48" border="0">'.__('Back to user profile').'</a></div>
        <br>
        <a href="?module=useredit&username='.$login.'"><img src="skins/icon_user_edit_big.gif"  width="48" border="0">'.__('Back to user edit').'</a></div>
        ';
    return($controls);
}

function web_delete_icon($title='Delete') {
    $icon='<img src="skins/icon_del.gif" border="0" title="'.__($title).'">';
    return($icon);
}

function web_add_icon($title='Add') {
    $icon='<img src="skins/icon_add.gif" border="0" title="'.__($title).'">';
    return($icon);
}


function web_edit_icon($title='Edit') {
    $icon='<img src="skins/icon_edit.gif" border="0" title="'.__($title).'">';
    return($icon);
}

function web_key_icon($title='Password') {
    $icon='<img src="skins/icon_key.gif" border="0" title="'.__($title).'">';
    return($icon);
}

function web_street_icon ($title='Street') {
    $icon='<img src="skins/icon_street.gif" border="0" title="'.__($title).'">';
    return($icon);
}

function web_city_icon ($title='City') {
    $icon='<img src="skins/icon_city.gif" border="0" title="'.__($title).'">';
    return($icon);
}
function web_build_icon($title='Builds') {
    $icon='<img src="skins/icon_build.gif" border="0" title="'.__($title).'">';
    return($icon);
}

function web_ok_icon($title='Ok') {
    $icon='<img src="skins/icon_ok.gif" border="0" title="'.__($title).'">';
    return($icon);
}

function web_profile_icon($title='Profile') {
    $icon='<img src="skins/icon_user.gif" border="0" title="'.__($title).'">';
    return($icon);
}

function web_stats_icon($title='Stats') {
    $icon='<img src="skins/icon_stats.gif" border="0" title="'.__($title).'">';
    return($icon);
}

function web_corporate_icon($title='Corporate') {
    $icon='<img src="skins/corporate_small.gif" border="0" title="'.__($title).'">';
    return($icon);
}

function web_green_led() {
    $icon='<img src="skins/icon_active.gif" border="0">';
    return($icon);
}

function web_red_led() {
    $icon='<img src="skins/icon_inactive.gif" border="0">';
    return($icon);
}

function web_star() {
    $icon='<img src="skins/icon_star.gif" border="0">';
    return($icon);
}

function web_star_black() {
    $icon='<img src="skins/icon_nostar.gif" border="0">';
    return($icon);
}

 function web_bool_led($flag,$text=false) {
     if ($text) {
         $no=' '.__('No').' ';
         $yes=__('Yes').' ';
     } else {
         $no='';
         $yes='';
     }     
     $led=$no.web_red_led();
     
     if ($flag) {
     $led=$yes.web_green_led();
     }
     
     return($led);
 }
 
  function web_bool_star($flag,$text=false) {
     if ($text) {
         $no=' '.__('No').' ';
         $yes=__('Yes').' ';
     } else {
         $no='';
         $yes='';
     }     
     $led=$no.web_star_black();
     
     if ($flag) {
     $led=$yes.web_star();
     }
     
     return($led);
 }

 
//return current locale
function curlang() {
    global $system;
    $result=$system->language;
    $result=vf($result);
    return ($result);
}


function zb_GetNonUniquePasswordUsers() {
    $query_p="SELECT `Password`,count(*) as cnt from `users` GROUP BY `Password` having cnt >1;";
    $duppasswords=  simple_queryall($query_p);
    $result=array();
    if (!empty($duppasswords)) {
        foreach ($duppasswords as $io=>$each) {
            $query_l="SELECT `login` from `users` WHERE `Password`='".$each['Password']."'";
            $userlogins=  simple_queryall($query_l);
            if (!empty($userlogins)) {
                foreach ($userlogins as $ia=>$eachlogin) {
                    $result[]=$eachlogin['login'];
                }
            }
        }
    }
    return ($result);
}
    
function zb_CheckPasswordUnique($password) {
    $password=  mysql_real_escape_string($password);
    $query="SELECT `login` from `users` WHERE `Password`='".$password."'";
    $data=  simple_query($query);
    if (empty($data)) {
        return (true);
    } else {
        return (false);
    }
}
    
//function for show localized calendar control
function web_CalendarControl($field) {
//    $lang=curlang();
//    $result='
//        <script src="modules/jsc/CalendarControl_'.$lang.'.js" language="javascript"></script> 
//        <input name="'.$field.'"  onfocus="showCalendarControl(this);" type="text" size="10">
//        ';
    
    $result=  wf_DatePicker($field);
    return ($result);
}

function web_trigger($value) {
    if ($value) {
        $result=__('Yes');
    } else {
        $result=__('No');
    }
    return($result);
}

function web_EditorStringDataForm($fieldnames,$fieldkey,$useraddress,$olddata='') {
    $field1=$fieldnames['fieldname1'];
    $field2=$fieldnames['fieldname2'];
    $form='
        <form action="" method="POST">
        <table width="100%" border="0">
        <tr>
        <td class="row2">'.__('User').'</td>
        <td class="row3">'.$useraddress.'</td>
        </tr>
        <tr>
        <td class="row2">'.$field1.'</td>
        <td class="row3">'.$olddata.'</td>
        </tr>
        <tr>
        <td class="row2">'.$field2.'</td>
        <td class="row3"><input type="text" name="'.$fieldkey.'"></td>
        </tr>
        </table>
        <input type="submit" value="'.__('Change').'">
        </form>
        <br><br>
        ';
return($form);
}


//apt check javascript code
 function js_CashCheck($suspect) {
     $suspect=vf($suspect,3);
     
     $result='
       <script type="text/javascript">
        function cashsuspectalert() {
              alert(\''.__('You try to bring to account suspiciously large amount of money. We have nothing against, but please check that all is correct').'\');
        }

        function checkcashfield()
        {
        var cashfield=document.getElementById("cashfield").value;
        
        if (cashfield > '.$suspect.') {
            cashsuspectalert();
        }
       }
   </script>
        ';
     
     return ($result);
 }

function web_EditorStringDataFormPassword($fieldnames,$fieldkey,$useraddress,$olddata='') {
    $field1=$fieldnames['fieldname1'];
    $field2=$fieldnames['fieldname2'];
    $alterconf=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    if (isset($alterconf['PASSWORD_GENERATION_LENGHT'])) {
        
        if ($alterconf['PASSWORD_TYPE']) {
            $password_proposal=  zb_rand_string($alterconf['PASSWORD_GENERATION_LENGHT']);
        } else {
            $password_proposal=  zb_rand_digits($alterconf['PASSWORD_GENERATION_LENGHT']);
        }
        
    } else {
        //default size
        $password_proposal=  zb_rand_string(8);
    }
    

    $form='
        <form action="" method="POST">
        <table width="100%" border="0">
        <tr>
        <td class="row2">'.__('User').'</td>
        <td class="row3">'.$useraddress.'</td>
        </tr>
        <tr>
        <td class="row2">'.$field1.'</td>
        <td class="row3">'.$olddata.'</td>
        </tr>
        <tr>
        <td class="row2">'.$field2.'</td>
        <td class="row3"><input type="text" name="'.$fieldkey.'" value="'.$password_proposal.'"></td>
        </tr>
        </table>
        <input type="submit" value="'.__('Change').'">
        </form>
        <br><br>
        ';
return($form);
}

function web_EditorStringDataFormCredit($fieldnames,$fieldkey,$useraddress,$olddata='') {
    $field1=$fieldnames['fieldname1'];
    $field2=$fieldnames['fieldname2'];
    
    if (empty ($olddata)) {
    $allcontracts=zb_UserGetAllContracts();
    for ($i=1;$i<10000;$i++) {
        if (!isset($allcontracts[$i])) {
            $contract_proposal=$i;
            break;
        }
    }
    } else {
        $contract_proposal='';
    }
    
    $form='
        <form action="" method="POST">
        <table width="100%" border="0">
        <tr>
        <td class="row2">'.__('User').'</td>
        <td class="row3">'.$useraddress.'</td>
        </tr>
        <tr>
        <td class="row2">'.$field1.'</td>
        <td class="row3">'.$olddata.'</td>
        </tr>
        <tr>
        <td class="row2">'.$field2.'</td>
        <td class="row3"><input type="text" name="'.$fieldkey.'" value="'.$contract_proposal.'"></td>
        </tr>
        </table>
        <input type="submit" value="'.__('Change').'">
        </form>
        <br><br>
        ';
return($form);
}


function web_EditorStringDataFormMAC($fieldnames,$fieldkey,$useraddress,$olddata='') {
    $field1=$fieldnames['fieldname1'];
    $field2=$fieldnames['fieldname2'];
    $altconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
      //mac vendor search
      if ($altconf['MACVEN_ENABLED']) {
            $backlogin=$_GET['username'];
            // old style
           // $lookuplink=  wf_Link('?module=macvendor&mac='.$olddata.'&username='.$backlogin, wf_img("skins/macven.gif"), false, '');
              $vendorframe='<iframe src="?module=macvendor&mac='.$olddata.'&username='.$backlogin.'" width="360" height="160" frameborder="0"></iframe';
             $lookuplink=  wf_modal(wf_img('skins/macven.gif', __('Device vendor')), __('Device vendor'), $vendorframe, '', '400', '220');
        } else {
            $lookuplink='';
        }
    
    
    if ($altconf['MACCHANGERANDOMDEFAULT']) {
        // funny random mac, yeah? :)
        $randommac='14:'.'88'.':'.rand(10,99).':'.rand(10,99).':'.rand(10,99).':'.rand(10,99);
        if (zb_mac_unique($randommac)) {
            $newvalue=$randommac;
        } else {
            show_error('Oops');
            $newvalue='';
        }
    } else {
        $newvalue='';
    }
    $form='
        <form action="" method="POST">
        <table width="100%" border="0">
        <tr>
        <td class="row2">'.__('User').'</td>
        <td class="row3">'.$useraddress.'</td>
        </tr>
        <tr>
        <td class="row2">'.$field1.' '.$lookuplink.'</td>
        <td class="row3">'.$olddata.'</td>
        </tr>
        <tr>
        <td class="row2">'.$field2.'</td>
        <td class="row3"><input type="text" name="'.$fieldkey.'" value="'.$newvalue.'"></td>
        </tr>
        </table>
        <input type="submit" value="'.__('Change').'">
        </form>
        <br><br>
        ';
return($form);
}

function zb_NewMacSelect($name='newmac') {
    global $billing_config;
    $alter_conf=parse_ini_file(CONFIG_PATH.'alter.ini');
    $sudo=$billing_config['SUDO'];
    $cat=$billing_config['CAT'];
    $grep=$billing_config['GREP'];
    $tail=$billing_config['TAIL'];
    $leases=$alter_conf['NMLEASES'];
    $leasesmark=$alter_conf['NMLEASEMARK'];
    $command=$sudo.' '.$cat.' '.$leases.' | '.$grep.'  "'.$leasesmark.'" | '.$tail.' -n 200';
    $rawdata=shell_exec($command);
    $result='<select name="'.$name.'">';
    if (!empty ($rawdata)) {
    $cleardata=exploderows($rawdata);
    foreach ($cleardata as $eachline) {
     preg_match('/[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}:[a-f0-9]{2}/i', $eachline, $matches);
        if (!empty ($matches[0])) {
            $nmarr[]=$matches[0];
            $unique_nmarr=array_unique($nmarr);
        }
                
    }
    if (!empty ($unique_nmarr))  {
        foreach ($unique_nmarr as $newmac) {
                if (multinet_mac_free($newmac)) {
                $result.='<option value="'.$newmac.'">'.$newmac.'</option>';
             }
            }
          }
      
    }
    $result.='</select>';
        
   return($result);
}

function web_EditorStringDataFormMACSelect($fieldnames,$fieldkey,$useraddress,$olddata='') {
    $field1=$fieldnames['fieldname1'];
    $field2=$fieldnames['fieldname2'];
    //mac vendor search
    $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
      if ($alterconf['MACVEN_ENABLED']) {
            $backlogin=$_GET['username'];
            // old style
            //$lookuplink=  wf_Link('?module=macvendor&mac='.$olddata.'&username='.$backlogin, wf_img("skins/macven.gif"), false, '');
             $vendorframe='<iframe src="?module=macvendor&mac='.$olddata.'&username='.$backlogin.'" width="360" height="160" frameborder="0"></iframe';
             $lookuplink=  wf_modal(wf_img('skins/macven.gif', __('Device vendor')), __('Device vendor'), $vendorframe, '', '400', '220');
        } else {
            $lookuplink='';
        }
        
        
    $form='
        <form action="" method="POST">
        <table width="100%" border="0">
        <tr>
        <td class="row2">'.__('User').'</td>
        <td class="row3">'.$useraddress.'</td>
        </tr>
        <tr>
        <td class="row2">'.$field1.' '.$lookuplink.'</td>
        <td class="row3">'.$olddata.'</td>
        </tr>
        <tr>
        <td class="row2">'.$field2.'</td>
        <td class="row3">'.  zb_NewMacSelect().'</td>
        </tr>
        </table>
        <input type="submit" value="'.__('Change').'">
        </form>
        <br><br>
        ';
return($form);
}

function web_EditorDateDataForm($fieldnames,$fieldkey,$useraddress,$olddata='') {
    $field1=$fieldnames['fieldname1'];
    $field2=$fieldnames['fieldname2'];
    $form='
        <form action="" method="POST">
        <table width="100%" border="0">
        <tr>
        <td class="row2">'.__('User').'</td>
        <td class="row3">'.$useraddress.'</td>
        </tr>
        <tr>
        <td class="row2">'.$field1.'</td>
        <td class="row3">'.$olddata.'</td>
        </tr>
        <tr>
        <td class="row2">'.$field2.'</td>
        <td class="row3">'.web_CalendarControl($fieldkey).'</td>
        </tr>
        </table>
        <input type="submit" value="'.__('Change').'">
        </form>
        <br><br>
        ';
return($form);
}


function web_CashTypeSelector() {
    $allcashtypes=zb_CashGetAlltypes();
    $selector='<select name="cashtype">';
    if (!empty ($allcashtypes)) {
        foreach ($allcashtypes as $io=>$eachtype) {
            $selector.='<option value="'.$eachtype['id'].'">'.__($eachtype['cashtype']).'</option>';
        }
    }
    $selector.='</select>';
    return($selector);
}


 function zb_CheckTableExists($tablename) {
            $query="SELECT CASE WHEN (SELECT COUNT(*) AS STATUS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = (SELECT DATABASE()) AND TABLE_NAME = '".$tablename."') = 1 THEN (SELECT 1)  ELSE (SELECT 0) END AS result;";
            $result=simple_query($query);
            return ($result['result']);
        }

function web_EditorCashDataForm($fieldnames,$fieldkey,$useraddress,$olddata='',$tariff_price='') {
    $field1=$fieldnames['fieldname1'];
    $field2=$fieldnames['fieldname2'];
    
    if ($tariff_price!=0) {
        $expected_time=', '.__('which should be enough for another').' '.intval($olddata/$tariff_price).' '.__('months of service use');
    } else {
        $expected_time='';
    }
    //cash suspect checking 
    $alterconf=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    if ($alterconf['SUSP_PAYMENTS_NOTIFY']) {
        $suspnotifyscript=js_CashCheck($alterconf['SUSP_PAYMENTS_NOTIFY']);
        $cashfieldanchor='onchange="checkcashfield();"';
    } else {
        $suspnotifyscript='';
        $cashfieldanchor='';
    }
    
    $radio='
        <input type="radio" name="operation" value="add" CHECKED> '.__('Add cash').'
        <input type="radio" name="operation" value="correct"> '.__('Correct saldo').'
        <input type="radio" name="operation" value="mock"> '.__('Mock payment').'
        <input type="radio" name="operation" value="set"> '.__('Set cash').'
        ';
    
    $form='
        '.$suspnotifyscript.'
        <form action="" method="POST">
        <table width="100%" border="0">
        <tr>
        <td class="row2">'.__('User').'</td>
        <td class="row3">'.$useraddress.'</td>
        </tr>
        <tr>
        <td class="row2">'.$field1.'</td>
        <td class="row3"><b>'.$olddata.'</b>'.$expected_time.'</td>
        </tr>
        <tr>
        <td class="row2">'.$field2.'</td>
        <td class="row3"><input type="text" name="'.$fieldkey.'" size="5" id="cashfield" '.$cashfieldanchor.'> '.__('The expected payment').': '. $tariff_price.'</td>
        </tr>
        <tr>
        <td class="row2">'.__('Actions').'</td>
        <td class="row3">'.$radio.'</td>
        </tr>
         <tr>
        <td class="row2">'.__('Payment type').'</td>
        <td class="row3">'.  web_CashTypeSelector().'</td>
        </tr>
        <tr>
        <td class="row2">'.__('Payment notes').'</td>
        <td class="row3"><input type="text" name="newpaymentnote" size="40"></td>
        </tr>
        </table>
        <input type="submit" value="'.__('Change').'">
        </form>
        <br><br>
        ';
return($form);
}

function web_TriggerSelector($name,$state='') {
    if (!$state) {
        $noflag='SELECTED';
    } else {
        $noflag='';
    }
    $selector='
           <select name="'.$name.'">
                       <option value="1">'.__('Yes').'</option>
                       <option value="0" '.$noflag.'>'.__('No').'</option>
           </select>
        ';
    return ($selector);
}


function web_EditorTrigerDataForm($fieldname,$fieldkey,$useraddress,$olddata='') {
    $curstate=web_trigger($olddata);
    $form='
        <form action="" method="POST">
        <table width="100%" border="0">
        <tr>
        <td class="row2">'.__('User').'</td>
        <td class="row3">'.$useraddress.'</td>
        </tr>
         <tr>
        <td class="row2">'.$fieldname.'</td>
        <td class="row3">'.$curstate.'</td>
        </tr>
         <tr>
         <td class="row2">
         </td>
         <td class="row3">
          '.  web_TriggerSelector($fieldkey, $olddata).'
         </td>
         </tr>
         </table>
        <input type="submit" value="'.__('Change').'">
        </form>
        <br><br>
        ';
    return($form);
}

// list all tariff names
function zb_TariffsGetAll() {
    $query="SELECT `name` from `tariffs`";
    $alltariffs=simple_queryall($query);
    return ($alltariffs);
}


function web_tariffselector($fieldname='tariffsel') {
    $alltariffs=zb_TariffsGetAll();
    $selector='<select name="'.$fieldname.'">';
        if (!empty ($alltariffs)) {
            foreach ($alltariffs as $io=>$eachtariff) {
                $selector.='<option value="'.$eachtariff['name'].'">'.$eachtariff['name'].'</option>';
            }
        }
    $selector.='</select>';
    return($selector);
}

function web_tariffselectorNoLousy($fieldname='tariffsel') {
    $alltariffs=zb_TariffsGetAll();
    $allousytariffs=zb_LousyTariffGetAll();
    
    $selector='<select name="'.$fieldname.'">';
        if (!empty ($alltariffs)) {
            foreach ($alltariffs as $io=>$eachtariff) {
                if (!zb_LousyCheckTariff($eachtariff['name'], $allousytariffs)) {
                $selector.='<option value="'.$eachtariff['name'].'">'.$eachtariff['name'].'</option>';
                }
            }
        }
    $selector.='</select>';
    return($selector);
}

function web_EditorTariffForm($fieldname,$fieldkey,$useraddress,$olddata='') {
   if ($olddata=='*_NO_TARIFF_*') {
        $nm_flag='DISABLED';
    } else {
        $nm_flag='';
    }
    $form='
        <form action="" method="POST">
        <table width="100%" border="0">
        <tr>
        <td class="row2">'.__('User').'</td>
        <td class="row3">'.$useraddress.'</td>
        </tr>
         <tr>
        <td class="row2">'.$fieldname.'</td>
        <td class="row3">'.$olddata.'</td>
        </tr>
         <tr>
         <td class="row2" align="right">
          <label for="nm"> '.__('Next month').'
         <input type="checkbox"  name="nextmonth" id="nm" '.$nm_flag.'> 
         </label>
         </td>
         <td class="row3">
               '.web_tariffselector($fieldkey).'
         </td>
         </tr>
         </table>
         <br>
        <input type="submit" value="'.__('Change').'">
        </form>
        <br><br>
        ';
    return($form);
}

function web_EditorTariffFormWithoutLousy($fieldname,$fieldkey,$useraddress,$olddata='') {
    if ($olddata=='*_NO_TARIFF_*') {
        $nm_flag='DISABLED';
    } else {
        $nm_flag='';
    }
       
    $form='
        <form action="" method="POST">
        <table width="100%" border="0">
        <tr>
        <td class="row2">'.__('User').'</td>
        <td class="row3">'.$useraddress.'</td>
        </tr>
         <tr>
        <td class="row2">'.$fieldname.'</td>
        <td class="row3">'.$olddata.'</td>
        </tr>
         <tr>
         <td class="row2" align="right">
         <label for="nm"> '.__('Next month').'
         <input type="checkbox"  name="nextmonth" id="nm" '.$nm_flag.'> 
         </label>
         </td>
         <td class="row3">
               '.web_tariffselectorNoLousy($fieldkey).'
         </td>
         </tr>
         </table>
         <br>
        <input type="submit" value="'.__('Change').'">
        </form>
        <br><br>
        ';
    return($form);
}


function web_EditorTwoStringDataForm($fieldnames,$fieldkeys,$olddata) {
    $field1=$fieldnames['fieldname1'];
    $field2=$fieldnames['fieldname2'];
    $fieldkey1=$fieldkeys['fieldkey1'];
    $fieldkey2=$fieldkeys['fieldkey2'];
    $form='
        <form action="" method="POST">
        <table width="100%" border="0">
        <tr>
        <td class="row2">'.$field1.'</td>
        <td class="row3"><input type="text" name="'.$fieldkey1.'" value="'.$olddata[1].'"></td>
        </tr>
        <tr>
        <td class="row2">'.$field2.'</td>
        <td class="row3"><input type="text" name="'.$fieldkey2.'" value="'.$olddata[2].'"></td>
        </tr>
        </table>
        <input type="submit" value="'.__('Change').'">
        </form>
        <br><br>
        ';
return($form);
}


 function zb_TranslatePaymentNote($paynote,$allservicenames) {
          if ($paynote=='') {
                    $paynote=__('Internet');
                }
                  
                if (isset ($allservicenames[$paynote])) {
                    $paynote=$allservicenames[$paynote];
                }
                
                 if (ispos($paynote, 'CARD:')) {
                    $cardnum=explode(':', $paynote);
                    $paynote=__('Card')." ".$cardnum[1];
                 }
                 
                 if (ispos($paynote, 'SCFEE')) {
                    $paynote=__('Credit fee');
                 }
                 
                 if (ispos($paynote, 'AFFEE')) {
                    $paynote=__('Freezing fee');
                 }
                 
                 if (ispos($paynote, 'TCHANGE:')) {
                    $tariff=explode(':', $paynote);
                    $paynote=__('Tariff change')." ".$tariff[1];
                 }
                 
                 if (ispos($paynote, 'BANKSTA:')) {
                    $banksta=explode(':', $paynote);
                    $paynote=__('Bank statement')." ".$banksta[1];
                 }
                 
                 if (ispos($paynote, 'MOCK:')) {
                     $mock=  explode(':',$paynote);
                     $paynote=__('Mock payment').' '.$mock[1];
                 }
                 
                 if (ispos($paynote, 'BALANCESET:')) {
                     $balset=  explode(':',$paynote);
                     $paynote=__('Set cash').' '.$balset[1];
                 }
                 
                 if (ispos($paynote, 'DISCOUNT:')) {
                     $disountset=  explode(':',$paynote);
                     $paynote=__('Discount').' '.$disountset[1].'%';
                 }
                 
                 return ($paynote);
  }

    
    function web_TariffSpeedForm() {
        $alltariffnames_q="SELECT `name` from `tariffs`";
        $alltariffs=simple_queryall($alltariffnames_q);
        $allspeeds=zb_TariffGetAllSpeeds();
        $form='<table width="100%" class="sortable" border="0">';
        $form.='
                    <tr class="row1">
                        <td>
                        '.__('Tariff').'
                        </td>
                        <td>
                        '.__('Download speed').'
                        </td>
                        <td>
                        '.__('Upload speed').'
                        </td>
                        <td>
                        '.__('Actions').'
                        </td>
                    </tr>
                    ';
        if (!empty ($alltariffs)) {
            foreach ($alltariffs as $io=>$eachtariff) {
                $form.='
                    <tr class="row3">
                        <td>
                        '.$eachtariff['name'].'
                        </td>
                        <td>
                        '.@$allspeeds[$eachtariff['name']]['speeddown'].'
                        </td>
                        <td>
                        '.@$allspeeds[$eachtariff['name']]['speedup'].'
                        </td>
                        <td>
                        <a href="?module=tariffspeeds&tariff='.$eachtariff['name'].'">'.web_edit_icon().'</a>
                        </td>
                    </tr>
                    ';
            }
        }
        $form.='</table>';
        
        return($form);
    }
    
     function zb_ProfileGetStgData($login) {
        $login=vf($login);
        $query="SELECT * from `users` WHERE `login`='".$login."'";
        $userdata=simple_query($query);
        return($userdata);
    }

    function web_ProfileControls($login) {
        $login=vf($login);
        $default_controls='
        <table width="100%" bgcolor="#ffffff" border="0">
        <tbody><tr valign="bottom">
	<td><a href="?module=lifestory&username='.$login.'"><img src="skins/icon_orb_big.gif" title="'.__('User lifestory').'" border="0"></a>
	<br>'.__('Details').'
	</td>
	<td><a href="?module=traffstats&username='.$login.'"><img src="skins/icon_stats_big.gif" title="'.__('Traffic stats').'" border="0"></a>
	<br>'.__('Traffic stats').'
	</td>
	<td><a href="?module=addcash&username='.$login.'#profileending"><img src="skins/icon_cash_big.gif" title="'.__('Cash').'" border="0"></a>
	<br>'.__('Cash').'
	</td>
	<td><a href="?module=macedit&username='.$login.'"><img src="skins/icon_ether_big.gif" title="'.__('Change MAC').'" border="0"></a>
	<br>'.__('Change MAC').'
	</td>
	<td><a href="?module=binder&username='.$login.'"><img src="skins/icon_build_big.gif" title="'.__('Address').'" border="0"></a>
	<br>'.__('Address').'
	</td>
	<td><a href="?module=tariffedit&username='.$login.'"><img src="skins/icon_tariff_big.gif" title="'.__('Tariff').'" border="0"></a>
	<br>'.__('Tariff').'
	</td>
	<td><a href="?module=useredit&username='.$login.'"><img src="skins/icon_user_edit_big.gif" title="'.__('Edit user').'" border="0"></a>
	<br>'.__('Edit').'
	</td>
        <td><a href="?module=jobs&username='.$login.'"><img src="skins/worker.gif" title="'.__('Jobs').'" border="0"></a>
	<br>'.__('Jobs').'
	</td>
	<td><a href="?module=reset&username='.$login.'"><img src="skins/icon_reset_big.gif" title="'.__('Reset user').'" border="0"></a>
	<br>'.__('Reset user').'
	</td>
	</tr>
	</tbody></table>

            ';
        return($default_controls);
    }
    
    function zb_ProfilePluginsLoad() {
        $plugins=rcms_parse_ini_file(CONFIG_PATH."plugins.ini", true);
        return($plugins);
    }
    
        function web_ProfilePluginsShowOverlay($login,$overlaydata) {
        $login=vf($login);
        $plugins=rcms_parse_ini_file(CONFIG_PATH.$overlaydata,true);
        $result='<table width="100%" height="100%" border="0">
            <tr>
            <td valign="middle" align="center" >
            ';
        if (!empty ($plugins)) {
            foreach ($plugins as $io=>$eachplugin) {
              $result.='<div style="width: 150px; height: 150px; float: left;"> <a href="?module='.$io.'&username='.$login.'" title="'.__($eachplugin['name']).'"><img src="skins/'.$eachplugin['icon'].'"  border="0"></a> </div>';   
            }
        }
        $result.='</td></tr></table>';
        return($result);
    }
    
    function web_ProfilePluginsShow($login) {
        $login=vf($login);
        $plugins=zb_ProfilePluginsLoad();
        $result='';
        if (!empty ($plugins)) {
            foreach ($plugins as $io=>$eachplugin) {
             if (isset($eachplugin['overlay'])) {
             $overlaydata=web_ProfilePluginsShowOverlay($login, $eachplugin['overlaydata']).'<br><br>';
             $result.=wf_modal('<img src="skins/'.$eachplugin['icon'].'"  border="0" title="'.__($eachplugin['name']).'">', __($eachplugin['name']), $overlaydata, '', 800, 400);   
             } else {
              $result.='<a href="?module='.$io.'&username='.$login.'" title="'.__($eachplugin['name']).'"><img src="skins/'.$eachplugin['icon'].'"  border="0"></a> <br><br>';   
             }
            }
        }
        
        return($result);
    }
    
  
    
    function web_ProfileShow($login) {
        $alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
        $hightlight_start='';
        $hightlight_end='';
        $profile_plugins='';
        if ($alter_conf['HIGHLIGHT_IMPORTANT']) {
            $hightlight_start='<b>';
            $hightlight_end='</b>';
        }
        $userdata=zb_ProfileGetStgData($login);
        $alladdress=zb_AddressGetFullCityaddresslist();
        @$useraddress=$alladdress[$login];
        $realname=zb_UserGetRealName($login);
        $phone=zb_UserGetPhone($login);
        $mobile=zb_UserGetMobile($login);
        $contract=zb_UserGetContract($login);
        $mail=zb_UserGetEmail($login);
        $aptdata=zb_AddressGetAptData($login);
        $speedoverride=zb_UserGetSpeedOverride($login);
        $mac=zb_MultinetGetMAC($userdata['IP']);
        if ($alter_conf['MACVEN_ENABLED']) {
            $vendorframe='<iframe src="?module=macvendor&mac='.$mac.'&username='.$login.'" width="360" height="160" frameborder="0"></iframe';
            $lookuplink=  wf_modal(wf_img('skins/macven.gif', __('Device vendor')), __('Device vendor'), $vendorframe, '', '400', '220');
        } else {
            $lookuplink='';
        }
        
        $creditexpire=$userdata['CreditExpire'];
        if ($creditexpire>0) {
            $creditexpire=date("Y-m-d",$creditexpire);
        } else {
            $creditexpire=__('No');
        }
        if ($alter_conf['PASSWORDSHIDE']) {
            $userdata['Password']=__('Hidden');
        }
        if ($userdata['LastActivityTime']!=0) {
            $lat=date("Y-m-d H:i:s",$userdata['LastActivityTime']);
        } else {
            $lat='';
        }
        $act='<img src="skins/icon_active.gif" border="0"> '.__('Yes');
         if ($userdata['Cash']<'-'.$userdata['Credit']) {
                $act='<img src="skins/icon_inactive.gif" border="0"> '.__('No');
         }
         
         if ($alter_conf['PROFILE_PLUGINS']) {
             $profile_plugins=web_ProfilePluginsShow($login);
         }
         
        // corporate user check
        $profile='';
        if ($alter_conf['USER_LINKING_ENABLED']) {
            $alllinkedusers=cu_GetAllLinkedUsers();
         if (isset ($alllinkedusers[$login])) {
           $parent_login=cu_GetParentUserLogin($alllinkedusers[$login]);
           $profile='<a href="?module=corporate&userlink='.$alllinkedusers[$login].'">
                      <img src="skins/corporate_small.gif"  border="0">
                      '.__('User linked with').': '.@$alladdress[$parent_login].'
                      
                      </a>';
           
         } 
        } 
        
        //check is user corporate parent?
        if ($alter_conf['USER_LINKING_ENABLED']) {
            $allparentusers=cu_GetAllParentUsers();
            if (isset ($allparentusers[$login])) {
                if (($_GET['module']!='corporate') AND ($_GET['module']!='addcash')) {
                rcms_redirect("?module=corporate&userlink=".$allparentusers[$login]);
                }
            }
            
        }
        
        //cosmetic issues
        if ($userdata['Passive']) {
            $passiveicon=  wf_img('skins/icon_passive.gif').' ';
        } else {
            $passiveicon='';
        }
        
          if ($userdata['Down']) {
            $downicon=  wf_img('skins/icon_down.gif').' ';
        } else {
            $downicon='';
        }
        
        //profile task creation icon
        if ($alter_conf['CREATETASK_IN_PROFILE']) {
           $shortaddress=  zb_UserGetFullAddress($login);
           $taskcreatelink=wf_modal(wf_img('skins/createtask.gif', __('Create task')), __('Create task'), ts_TaskCreateFormProfile($shortaddress,$mobile,$phone), '', '420', '500'); 
        } else {
            $taskcreatelink='';
        }
        //payment id
        if ($alter_conf['OPENPAYZ_REALID']) {
            $paymentid=zb_PaymentIDGet($login);
        } else {
            $paymentid=ip2int($userdata['IP']);
        }
        
        //passport data in profile
        if ($alter_conf['PASSPDATA_IN_PROFILE']) {
            $passportdata= web_UserPassportDataShow($login);
            $passplink=  wf_modal('<img src="skins/icon_passport.gif" border="0" title="'.__('Passport data').'" height="10">', __('Passport data'), $passportdata, '', '600', '300');
        } else {
            $passplink='';
        }
         
        $profile.='
       <table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="2">
       <tbody>
        <tr>
              <td valign="top">
       <table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="2">
        <tbody>
            <tr>
                <td class="row2" width="30%">'.__('Full address').$taskcreatelink.'</td>
                <td class="row3">'.$useraddress.'</td>
            </tr>
           <tr>
                <td class="row2" width="30%">'.__('Entrance').', '.__('Floor').'</td>
                <td class="row3">'.@$aptdata['entrance'].' '.@$aptdata['floor'].'</td>
            </tr>
            <tr>
                <td class="row2">'.$hightlight_start.''.__('Real name').''.$hightlight_end.$passplink.'</td>
                <td class="row3">'.$hightlight_start.''.$realname.''.$hightlight_end.'</td>
            </tr>
               <tr>
                <td class="row2">'.__('Contract').'</td>
                <td class="row3">'.$contract.'</td>
            </tr>
              <tr>
                <td class="row2">'.__('Phone').'</td>
                <td class="row3">'.$phone.'</td>
            </tr>
              <tr>
                <td class="row2">'.__('Mobile').'</td>
                <td class="row3">'.$mobile.'</td>
            </tr>
               <tr>
                <td class="row2">'.__('Email').'</td>
                <td class="row3">'.$mail.'</td>
            </tr>
            <tr>
                <td class="row2"> '.$hightlight_start.' '.__('Payment ID').''.$hightlight_end.'</td>
                <td class="row3"> '.$hightlight_start.' '.$paymentid.''.$hightlight_end.'</td>
            </tr>
           
            <tr>
                <td class="row2"> '.$hightlight_start.' '.__('Last activity time').''.$hightlight_end.'</td>
                <td class="row3"> '.$hightlight_start.' '.$lat.''.$hightlight_end.'</td>
            </tr>
             <tr>
                <td class="row2" >'.$hightlight_start.' '.__('Login').''.$hightlight_end.'</td>
                <td class="row3">'.$hightlight_start.' '.$userdata['login'].' '.$hightlight_end.'</td>
            </tr>
            <tr>
                <td class="row2"> '.$hightlight_start.' '.__('Password').''.$hightlight_end.'</td>
                <td class="row3"> '.$hightlight_start.' '.$userdata['Password'].''.$hightlight_end.'</td>
            </tr>
            <tr>
                <td class="row2"> '.$hightlight_start.' '.__('IP').''.$hightlight_end.'</td>
                <td class="row3"> '.$hightlight_start.' '.$userdata['IP'].''.$hightlight_end.'</td>
            </tr>
            <tr>
                <td class="row2">'.__('MAC').' '.$lookuplink.'</td>
                <td class="row3">'.$mac.'</td>
            </tr>
             <tr>
                <td class="row2">'.$hightlight_start.''.__('Tariff').''.$hightlight_end.'</td>
                <td class="row3">'.$hightlight_start.''.$userdata['Tariff'].''.$hightlight_end.'</td>
            </tr>
            <tr>
                <td class="row2">'.__('Planned tariff change').'</td>
                <td class="row3">'.$userdata['TariffChange'].'</td>
            </tr>
            <tr>
                <td class="row2">'.__('Speed override').'</td>
                <td class="row3">'.$speedoverride.'</td>
            </tr>
            <tr>
                <td class="row2"> '.$hightlight_start.' '.__('Balance').''.$hightlight_end.'</td>
                <td class="row3"> '.$hightlight_start.' '.$userdata['Cash'].''.$hightlight_end.'</td>
            </tr>
            <tr>
                <td class="row2"> '.$hightlight_start.' '.__('Credit').'</td>
                <td class="row3"> '.$hightlight_start.' '.$userdata['Credit'].''.$hightlight_end.'</td>
            </tr>
            <tr>
                <td class="row2">'.__('Credit expire').'</td>
                <td class="row3">'.$creditexpire.'</td>
            </tr>
              <tr>
                <td class="row2">'.__('Prepayed traffic').'</td>
                <td class="row3">'.$userdata['FreeMb'].'</td>
            </tr>
             <tr>
                <td class="row2">'.__('Active').'</td>
                <td class="row3">'.$act.'</td>
            </tr>
            <tr>
                <td class="row2">'.__('Always Online').'</td>
                <td class="row3">'.web_trigger($userdata['AlwaysOnline']).'</td>
            </tr>
            <tr>
                <td class="row2">'.__('Disable detailed stats').'</td>
                <td class="row3">'.web_trigger($userdata['DisabledDetailStat']).'</td>
            </tr>
            <tr>
                <td class="row2"> '.$hightlight_start.''.__('Freezed').''.$hightlight_end.'</td>
                <td class="row3"> '.$hightlight_start.''.$passiveicon.web_trigger($userdata['Passive']).''.$hightlight_end.'</td>
            </tr>
            <tr>
                <td class="row2"> '.$hightlight_start.''.__('Disabled').''.$hightlight_end.'</td>
                <td class="row3"> '.$hightlight_start.' '.$downicon.web_trigger($userdata['Down']).''.$hightlight_end.'</td>
            </tr>
              <tr>
                <td class="row2">'.__('Notes').'<a id="profileending"></a></td>
                <td class="row3">'.zb_UserGetNotes($login).'</td>
            </tr>
           
        </tbody>
        </table>
        </td>
                <td valign="top" width="10%"> 
              '.$profile_plugins.'
                </td>
        </tr>
        </tbody>
        </table>
            ';
        $profile.=cf_FieldShower($login);
        $profile.='<a href="?module=usertags&username='.$login.'">'.web_add_icon('Tags').'</a> ';
        $profile.=stg_show_user_tags($login);
        $profile.=web_ProfileControls($login);
        return($profile);
    }
    
    function zb_EventGetAllDateTimes() {
        $query="SELECT `admin`,`date` from `weblogs`";
        $result=array();
        $allevents=simple_queryall($query);
        if (!empty ($allevents)) {
            foreach ($allevents as $io=>$eachevent) {
                $result[$eachevent['date']]=$eachevent['admin'];
            }
        }
        return ($result);
    }
    
  
    
    function web_PaymentsByUser($login) {
        $allpayments=zb_CashGetUserPayments($login);
        $alter_conf=rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
        $alltypes=zb_CashGetAllCashTypes();
        $allservicenames=zb_VservicesGetAllNamesLabeled();
        $total_payments="0";
        $curdate=curdate();
        $last_payment=zb_CashGetUserLastPayment($login);
        $result='<table width="100%" border="0" class="sortable">';
          $result.='
                    <tr class="row1">
                    <td>'.__('ID').'</td>
                    <td>'.__('IDENC').'</td>
                    <td>'.__('Date').'</td>
                    <td>'.__('Payment').'</td>
                    <td>'.__('Balance before').'</td>
                    <td>'.__('Cash type').'</td>
                    <td>'.__('Payment note').'</td>
                    <td>'.__('Admin').'</td>
                    <td>'.__('Actions').'</td>
                    </tr>
                    ';
        if (!empty ($allpayments)) {
            foreach ($allpayments as $io=>$eachpayment) {
                if ($alter_conf['TRANSLATE_PAYMENTS_NOTES']) {
              $eachpayment['note']=  zb_TranslatePaymentNote($eachpayment['note'], $allservicenames);
            }
            
            //hightlight of today payments
            if ($alter_conf['HIGHLIGHT_TODAY_PAYMENTS']) {
                if (ispos($eachpayment['date'], $curdate)) {
                    $hlight="paytoday";
                } else {
                    $hlight="row3";
                }
                
            } else {
                $hlight="row3";
            }
                
                $result.='
                    <tr class="'.$hlight.'">
                    <td>'.$eachpayment['id'].'</td>
                     <td>'.zb_NumEncode($eachpayment['id']).'</td>
                    <td>'.$eachpayment['date'].'</td>
                    <td>'.$eachpayment['summ'].'</td>
                    <td>'.$eachpayment['balance'].'</td>
                    <td>'.@__($alltypes[$eachpayment['cashtypeid']]).'</td>
                    <td>'.$eachpayment['note'].'</td>
                    <td>'.$eachpayment['admin'].'</td>
                    <td><a href="#"  onClick="window.open(\'?module=printcheck&paymentid='.$eachpayment['id'].'\',\'checkwindow\',\'width=800,height=600\')"><img src="skins/printer_small.gif" border="0"></a></td>
                    </tr>
                    ';
                $total_payments=$total_payments+$eachpayment['summ'];
            }
        }
        $result.='</table>';
        $result.=__('Total payments').': <b>'.abs($total_payments).'</b> <br>';
        $result.=$last_payment.'<br>';
        return($result);
    }
    
        function web_GrepLogByUser($login) {
      $query='SELECT * from `weblogs` WHERE `event` LIKE "%'.$login.'%" ORDER BY `date` DESC';
      $allevents=  simple_queryall($query);
          $result='<table width="100%" class="sortable" border="0">';
              $result.='
                <tr class="row1">
                <td>'.__('Who?').'</td>
                <td>'.__('When?').'</td>
                <td>'.__('What happen?').'</td>
                </tr>';	
      if (!empty ($allevents)) {
            foreach ($allevents as $io=>$eachevent) {
              $result.='
                <tr class="row3">
                <td>'.$eachevent['admin'].'</td>
                <td>'.$eachevent['date'].'</td>
                <td>'.$eachevent['event'].'</td>
	</tr>
	';	
          }
      }
      $result.='<table>';
      return($result);
    }

    
    function web_EditorTableDataFormOneField($fieldname,$fieldkey,$formurl,$olddata) {
    $form='<table width="100%" class="sortable" border="0">';
    $form.='<tr class="row1">
                    <td>'.__('ID').'</td>
                    <td>'.__($fieldname).'</td>
                    <td>'.__('Actions').'</td>
                    </tr>';
    if (!empty ($olddata)) {
        foreach ($olddata as $io=>$value) {
            $form.='<tr class="row3">
                    <td>'.$value['id'].'</td>
                    <td>'.$value[$fieldkey].'</td>
                    <td>
                    '.wf_JSAlert($formurl.'&action=delete&id='.$value['id'], web_delete_icon(), 'Removing this may lead to irreparable results').'
                    <a href="'.$formurl.'&action=edit&id='.$value['id'].'">'.web_edit_icon().'</a>
                    </td>
                    </tr>';
        }
        
    }
    $form.='</table>';
    $form.='
        <form action="" method="POST">
        '.__($fieldname).' <input type="text" name="new'.$fieldkey.'">
        <input type="submit" value="'.__('Create').'">
        </form>
        ';
    return($form);
}

function web_year_selector() {
    $curyear=curyear();
    $count=5;
    $selector='<select name="yearsel">';
    for ($i=0;$i<$count;$i++) {
        $selector.='<option value="'.($curyear-$i).'">'.($curyear-$i).'</option>';
    }
    $selector.='</select>';
    return($selector);
}

function web_DirectionsShow() {
      $allrules=zb_DirectionsGetAll();
      $result='<table width="100%" class="sortable" border="0">';
         $result.='
                  <tr class="row1">
                   <td>
                    '.__('Rule number').'
                    </td>
                    <td>
                    '.__('Rule name').'
                    </td>
                     <td>
                    '.__('Actions').'
                    </td>
                  </tr>
                  ';
      if (!empty ($allrules)) {
          foreach ($allrules as $io=>$eachrule) {
              $result.='
                  <tr class="row3">
                    <td>
                    '.$eachrule['rulenumber'].'
                    </td>
                    <td>
                    '.$eachrule['rulename'].'
                    </td>
                     <td>
                    '.  wf_JSAlert('?module=rules&delete='.$eachrule['id'], web_delete_icon(), 'Removing this may lead to irreparable results').'
                    '.  wf_JSAlert("?module=rules&edit=".$eachrule['id'], web_edit_icon(), 'Are you serious').'    
                    </td>
                  </tr>
                  ';
          }
      }
      $result.='</table>';
      show_window(__('Traffic classes'),$result);
  }
  
  function web_DirectionAddForm() {
      $allrules=zb_DirectionsGetAll();
      $availrules=array();
      if (!empty ($allrules)) {
          foreach ($allrules as $io=>$eachrule) {
              $availrules[$eachrule['rulenumber']]=$eachrule['rulename'];
          }
      }
      $selector='<select name="newrulenumber">';
       for ($i=0;$i<=9;$i++) {
           if (!isset ($availrules[$i])) {
               $selector.='<option value="'.$i.'">'.$i.'</option>';
           }
       }
       $selector.='</select>';
       
      $form='
          <form action="" method="POST" class="glamour">
            '.$selector.' '.__('Direction number').'<br>
            <input type="text" name="newrulename"> '.__('Direction name').' <br>
           <input type="submit" value="'.__('Create').'">
          </form>
          ';
      show_window(__('Add new traffic class'), $form);
  }
  
   function web_DirectionsEditForm($ruleid) {
      $ruleid=vf($ruleid,3);
      $query="SELECT * from `directions` WHERE `id`='".$ruleid."'";
      $ruledata=simple_query($query);
           
      $editinputs=wf_TextInput('editrulename', 'Rule name', $ruledata['rulename'], true, '20');
      $editinputs.=wf_Submit('Save');
      $editform=wf_Form("", 'POST', $editinputs, 'glamour');
      $editform.=wf_Link('?module=rules', 'Back', true, 'ubButton');
      show_window(__('Edit').' '.__('Rule name'),$editform);
  }
  
  
 
  function web_PaymentsShow($query) {
    $alter_conf=rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
    $alladrs=zb_AddressGetFulladdresslist();
    $allrealnames=zb_UserGetAllRealnames();
    $alltypes=zb_CashGetAllCashTypes();
    $allapayments=simple_queryall($query);
    $allservicenames=zb_VservicesGetAllNamesLabeled();
    //getting full contract list
    if ($alter_conf['FINREP_CONTRACT']) {
       $allcontracts=  zb_UserGetAllContracts(); 
       $allcontracts=  array_flip($allcontracts);
    }
    $total=0;
    $totalPaycount=0;
    
      $cells=  wf_TableCell(__('ID'));
      $cells.= wf_TableCell(__('IDENC'));
      $cells.= wf_TableCell(__('Date'));
      $cells.= wf_TableCell(__('Cash'));
      //optional contract display
      if ($alter_conf['FINREP_CONTRACT']) {
        $cells.= wf_TableCell(__('Contract'));  
      }
      $cells.= wf_TableCell(__('Login'));
      $cells.= wf_TableCell(__('Full address'));
      $cells.= wf_TableCell(__('Real Name'));
      $cells.= wf_TableCell(__('Cash type'));
      $cells.= wf_TableCell(__('Notes'));
      $cells.= wf_TableCell(__('Admin'));
      $rows=  wf_TableRow($cells, 'row1');
      
    if (!empty ($allapayments)) {
        foreach ($allapayments as $io=>$eachpayment) {
           
            if ($alter_conf['TRANSLATE_PAYMENTS_NOTES']) {
            $eachpayment['note']=  zb_TranslatePaymentNote($eachpayment['note'], $allservicenames);
            }
            
              $cells=  wf_TableCell($eachpayment['id']);
              $cells.= wf_TableCell(zb_NumEncode($eachpayment['id']));
              $cells.= wf_TableCell($eachpayment['date']);
              $cells.= wf_TableCell($eachpayment['summ']);
              //optional contract display
              if ($alter_conf['FINREP_CONTRACT']) {
                $cells.= wf_TableCell(@$allcontracts[$eachpayment['login']]);  
              }
              $cells.= wf_TableCell(wf_Link('?module=userprofile&username='.$eachpayment['login'], (web_profile_icon().' '.$eachpayment['login']), false, ''));
              $cells.= wf_TableCell(@$alladrs[$eachpayment['login']]);
              $cells.= wf_TableCell(@$allrealnames[$eachpayment['login']]);
              $cells.= wf_TableCell(@__($alltypes[$eachpayment['cashtypeid']]));
              $cells.= wf_TableCell($eachpayment['note']);
              $cells.= wf_TableCell($eachpayment['admin']);
              $rows.=  wf_TableRow($cells, 'row3');
            
            if ($eachpayment['summ']>0) {
            $total=$total+$eachpayment['summ'];
            $totalPaycount++;
            }
        }
    }
   
    $result=  wf_TableBody($rows, '100%', '0', 'sortable');
    $result.=wf_tag('strong').__('Cash').': '.$total.  wf_tag('strong', true).wf_tag('br');
    $result.=wf_tag('strong').__('Count').': '.$totalPaycount.  wf_tag('strong', true);
    return($result);
}

function web_bar($count,$total) {
    $barurl='skins/bar.png';
    if ($total!=0) {
    $width=($count/$total)*100;
    } else {
     $width=0;
    }
    $code='<img src="'.$barurl.'"  height="14" width="'.$width.'%" border="0">';
    return($code);
}

//retunt all months with names in two digit notation
function months_array() {
    $months=array(
        '01'=>'January',
        '02'=>'February',
        '03'=>'March',
        '04'=>'April',
        '05'=>'May',
        '06'=>'June',
        '07'=>'July',
        '08'=>'August',
        '09'=>'September',
        '10'=>'October',
        '11'=>'November',
        '12'=>'December');
    return($months);
}

//retunt all months with names without begin zeros
function months_array_wz() {
    $months=array(
        '1'=>'January',
        '2'=>'February',
        '3'=>'March',
        '4'=>'April',
        '5'=>'May',
        '6'=>'June',
        '7'=>'July',
        '8'=>'August',
        '9'=>'September',
        '10'=>'October',
        '11'=>'November',
        '12'=>'December');
    return($months);
}

function web_PaymentsShowGraph($year) {
    $months=months_array();
    $result='<table width="100%" class="sortable" border="0">';
    $year_summ=zb_PaymentsGetYearSumm($year);
    $result.='
            <tr class="row1">
                <td></td>
                <td>'.__('Month').'</td>
                <td>'.__('Payments count').'</td>
                <td>'.__('ARPU').'</td>
                <td>'.__('Cash').'</td>
                <td width="50%">'.__('Visual').'</td>
            </tr>
            ';
    foreach ($months as $eachmonth=>$monthname) {
        $month_summ=zb_PaymentsGetMonthSumm($year, $eachmonth);
        $paycount=zb_PaymentsGetMonthCount($year, $eachmonth);
        $result.='
            <tr class="row3">
                <td>'.$eachmonth.'</td>
                <td><a href="?module=report_finance&month='.$year.'-'.$eachmonth.'">'.rcms_date_localise($monthname).'</a></td>
                <td>'.$paycount.'</td>
                <td>'.@round($month_summ/$paycount,2).'</td> 
                <td>'.$month_summ.'</td>
                <td>'.web_bar($month_summ, $year_summ).'</td>
            </tr>
            ';
    }
    $result.='</table>';
    show_window(__('Payments by').' '.$year, $result);
}

   function web_Overlay($title,$text,$opacity='0.65') {
        $text=str_replace("\n", '', $text);
        $text=str_replace("\r", '', $text);
        $overlayname='overlay'.rand(0,9999);
        $overlaystyle='
            <style type="text/css">
            .overLayer
            {
                background:black;
                display:block;
                left:0;
                opacity:'.$opacity.';
                filter: alpha(opacity = 65);
                position:fixed;
                top:0;
                width: 100%;
                height: 100%;
                z-index: 1000;
                color: white;
                padding: 50px;
                }
             </style>
            ';
        $overlaycode='
            <p><a href="" class="'.$overlayname.'">'.$title.'</a></p>
            <script type="text/javascript" src="modules/jsc/jquery.min.js"></script>
            <script type="text/javascript">
            $(function()
                {
                 $(\'.'.$overlayname.'\').click(function()
                  {
                var ol = $(\'<div class="overLayer">'.$text.'</div>\');
                ol.click(function()
                {
                 $(this).remove();
                });
                $(\'body\').append(ol);
                return false;
                });
               })
            </script>
        <div style=\'clear: both;\'></div>
         ';
   $overlay=$overlaystyle.$overlaycode;
   return($overlay);
    }
  
    function web_GridEditor($titles,$keys,$alldata,$module,$delete=true,$edit=false,$prefix='') {
        $result='<table width="100%" class="sortable" border="0">';
        $result.='<tr class="row1">';
        foreach ($titles as $eachtitle) {
            $result.='<td>'.__($eachtitle).'</td>';
        }
        $result.='<td>'.__('Actions').'</td>';
        $result.='</tr>';
        if (!empty ($alldata)) {
            foreach ($alldata as $io=>$eachdata) {
                $result.='<tr class="row3">';
                foreach ($keys as $eachkey) {
                if (array_key_exists($eachkey, $eachdata)) {
                    $result.='<td>'.$eachdata[$eachkey].'</td>';
                    }    
                }
            if ($delete) {
                //$deletecontrol='<a href="?module='.$module.'&'.$prefix.'delete='.$eachdata['id'].'">'.web_delete_icon().'</a>';
                $deletecontrol=wf_JSAlert('?module='.$module.'&'.$prefix.'delete='.$eachdata['id'], web_delete_icon(), 'Are you serious');
            } else {
                $deletecontrol='';
            }
            
            if ($edit) {
                $editcontrol='<a href="?module='.$module.'&'.$prefix.'edit='.$eachdata['id'].'">'.web_edit_icon().'</a>';
            } else {
                $editcontrol='';
            }
            $result.='<td>'.$deletecontrol.' '.$editcontrol.' </td>';
            $result.='</tr>';
            }
        }
        
        $result.='</table>';
        return($result);
    }
    
   function web_GridEditorNas($titles,$keys,$alldata,$module,$delete=true,$edit=true,$prefix='') {
          $allnetworkdata=multinet_get_all_networks();
          $netcidrs=array();
          if (!empty ($allnetworkdata)) {
            foreach ($allnetworkdata as $io=>$eachnet) {
                $netcidrs[$eachnet['id']]=$eachnet['desc'];
            }
           }
          
        $result='<table width="100%" class="sortable" border="0">';
        $result.='<tr class="row1">';
        foreach ($titles as $eachtitle) {
            $result.='<td>'.__($eachtitle).'</td>';
        }
        $result.='<td>'.__('Actions').'</td>';
        $result.='</tr>';
        if (!empty ($alldata)) {
            foreach ($alldata as $io=>$eachdata) {
                $result.='<tr class="row3">';
                foreach ($keys as $eachkey) {
                if (array_key_exists($eachkey, $eachdata)) {
                    if ($eachkey=='netid') {
                        $result.='<td>'.$eachdata[$eachkey].': '.$netcidrs[$eachdata[$eachkey]].'</td>';
                    } else {
                        $result.='<td>'.$eachdata[$eachkey].'</td>';
                    }
                    
                    }    
                }
            if ($delete) {
                //$deletecontrol='<a href="?module='.$module.'&'.$prefix.'delete='.$eachdata['id'].'">'.web_delete_icon().'</a>';
                $deletecontrol=wf_JSAlert('?module='.$module.'&'.$prefix.'delete='.$eachdata['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            } else {
                $deletecontrol='';
            }
            
            if ($edit) {
                $editcontrol='<a href="?module='.$module.'&'.$prefix.'edit='.$eachdata['id'].'">'.web_edit_icon().'</a>';
            } else {
                $editcontrol='';
            }
            $result.='<td>'.$deletecontrol.' '.$editcontrol.' </td>';
            $result.='</tr>';
            }
        }
        
        $result.='</table>';
        return($result);
    }
    
    function web_GridEditorVservices($titles,$keys,$alldata,$module,$delete=true,$edit=false) {
        $alltagnames=stg_get_alltagnames();
        $result='<table width="100%" class="sortable" border="0">';
        $result.='<tr class="row1">';
        foreach ($titles as $eachtitle) {
            $result.='<td>'.__($eachtitle).'</td>';
        }
        $result.='<td>'.__('Actions').'</td>';
        $result.='</tr>';
        if (!empty ($alldata)) {
            foreach ($alldata as $io=>$eachdata) {
                $result.='<tr class="row3">';
                foreach ($keys as $eachkey) {
                if (array_key_exists($eachkey, $eachdata)) {
                    if ($eachkey=='tagid') {
                    @$tagname=$alltagnames[$eachdata['tagid']];
                    $result.='<td>'.$tagname.'</td>';
                    } else {
                    $result.='<td>'.$eachdata[$eachkey].'</td>';
                    }
                    }    
                }
            if ($delete) {
                $deletecontrol=wf_JSAlert('?module='.$module.'&delete='.$eachdata['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            } else {
                $deletecontrol='';
            }
            
            if ($edit) {
                $editcontrol='<a href="?module='.$module.'&edit='.$eachdata['id'].'">'.web_edit_icon().'</a>';
            } else {
                $editcontrol='';
            }
            $result.='<td>'.$deletecontrol.' '.$editcontrol.' </td>';
            $result.='</tr>';
            }
        }
        
        $result.='</table>';
        return($result);
    }
    
       function web_NasAddForm() {
            $form='
                <form action="" method="POST">
                <br>    '.  multinet_network_selector().' '.__('Network').'
                <br>    <select name="newnastype"> 
                            <option value="rscriptd">rscriptd</option>
                            <option value="radius">radius</option>
                            <option value="mtdirect">Mikrotik Direct</option>
                            <option value="local">Local NAS</option>
                        </select>'.__('NAS type').'
                <br>    <input type="text" name="newnasip"> '.__('IP').'
                <br>    <input type="text" name="newnasname"> '.__('NAS name').'
                <br>    <input type="text" name="newbandw"> '.__('Bandwidthd URL').'
                <br>    <input type="submit" value="'.__('Create').'">
                </form>
                
                ';
            return($form);
        }
       
 // simple backup routine
function zb_backup_tables($tables = '*',$silent=false) {
    $alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    $exclude_tables=$alter_conf['NOBACKUPTABLESLIKE'];
    $exclude_tables=explode(',',$exclude_tables);
    
	if($tables == '*')
	{
		$tables = array();
		$result = mysql_query('SHOW TABLES');
		while($row = mysql_fetch_row($result))	{
			$tables[] = $row[0];
		}
             
                
                
	}
	else
	{
		$tables = is_array($tables) ? $tables : explode(',',$tables);
	}
        
        $return='';
        
           //exclude some tables
                if (!empty ($exclude_tables)) {
                    foreach ($exclude_tables as $oo=>$eachexclude) {
                        foreach ($tables as $io=>$eachtable) {
                            if (ispos($eachtable, $eachexclude)) {
                            unset ($tables[$io]);
                            }
                        }
                    }
                }

	//cycle through
	foreach($tables as $table)
	{
		$result = mysql_query('SELECT * FROM '.$table);
		$num_fields = mysql_num_fields($result);
		//$return.= 'DROP TABLE '.$table.';';
		$row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));
		$return.= "\n\n".$row2[1].";\n\n";

		for ($i = 0; $i < $num_fields; $i++)
		{
			while($row = mysql_fetch_row($result))
			{
				$return.= 'INSERT INTO '.$table.' VALUES(';
				for($j=0; $j<$num_fields; $j++)
				{
					$row[$j] = addslashes($row[$j]);
					@$row[$j] = ereg_replace("\n","\\n",$row[$j]);
					if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
					if ($j<($num_fields-1)) { $return.= ','; }
				}
				$return.= ");\n";
			}
		}
		$return.="\n\n\n";
	}

	//save file
        $backname=DATA_PATH.'backups/sql/billing-db-backup-'.time().'.sql';
	$handle = fopen($backname,'w+');
	fwrite($handle,$return);
	fclose($handle);
        
        if (!$silent) {
            show_window(__('Backup saved'),$backname);
        }
        
        log_register("CREATE Backup ".$backname);
        return ($backname);
}


function web_BackupForm() {
    $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    $excludes=$alterconf['NOBACKUPTABLESLIKE'];
    $backupinputs=__('This will create a backup copy of all tables in the database, except those whose names are found').': '.$excludes.'<br>';
    $backupinputs.=wf_HiddenInput('createbackup', 'true');
    $backupinputs.=wf_CheckInput('imready', 'I`m ready', true, false);
    $backupinputs.=wf_Submit('Create');
    $form=wf_Form('', 'POST', $backupinputs, 'glamour');
    
    return($form);
}

   function web_AddressAptForm($login) {
         $login=vf($login);
         $aptdata=zb_AddressGetAptData($login);
         $useraddress=zb_AddressGetFulladdresslist();
         @$useraddress=$useraddress[$login];
         $form='
             <form action="" method="POST">
                <table width="100%" border="0">
                 <tr class="row1">
                    <td>'.__('Value').'</td>
                    <td>'.__('Current state').'</td>
                    <td>'.__('Actions').'</td>
                </tr>    
                 <tr class="row3">
                    <td>'.__('Login').'</td>
                    <td>'.$login.'</td>
                    <td></td>
                </tr>  
                <tr class="row3">
                    <td>'.__('Full address').'</td>
                    <td>'.@$useraddress.'</td>
                    <td>
                    '.  wf_JSAlert('?module=binder&username='.$login.'&orphan=true', web_delete_icon(), __('Are you sure you want to make the homeless this user')."?").'
                    </td>
                </tr>    
                <tr class="row3">
                    <td>'.__('Entrance').'</td>
                    <td>'.@$aptdata['entrance'].'</td>
                    <td><input type="text" value="'.@$aptdata['entrance'].'" name="changeentrance"></td>
                </tr>    
                <tr class="row3">
                    <td>'.__('Floor').'</td>
                    <td>'.@$aptdata['floor'].'</td>
                    <td><input type="text" value="'.@$aptdata['floor'].'" name="changefloor"></td>
                </tr>    
                <tr class="row3">
                    <td>'.__('Apartment').'<sup>*</sup></td>
                    <td>'.@$aptdata['apt'].'</td>
                    <td><input type="text" value="'.@$aptdata['apt'].'" name="changeapt"></td>
                </tr>    
                </table>
                <input type="submit" value="'.__('Save').'">
             </form>
             ';
         
        return($form);
     }
     
     
     function web_AddressOccupancyForm() {
         $form='<form action="" method="POST">';
               if (!isset ($_POST['citysel'])) { 
                   $form.=__('City').' '.web_CitySelectorAc();
               } else {
                   $cityname=zb_AddressGetCityData($_POST['citysel']);
                   $cityname=$cityname['cityname'];
                   $form.=web_ok_icon().' <input type="hidden" name="citysel" value="'.$_POST['citysel'].'"> '.$cityname.'<br>';
                   
                   if (!isset ($_POST['streetsel'])) {
                       $form.=__('Street').' '.web_StreetSelectorAc($_POST['citysel']);
                   } else {
                       $streetname=zb_AddressGetStreetData($_POST['streetsel']);
                       $streetname=$streetname['streetname'];
                       $form.=web_ok_icon().'<input type="hidden" name="streetsel" value="'.$_POST['streetsel'].'"> '.$streetname.'<br>';
                           if (!isset ($_POST['buildsel'])) {
                           $form.=__('Build').' '.  web_BuildSelectorAc($_POST['streetsel']);
                       } else {
                           $buildnum=zb_AddressGetBuildData($_POST['buildsel']);
                           $buildnum=$buildnum['buildnum'];
                           $form.=web_ok_icon().'<input type="hidden" name="buildsel" value="'.$_POST['buildsel'].'"> '.$buildnum.'<br>';
                           $form.=web_AddressBuildShowAptsCheck($_POST['buildsel']).web_AptCreateForm();
                           $form.='<input type="submit" value="'.__('Create').'">';
                       }
                   }
               }
         $form.='</form>';
         
         return($form);
     }
     
     function web_UserTraffStats($login) {
       $login=vf($login);
       $alldirs=zb_DirectionsGetAll();
       /*
        * Current month traffic stats
        */
      
       $cells= wf_TableCell(__('Traffic classes'));
       $cells.=wf_TableCell(__('Downloaded'));
       $cells.=wf_TableCell(__('Uploaded'));
       $cells.=wf_TableCell(__('Total'));
       $rows=  wf_TableRow($cells, 'row1');
       
            if (!empty ($alldirs)) {
                foreach ($alldirs as $io=>$eachdir) {
                   $query_downup="SELECT `D".$eachdir['rulenumber']."`,`U".$eachdir['rulenumber']."` from `users` WHERE `login`='".$login."'";
                   $downup=simple_query($query_downup);
                   $cells= wf_TableCell($eachdir['rulename']);
                   $cells.=wf_TableCell(stg_convert_size($downup['D'.$eachdir['rulenumber']]), '', '',  'sorttable_customkey="'.$downup['D'.$eachdir['rulenumber']].'"');
                   $cells.=wf_TableCell(stg_convert_size($downup['U'.$eachdir['rulenumber']]), '', '',  'sorttable_customkey="'.$downup['U'.$eachdir['rulenumber']].'"');
                   $cells.=wf_TableCell(stg_convert_size(($downup['U'.$eachdir['rulenumber']]+$downup['D'.$eachdir['rulenumber']])), '', '',  'sorttable_customkey="'.($downup['U'.$eachdir['rulenumber']]+$downup['D'.$eachdir['rulenumber']]).'"');
                   $rows.=  wf_TableRow($cells, 'row3');
                }
            }

       $result=  wf_tag('h3').__('Current month traffic stats').wf_tag('h3',true);
       $result.=  wf_TableBody($rows, '100%', '0', 'sortable');
       /*
        * Some per-user graphs
        */
       $ip=zb_UserGetIP($login);
       $bandwidthd=zb_BandwidthdGetUrl($ip);
       if ($bandwidthd) {
           $bwd=zb_BandwidthdGenLinks($ip);
           
          //day graph
          $daybw=wf_img($bwd['dayr'], __('Downloaded'));
          if (!empty($bwd['days'])) {
             $daybw.=wf_delimiter().wf_img($bwd['days'], __('Uploaded'));
          }
          
          //week graph
          $weekbw=wf_img($bwd['weekr'], __('Downloaded'));
          if (!empty($bwd['weeks'])) {
             $weekbw.=wf_delimiter().wf_img($bwd['weeks'], __('Uploaded'));
          }
          
          //month graph
          $monthbw=wf_img($bwd['monthr'], __('Downloaded'));
          if (!empty($bwd['months'])) {
             $monthbw.=wf_delimiter().wf_img($bwd['months'], __('Uploaded'));
          }
          
          //year graph
          $yearbw=wf_img($bwd['yearr'], __('Downloaded'));
          if (!empty($bwd['years'])) {
             $yearbw.=wf_delimiter().wf_img($bwd['years'], __('Uploaded'));
          }
          
          //modal window sizes
          if (!empty($bwd['days'])) {
              $modal_w= 920;
	      $modal_h = 600;
          } else {
              $modal_w= 530;
	      $modal_h = 230;
          }
          
          $result.=wf_delimiter();
          $result.=wf_tag('h3').__('Graphs').  wf_tag('h3',true);
                
          $bwcells= wf_TableCell(wf_modal(__('Graph by day'), __('Graph by day'), $daybw, 'ubButton', $modal_w, $modal_h));
          $bwcells.=wf_TableCell(wf_modal(__('Graph by week'), __('Graph by week'), $weekbw, 'ubButton', $modal_w, $modal_h));
          $bwcells.=wf_TableCell(wf_modal(__('Graph by month'), __('Graph by month'), $monthbw, 'ubButton', $modal_w, $modal_h));
          $bwcells.=wf_TableCell(wf_modal(__('Graph by year'), __('Graph by year'), $yearbw, 'ubButton', $modal_w, $modal_h));
          $bwrows=  wf_TableRow($bwcells);
          //adding graphs to result
          $result.=wf_TableBody($bwrows, '', '0', '');
          $result.=wf_delimiter();


               
       } else {
           $result.=__('No user graphs because no NAS with bandwidthd for his network');
       }
       
       
       /*
        * traffic stats by previous months
        */
     
     $result.=wf_tag('h3').__('Previous month traffic stats').wf_tag('h3', true);
     
     $cells=  wf_TableCell(__('Year'));
     $cells.= wf_TableCell(__('Month'));
     $cells.= wf_TableCell(__('Traffic classes'));
     $cells.= wf_TableCell(__('Downloaded'));
     $cells.= wf_TableCell(__('Uploaded'));
     $cells.= wf_TableCell(__('Total'));
     $cells.= wf_TableCell(__('Cash'));
     $rows=  wf_TableRow($cells, 'row1');

       if (!empty ($alldirs)) {
           foreach ($alldirs as $io=>$eachdir) {
               $query_prev="SELECT `D".$eachdir['rulenumber']."`,`U".$eachdir['rulenumber']."`,`month`,`year`,`cash` from `stat` WHERE `login`='".$login."' ORDER BY YEAR";
               $allprevmonth=simple_queryall($query_prev);
                if (!empty ($allprevmonth)) {
                   foreach ($allprevmonth as $io2=>$eachprevmonth) {
                     $cells=  wf_TableCell($eachprevmonth['year']);
                     $cells.= wf_TableCell($eachprevmonth['month']);
                     $cells.= wf_TableCell($eachdir['rulename']);
                     $cells.= wf_TableCell(stg_convert_size($eachprevmonth['D'.$eachdir['rulenumber']]), '', '', 'sorttable_customkey="'.$eachprevmonth['D'.$eachdir['rulenumber']].'"');
                     $cells.= wf_TableCell(stg_convert_size($eachprevmonth['U'.$eachdir['rulenumber']]), '', '', 'sorttable_customkey="'.$eachprevmonth['U'.$eachdir['rulenumber']].'"');
                     $cells.= wf_TableCell(stg_convert_size(($eachprevmonth['U'.$eachdir['rulenumber']]+$eachprevmonth['D'.$eachdir['rulenumber']])), '', '', 'sorttable_customkey="'.($eachprevmonth['U'.$eachdir['rulenumber']]+$eachprevmonth['D'.$eachdir['rulenumber']]).'"');
                     $cells.= wf_TableCell(round($eachprevmonth['cash'],2));
                     $rows.=  wf_TableRow($cells, 'row3');

                   }
               }
           }
       }

       $result.=wf_TableBody($rows, '100%', '0', 'sortable');
       
       return($result);
   }
    
   
    function zb_TariffGetCount() {
        $alltariffs=zb_TariffsGetAll();
        $result=array();
        if (!empty ($alltariffs)) {
            foreach ($alltariffs as $eachtariff) {
                $tariffname=$eachtariff['name'];
                $query="SELECT COUNT(`login`) from `users` WHERE `tariff`='".$tariffname."'";
                $tariffusercount=simple_query($query);
                $tariffusercount=$tariffusercount['COUNT(`login`)'];
                $result[$tariffname]=$tariffusercount;
            }
        } else {
            show_error(__('No tariffs found'));
        }
        return($result);
    }
   

    function web_TariffShowReport() {
        $tariffcount=zb_TariffGetCount();
        $totalusers=0;
        $result='<table width="100%" class="sortable" border="0">';
        $result.='
                    <tr class="row1">
                    <td width="20%">'.__('Tariff').'</td>
                    <td width="20%">'.__('Total').'</td>
                    <td>'.__('Visual').'</td>
                    </tr>
                    ';
        if (!empty ($tariffcount)) {
            $maxusers=max($tariffcount);
            foreach ($tariffcount as $eachtariffname=>$eachtariffcount) {
                $totalusers=$totalusers+$eachtariffcount;
                $result.='
                    <tr class="row3">
                    <td>'.$eachtariffname.'</td>
                    <td>'.$eachtariffcount.'</td>
                    <td>'.  web_bar($eachtariffcount, $maxusers).'</td>
                    </tr>
                    ';
            }
         }
        $result.='</table>';
        $result.='<h2>'.__('Total').': '.$totalusers.'</h2>';
        return($result);
    }
    
    function web_TariffShowMoveReport() {
       $alter_conf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
       $billing_conf=rcms_parse_ini_file(CONFIG_PATH."billing.ini");
       $nmchange='#!/bin/sh'."\n";
       //is nmchange enabled?
       if ($alter_conf['NMCHANGE']) {
           $sgconf=$billing_conf['SGCONF'];
           $stg_host=$billing_conf['STG_HOST'];
           $stg_port=$billing_conf['STG_PORT'];
           $stg_login=$billing_conf['STG_LOGIN'];
           $stg_passwd=$billing_conf['STG_PASSWD'];
       }
       
       $query="SELECT `login`,`Tariff`,`TariffChange` from `users` WHERE `TariffChange` !=''";
       $allmoves=simple_queryall($query);
       $alladdrz=zb_AddressGetFulladdresslist();
       $allrealnames=  zb_UserGetAllRealnames();
       $alltariffprices=  zb_TariffGetPricesAll();
       $totaldiff=0;
       $movecount=0;
       
      
       $tablecells=  wf_TableCell(__('Login'));
       $tablecells.=  wf_TableCell(__('Full address'));
       $tablecells.=  wf_TableCell(__('Real name'));
       $tablecells.=  wf_TableCell(__('Tariff'));
       $tablecells.=  wf_TableCell(__('Next month'));
       $tablecells.=  wf_TableCell(__('Difference'));
       $tablerows=  wf_TableRow($tablecells, 'row1');
       
       if (!empty ($allmoves)) {
           foreach ($allmoves as $io=>$eachmove) {
               //generate NMCHANGE option
               if ($alter_conf['NMCHANGE']) {
                $nmchange.=$sgconf.' set -s '.$stg_host.' -p '.$stg_port.' -a'.$stg_login.' -w'.$stg_passwd.' -u'.$eachmove['login'].' --always-online 0'."\n";   
                $nmchange.=$sgconf.' set -s '.$stg_host.' -p '.$stg_port.' -a'.$stg_login.' -w'.$stg_passwd.' -u'.$eachmove['login'].' --always-online 1'."\n";
               }
               
               @$current_price=$alltariffprices[$eachmove['Tariff']];
               @$next_price=$alltariffprices[$eachmove['TariffChange']];
               @$difference=$next_price-$current_price;
               //coloring movements
               if ($difference<0) {
                   $cashcolor='#a90000';
               } else {
                   $cashcolor='#005304';
               }
               $totaldiff=$totaldiff+$difference;
               $movecount++;
               
               $tablecells=  wf_TableCell(wf_Link('?module=userprofile&username='.$eachmove['login'], web_profile_icon().' '.$eachmove['login'], false));
               $tablecells.=  wf_TableCell(@$alladdrz[$eachmove['login']]);
               $tablecells.=  wf_TableCell(@$allrealnames[$eachmove['login']]);
               $tablecells.=  wf_TableCell($eachmove['Tariff']);
               $tablecells.=  wf_TableCell($eachmove['TariffChange']);
               $tablecells.=  wf_TableCell('<font color="'.$cashcolor.'">'.$difference.'</font>');
               $tablerows.=  wf_TableRow($tablecells, 'row3');
         
           }
       }
      
       $result= wf_TableBody($tablerows, '100%', 0, 'sortable');
       
       
         //coloring profit
               if ($totaldiff<0) {
                   $profitcolor='#a90000';
               } else {
                   $profitcolor='#005304';
               }
       $result.='<b>'.__('Total').': '.$movecount.'</b><br>';
       $result.='<font color="'.$profitcolor.'">'.__('PROFIT').': '.$totaldiff.'</font>';
               
       //yep, lets write nmchange
         if ($alter_conf['NMCHANGE']) {
             if (date("d")!=1) {
                 // protect of override on 1st day
                 file_put_contents(CONFIG_PATH.'nmchange.sh', $nmchange);
             }
             
             }
       
       return($result);
   }
    
   function translit_string($var) {
    $NpjLettersFrom = "абвгдезиклмнопрстуфцыіїє ";
    $NpjLettersTo   = "abvgdeziklmnoprstufcyiie_";
    $NpjBiLetters = array(
        "й" => "jj", "ё" => "jo", "ж" => "zh", "х" => "kh", "ч" => "ch",
        "ш" => "sh", "щ" => "shh", "э" => "je", "ю" => "ju", "я" => "ja",
        "ъ" => "", "ь" => "");

    $NpjCaps  = "АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЫЭЮЯІЇЄ ";
    $NpjSmall = "абвгдеёжзийклмнопрстуфхцчшщьъыэюяіїє ";

    $var = trim(strip_tags($var));
    $var = preg_replace( "/s+/ms", "_", $var );
    $var = strtr( $var, $NpjCaps, $NpjSmall );
    $var = strtr( $var, $NpjLettersFrom, $NpjLettersTo );
    $var = strtr( $var, $NpjBiLetters );
    $var = preg_replace("/[^a-z0-9_]+/mi", "", $var);
    $var = strtolower ( $var );
    return ($var);
}

//check for substring in string
function ispos($string,$search) {
    if (strpos($string,$search)===false) {
        return(false);
    } else {
        return(true);
    }
}

//encode numbers as letters as backarray
function zb_NumEncode($data) {
       $numbers=array('0','1','2','3','4','5','6','7','8','9');
       $letters=array('A','B','C','D','E','F','G','H','I','J');
       $letters=array_reverse($letters);
       $result=str_replace($numbers, $letters, $data);
       return($result);
   }
   
//reverse function to 
function zb_NumUnEncode($data) {
       $numbers=array('0','1','2','3','4','5','6','7','8','9');
       $letters=array('A','B','C','D','E','F','G','H','I','J');
       $letters=array_reverse($letters);
       $result=str_replace( $letters, $numbers, $data);
       return($result);
   }

 function zb_UserSearchAddressPartial($query) {
        $query=mysql_real_escape_string($query);
        $query=strtolower_utf8($query);
        $alluseraddress=zb_AddressGetFulladdresslist();
        $result=array();
        if (!empty ($alluseraddress)) {
        foreach ($alluseraddress as $login=>$address) {
            if (ispos(strtolower_utf8($address), $query)) {
                $result[]=$login;
            }
        }
        }
        return ($result);
    }
    
   function web_UserSearchShowResults($usersarr) {
        if (!empty ($usersarr)) {
            $alladdress=zb_AddressGetFulladdresslist();
            $allrealnames=zb_UserGetAllRealnames();
            $result='<table width="100%" boerder="0" class="sortable">
                    <tr class="row1">
                    <td>
                   '.__('Login').'
                    </td>
                    <td>
                   '.__('Address').'
                    </td>
                    <td>
                    '.__('Real Name').'
                    </td>
                    </tr>
                    ';
            foreach ($usersarr as $eachlogin) {
                 $result.='
                    <tr class="row3">
                    <td>
                     <a href="?module=userprofile&username='.$eachlogin.'">
                    '.  web_profile_icon().'
                    '.$eachlogin.'
                    </a>
                    </td>
                    <td>
                   '.@$alladdress[$eachlogin].'
                    </td>
                    <td>
                    '.@$allrealnames[$eachlogin].'
                    </td>
                    </tr>
                    ';
            }
            $result.="</table>";
            
            show_window(__('Search results'), $result);
            
        } else {
            show_window(__('Error'), __('Any users found'));
        }
    }


 function web_UserArrayShower($usersarr) {
     $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
     
        if (!empty ($usersarr)) {
            $alladdress=zb_AddressGetFulladdresslist();
            $allrealnames=zb_UserGetAllRealnames();
            $alltariffs=zb_TariffsGetAllUsers();
            $allusercash=zb_CashGetAllUsers();
            $allusercredits=zb_CreditGetAllUsers();
            $alluserips=zb_UserGetAllIPs();
            
            if ($alterconf['ONLINE_LAT']) {
                $alluserlat=zb_LatGetAllUsers();
            }
            
            
            //additional finance links
    if ($alterconf['FAST_CASH_LINK']) {
           $fastcash=true;
       } else {
           $fastcash=false;
       }
            
            $tablecells=wf_TableCell(__('Login'));
            $tablecells.=wf_TableCell(__('Address'));
            $tablecells.=wf_TableCell(__('Real Name'));
            $tablecells.=wf_TableCell(__('IP'));
            $tablecells.=wf_TableCell(__('Tariff'));
            // last activity time
            if ($alterconf['ONLINE_LAT']) {
               $tablecells.=wf_TableCell(__('LAT'));
            }
            $tablecells.=wf_TableCell(__('Active'));
            //online detect
            if ($alterconf['DN_ONLINE_DETECT']) {
               $tablecells.=wf_TableCell(__('Users online'));
            }
            $tablecells.=wf_TableCell(__('Balance'));
            $tablecells.=wf_TableCell(__('Credit'));
            
           
            
            $tablerows=wf_TableRow($tablecells, 'row1');
            
            foreach ($usersarr as $eachlogin) {
                @$usercash=$allusercash[$eachlogin];
                @$usercredit=$allusercredits[$eachlogin];
                //finance check
                $activity=web_green_led();
                $activity_flag=1;
                if ($usercash<'-'.$usercredit) {
                 $activity=web_red_led();
                 $activity_flag=0;
                }
                
                 //fast cash link
            if ($fastcash) {
                $financelink=' <a href="?module=addcash&username='.$eachlogin.'"><img src="skins/icon_dollar.gif" border="0" title="'.__('Finance operations').'"></a> ';
             } else {
             $financelink='';
            } 
            
                $profilelink=$financelink.wf_Link('?module=userprofile&username='.$eachlogin, web_profile_icon().' '.$eachlogin);
                $tablecells=wf_TableCell($profilelink);
                $tablecells.=wf_TableCell(@$alladdress[$eachlogin]);
                $tablecells.=wf_TableCell(@$allrealnames[$eachlogin]);
                $tablecells.=wf_TableCell(@$alluserips[$eachlogin],'','','sorttable_customkey="'.  ip2int(@$alluserips[$eachlogin]).'"');
                $tablecells.=wf_TableCell(@$alltariffs[$eachlogin]);
                 if ($alterconf['ONLINE_LAT']) {
                        $tablecells.=wf_TableCell(date("Y-m-d H:i:s",$alluserlat[$eachlogin]));
                    }
                $tablecells.=wf_TableCell($activity,'','','sorttable_customkey="'.$activity_flag.'"');
                if ($alterconf['DN_ONLINE_DETECT']) {
                   if (file_exists(DATA_PATH.'dn/'.$eachlogin)) {
                        $online_flag=1;
                        } else {
                        $online_flag=0;
                        }
                $tablecells.=wf_TableCell(web_bool_star($online_flag), '', '', 'sorttable_customkey="'.$online_flag.'"');
                }
                $tablecells.=wf_TableCell($usercash);
                $tablecells.=wf_TableCell($usercredit);
                
               
                $tablerows.=wf_TableRow($tablecells, 'row3');
            }
            
            $result=wf_TableBody($tablerows, '100%', '0', 'sortable');
            $result.='<b>'.__('Total').':</b> '.sizeof($usersarr);
            } else {
            $result=__('Any users found');
           }
        
        return ($result);
    }

    
function strtolower_utf8($string){
  $convert_to = array(
    "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u",
    "v", "w", "x", "y", "z", "à", "á", "â", "ã", "ä", "å", "æ", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï",
    "ð", "ñ", "ò", "ó", "ô", "õ", "ö", "ø", "ù", "ú", "û", "ü", "ý", "а", "б", "в", "г", "д", "е", "ё", "ж",
    "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "ч", "ш", "щ", "ъ", "ы",
    "ь", "э", "ю", "я", "ы"
  );
  $convert_from = array(
    "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U",
    "V", "W", "X", "Y", "Z", "À", "Á", "Â", "Ã", "Ä", "Å", "Æ", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï",
    "Ð", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "Ø", "Ù", "Ú", "Û", "Ü", "Ý", "А", "Б", "В", "Г", "Д", "Е", "Ё", "Ж",
    "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Ч", "Ш", "Щ", "Ъ", "Ъ",
    "Ь", "Э", "Ю", "Я", "Ы"
  );

  return str_replace($convert_from, $convert_to, $string);
} 


function zb_BillingCheckUpdates() {
    $release_url='http://ubilling.net.ua/RELEASE';
    @$last_release=  file_get_contents($release_url);
    if ($last_release) {
       $result=__('Last stable release is').': '.$last_release;
    } else {
        $result=__('Error checking updates');
    }

    die($result);
}

   function zb_BillingStats($quiet=false) {
        $ubstatsurl=file_get_contents(CONFIG_PATH."ubstats");
        $ubstatsurl=trim($ubstatsurl);
        
     //detect host id
     $hostid_q="SELECT * from `ubstats` WHERE `key`='ubid'";
     $hostid=simple_query($hostid_q);
     if (empty($hostid)) {
         //register new ubilling
         $randomid='UB'.md5(curdatetime().zb_rand_string(8));
         $newhostid_q="INSERT INTO `ubstats` (`id` ,`key` ,`value`) VALUES (NULL , 'ubid', '".$randomid."');";
         nr_query($newhostid_q);
         $thisubid=$randomid;
     } else {
         $thisubid=$hostid['value'];
     }
     
     //detect stats collection feature
     $statscollect_q="SELECT * from `ubstats` WHERE `key`='ubcollect'";
     $statscollect=simple_query($statscollect_q);
     if (empty($statscollect)) {
         $newstatscollect_q="INSERT INTO `ubstats` (`id` ,`key` ,`value`) VALUES (NULL , 'ubcollect', '1');";
         nr_query($newstatscollect_q);
         $thiscollect=1;
     } else {
         $thiscollect=$statscollect['value'];
     }
     
     //disabling collect subroutine
     if (isset($_POST['editcollect'])) {
     if (!isset($_POST['collectflag'])) {
         simple_update_field('ubstats', 'value', '0', "WHERE `key`='ubcollect'");
     } else {
         simple_update_field('ubstats', 'value', '1', "WHERE `key`='ubcollect'");
     }
     rcms_redirect("?module=report_sysload");
     }
     //detect total user count
     $usercount_q="SELECT COUNT(`login`) from `users`";
     $usercount=simple_query($usercount_q);
     $usercount=$usercount['COUNT(`login`)'];
     
     //detect tariffs count
     $tariffcount_q="SELECT COUNT(`name`) from `tariffs`";
     $tariffcount=simple_query($tariffcount_q);
     $tariffcount=$tariffcount['COUNT(`name`)'];
     
     //detect nas count
     $nascount_q="SELECT COUNT(`id`) from `nas`";
     $nascount=simple_query($nascount_q);
     $nascount=$nascount['COUNT(`id`)'];
     
     //detect payments count
     $paycount_q="SELECT COUNT(`id`) from `payments`";
     $paycount=simple_query($paycount_q);
     $paycount=$paycount['COUNT(`id`)'];
     $paycount=$paycount/100;
     $paycount=round($paycount);
     
     //detect ubilling actions count
     $eventcount_q="SELECT COUNT(`id`) from `weblogs`";
     $eventcount=simple_query($eventcount_q);
     $eventcount=$eventcount['COUNT(`id`)'];
     $eventcount=$eventcount/100;
     $eventcount=round($eventcount);
     
     //detect ubilling version
     $releaseinfo=file_get_contents("RELEASE");
     $ubversion=explode(' ',$releaseinfo);
     $ubversion=vf($ubversion[0],3);

     $releasebox=wf_tag('span', false, '', 'id="lastrelease"');
     $releasebox.=wf_tag('span',true).'<br>';
     $updatechecker='<a href="#checkupdates"  onclick="goajax(\'?module=report_sysload&checkupdates=true\',\'lastrelease\');" title="'.__('Check updates').'">'.$releaseinfo.' ('.__('Check updates').'?)</a>';

     $ubstatsinputs=zb_AjaxLoader();
     
     $ubstatsinputs.='<b>'.__('Serial key').':</b> '.$thisubid.'<br>';
     $ubstatsinputs.='<b>'.__('Ubilling version').':</b> '.$updatechecker.'<br>';
     $ubstatsinputs.=$releasebox;
     $ubstatsinputs.=wf_HiddenInput('editcollect', 'true');
     $ubstatsinputs.=wf_CheckInput('collectflag', 'I want to help make Ubilling better', false, $thiscollect);
     $ubstatsinputs.=' '.wf_Submit('Save');
     $ubstatsform = wf_Form("",'POST',$ubstatsinputs,'glamour');
     
     $statsurl=$ubstatsurl.'?u='.$thisubid.'x'.$usercount.'x'.$tariffcount.'x'.$nascount.'x'.$paycount.'x'.$eventcount.'x'.$ubversion;
     $tracking_code='<div style="display:none;"><iframe src="'.$statsurl.'" width="1" height="1" frameborder="0"></iframe></div>';    
     
     if ($quiet==false) {
     show_window(__('Billing info'),$ubstatsform);
     }
     
      if ($thiscollect) {
         show_window('',$tracking_code);
     }
    }
    
    
 function crc16($string) {
  $crc = 0xFFFF;
  for ($x = 0; $x < strlen ($string); $x++) {
    $crc = $crc ^ ord($string[$x]);
    for ($y = 0; $y < 8; $y++) {
      if (($crc & 0x0001) == 0x0001) {
        $crc = (($crc >> 1) ^ 0xA001);
      } else { $crc = $crc >> 1; }
    }
  }
  return $crc;
} 

//lookups vendor by mac
  function zb_MacVendorLookup($mac) {
        $altcfg=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
        $result='';
        
        if (!empty($altcfg['MACVENAPI_KEY'])) {
        $apikey=$altcfg['MACVENAPI_KEY'];
        $url='http://www.macvendorlookup.com/api/'.$apikey.'/';
        $mac=  str_replace(':', '', $mac);
        @$rawdata=file_get_contents($url.$mac);
        
        if (!empty($rawdata)) {
            $data=  explode("|", $rawdata);
            if (!empty($data)) {
               $result=$data[0];
                }
            }
            
        } else {
            $result=__('No macvendorlookup.com API key set');
        }
        
        return ($result);
    }

///////////////////////
// discounts support //
///////////////////////

function zb_DiscountsGetAllUsers() {
    $alterconf=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    $cfid=$alterconf['DISCOUNT_PERCENT_CFID'];
    $cfid=vf($cfid,3);
    $result=array();
    if (!empty($cfid)) {
        $query="SELECT * from `cfitems` WHERE `typeid`='".$cfid."'";
        $alldiscountusers=  simple_queryall($query);
        if (!empty($alldiscountusers)) {
            foreach ($alldiscountusers as $io=>$each) {
                $result[$each['login']]=vf($each['content']);
            }
        }
    }
    return ($result);
}


function zb_DiscountsGetMonthPayments($month) {
    $query="SELECT * from `payments` WHERE `date` LIKE '".$month."%' AND `summ`>0";
    $allpayments=  simple_queryall($query);
    $result=array();
    if (!empty($allpayments)) {
        foreach ($allpayments as $io=>$each) {
            //if not only one payment
            if (isset($result[$each['login']])) {
              $result[$each['login']]=$result[$each['login']]+$each['summ'];  
            } else {
               $result[$each['login']]=$each['summ'];
            }
            
            
        }
    }
    return ($result);
}


function zb_DiscountProcessPayments($debug=false) {
    $alldiscountusers=  zb_DiscountsGetAllUsers();
    $monthpayments=  zb_DiscountsGetMonthPayments(curmonth());
    $alterconf= rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    $cashtype=$alterconf['DISCOUNT_CASHTYPEID'];
    $operation=$alterconf['DISCOUNT_OPERATION'];
    
    
    if ((!empty($alldiscountusers) AND (!empty($monthpayments)))) {
        foreach ($monthpayments as $login=>$eachpayment) {
            //have this user discount?
            if (isset($alldiscountusers[$login])) {
                //yes it have
                $discount_percent=$alldiscountusers[$login];
                $payment_summ=$eachpayment;
                $discount_payment=($payment_summ/100)*$discount_percent;
                
               
                
                if ($operation=='CORR') {
                    zb_CashAdd($login, $discount_payment, 'correct', $cashtype, 'DISCOUNT:'.$discount_percent);
                } 
                
                if ($operation=='ADD') {
                    zb_CashAdd($login, $discount_payment, 'add', $cashtype, 'DISCOUNT:'.$discount_percent);
                }
                
                if ($debug) {
                print('USER:'.$login.' SUMM:'.$payment_summ.' DISCOUNT:'.$discount_percent.' PAYMENT:'.$discount_payment."\n");    
                log_register("DISCOUNT ".$operation." (".$login.") ON ".$discount_payment);
                }
                
                
            }
        }
    }
}

 
 function web_ConfigEditorShow($prefix,$configdata,$optsdata) {
    global $hide_passwords;
    $result='';
    if ((!empty($configdata)) AND (!empty($optsdata))) {
        foreach ($optsdata as $option=>$handlers) {
            
           if (isset($configdata[$option])) {
           $currentdata=$configdata[$option];
           $handlers=  explode('|', $handlers);
           $type=$handlers[0];
           
           //option description
           if (!empty($handlers[1])) {
               $description=trim($handlers[1]);
               $description=__($description);
           } else {
               $description=$option;
           }
           
           //option controls
           if ($type=='TRIGGER') {
               $control=  web_bool_led($configdata[$option]);
           }
           
           if ($type=='VARCHAR') {
               if ($hide_passwords) {
                   if (isset($handlers[2])) {
                       if ($handlers[2]=='PASSWD') {
                           $datavalue=__('Hidden');
                       } else {
                       $datavalue=$configdata[$option];
                       }
                   } else {
                       $datavalue=$configdata[$option];
                   }
               } else {
                       $datavalue=$configdata[$option];
                   }
               $control='<input type="text" name="'.$prefix.'_'.$option.'" size="25" value="'.$datavalue.'" readonly>'."\n";
           }
           
      
           $result.=$control.' '.$description.'<br>';
           } else {
             if (ispos($option,'CHAPTER')) {
                    $result.=wf_tag('h3', false);
                    $result.=__($handlers);
                    $result.=wf_tag('h3', true);
                    
             } else {
             $result.=wf_tag('font', false, '', 'color="#FF0000"');
             $result.=__('You missed an important option').': '.$option.'';
             $result.=wf_tag('font', true);
             $result.='<br>';
             }
           }
           
           
        }
    }
    
    return ($result);
}

 /*
  * Constructs ajax loader 
  * 
  * @return string
  */    
     
  function zb_AjaxLoader() {
      $result='
          <script type="text/javascript">
        function getXmlHttp()
        {
            var xmlhttp;
            try
        {
            xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
        }
        catch (e)
        {
            try
            {
                xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
            }
            catch (E)
            {
                xmlhttp = false;
            }
        }
 
        if(!xmlhttp && typeof XMLHttpRequest!=\'undefined\')
        {
            xmlhttp = new XMLHttpRequest();
        }
        return xmlhttp;
    }
 
    function goajax(link,container)
    {
 
        var myrequest = getXmlHttp()
        var docum = link;
        var contentElem = document.getElementById(container);
        myrequest.open(\'POST\', docum, true);
        myrequest.setRequestHeader(\'Content-Type\', \'application/x-www-form-urlencoded\');
 
        myrequest.onreadystatechange = function()
        {
            if (myrequest.readyState == 4)
            {
                if(myrequest.status == 200)
                {
                    var resText = myrequest.responseText;
 
 
                    var ua = navigator.userAgent.toLowerCase();
 
                    if (ua.indexOf(\'gecko\') != -1)
                    {
                        var range = contentElem.ownerDocument.createRange();
                        range.selectNodeContents(contentElem);
                        range.deleteContents();
                        var fragment = range.createContextualFragment(resText);
                        contentElem.appendChild(fragment);
                    }
                    else  
                    {
                        contentElem.innerHTML = resText;
                    }
                }
                else
                {
                    contentElem.innerHTML = \''.__('Error').'\';
                }
            }
 
        }
        myrequest.send();
    }
    </script>
          ';
      return ($result);
  } 
  
  
  function zb_JSHider() {
      $result='
          <script language=javascript type=\'text/javascript\'>
            function showhide(id){
            if (document.getElementById){
            obj = document.getElementById(id);
            if (obj.style.display == "none"){
            obj.style.display = "";
            } else {
            obj.style.display = "none";
            }
            }
           }
        </script> 
          ';
      return ($result);
  }

 /*
 * Database cleanup features
 */
  
 /*
 * Gets list of old stargazer log_ tables exept current month
 * 
 * @return array
 */
function zb_DBCleanupGetLogs() {
$logs_query="SHOW TABLE STATUS WHERE `Name` LIKE 'logs_%'";
$allogs=  simple_queryall($logs_query);
$oldlogs=array();
$skiplog='logs_'.date("m").'_'.date("Y");
if (!empty($allogs)) {
    foreach ($allogs as $io=>$each) {
        $filtered=array_values($each);
        $oldlogs[$filtered[0]]['name']=$each['Name'];
        $oldlogs[$filtered[0]]['rows']=$each['Rows'];
        $oldlogs[$filtered[0]]['size']=$each['Data_length'];
        
    }
    
   
  }
  
  if (!empty($oldlogs)) {
      unset($oldlogs[$skiplog]);
  }

 return ($oldlogs);
}


 /*
 * Gets list of old stargazer detailstat_ tables exept current month
 * 
 * @return array
 */
function zb_DBCleanupGetDetailstat() {
$detail_query="SHOW TABLE STATUS WHERE `Name` LIKE 'detailstat_%'";
$all=  simple_queryall($detail_query);
$old=array();
$skip='detailstat_'.date("m").'_'.date("Y");
if (!empty($all)) {
    foreach ($all as $io=>$each) {
        $filtered=array_values($each);
        $old[$filtered[0]]['name']=$each['Name'];
        $old[$filtered[0]]['rows']=$each['Rows'];
        $old[$filtered[0]]['size']=$each['Data_length'];
        
    }
    
   
  }
  
  if (!empty($old)) {
      unset($old[$skip]);
  }

 return ($old);
}

/*
 * Destroy or flush table in database
 * 
 * @param $tablename  string table name 
 * @return void
 */

function zb_DBTableCleanup($tablename) {
    $tablename=vf($tablename);
    $method='DROP';
    if (!empty($tablename)) {
        $query=$method." TABLE `".$tablename."`";
        nr_query($query);
        log_register("DBCLEANUP `".$tablename."`");
    }
}

/*
 * Shows database cleanup form
 * 
 * @return string
 */
function web_DBCleanupForm() {
    $oldLogs=  zb_DBCleanupGetLogs();
    $oldDetailstat=  zb_DBCleanupGetDetailstat();
    $cleanupData=$oldLogs+$oldDetailstat;
    $result='';
    $totalRows=0;
    $totalSize=0;
    $totalCount=0;
    
    $cells=  wf_TableCell(__('Table name'));
    $cells.= wf_TableCell(__('Rows'));
    $cells.= wf_TableCell(__('Size'));
    $cells.= wf_TableCell(__('Actions'));
    $rows=  wf_TableRow($cells, 'row1');
    
    if (!empty($cleanupData)) {
        foreach ($cleanupData as $io=>$each) {
            $cells=  wf_TableCell($each['name']);
            $cells.= wf_TableCell($each['rows']);
            $cells.= wf_TableCell(stg_convert_size($each['size']),'','','sorttable_customkey="'.$each['size'].'"');
            $actlink=  wf_JSAlert("?module=backups&tableclean=".$each['name'], web_delete_icon(), 'Are you serious');
            $cells.= wf_TableCell($actlink);
            $rows.=  wf_TableRow($cells, 'row3');
            $totalRows=$totalRows+$each['rows'];
            $totalSize=$totalSize+$each['size'];
            $totalCount=$totalCount+1;
        }
    }
    
    $result=  wf_TableBody($rows, '100%', '0', 'sortable');
    $result.=wf_tag('b').__('Total').': '.$totalCount.' / '.$totalRows.' / '.stg_convert_size($totalSize).  wf_tag('b',true);
    
    return ($result);
}

/*
 * Auto Cleans all deprecated data
 * 
 * @return string count of cleaned tables
 */

function zb_DBCleanupAutoClean() {
    $oldLogs=  zb_DBCleanupGetLogs();
    $oldDstat=  zb_DBCleanupGetDetailstat();
    $allClean=$oldLogs+$oldDstat;
    $counter=0;
    if (!empty($allClean)) {
        foreach ($allClean as $io=>$each) {
            zb_DBTableCleanup($each['name']);
            $counter++;
        }
    }
    return ($counter);
}
/*  UTF8-safe translit function
 * 
 * @param $string  string to be transliterated
 * @return string
 */  

function zb_TranslitString($string) {
	$replace=array(
		"'"=>"",
		"`"=>"",
		"а"=>"a","А"=>"a",
		"б"=>"b","Б"=>"b",
		"в"=>"v","В"=>"v",
		"г"=>"g","Г"=>"g",
		"д"=>"d","Д"=>"d",
		"е"=>"e","Е"=>"e",
		"ж"=>"zh","Ж"=>"zh",
		"з"=>"z","З"=>"z",
		"и"=>"i","И"=>"i",
		"й"=>"y","Й"=>"y",
		"к"=>"k","К"=>"k",
		"л"=>"l","Л"=>"l",
		"м"=>"m","М"=>"m",
		"н"=>"n","Н"=>"n",
		"о"=>"o","О"=>"o",
		"п"=>"p","П"=>"p",
		"р"=>"r","Р"=>"r",
		"с"=>"s","С"=>"s",
		"т"=>"t","Т"=>"t",
		"у"=>"u","У"=>"u",
		"ф"=>"f","Ф"=>"f",
		"х"=>"h","Х"=>"h",
		"ц"=>"c","Ц"=>"c",
		"ч"=>"ch","Ч"=>"ch",
		"ш"=>"sh","Ш"=>"sh",
		"щ"=>"sch","Щ"=>"sch",
		"ъ"=>"","Ъ"=>"",
		"ы"=>"y","Ы"=>"y",
		"ь"=>"","Ь"=>"",
		"э"=>"e","Э"=>"e",
		"ю"=>"yu","Ю"=>"yu",
		"я"=>"ya","Я"=>"ya",
		"і"=>"i","І"=>"i",
		"ї"=>"yi","Ї"=>"yi",
		"є"=>"e","Є"=>"e"
	);
	return $str=iconv("UTF-8","UTF-8//IGNORE",strtr($string,$replace));
}
?>
