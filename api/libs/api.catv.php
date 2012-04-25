<?php

  function catv_GlobalControlsShow() {
      $controls='
          <a href="?module=catv&action=showusers" class="ubButton">'.__('Users list').'</a> 
          <a href="?module=catv&action=userreg" class="ubButton">'.__('Users registration').'</a>
          <a href="?module=catv&action=tariffs" class="ubButton">'.__('Tariffs').'</a> 
          <a href="?module=catv&action=fees" class="ubButton">'.__('Сharge monthly fee').'</a>
          <a href="?module=catv&action=reports" class="ubButton">'.__('Reports').'</a> 
          ';
      show_window(__('Cable TV controls'), $controls);
  }
  
  
  function catv_ProfileBack($userid) {
      $userid=vf($userid,3);
      $result='<a href="?module=catv_profile&userid='.$userid.'"><img src="skins/icon_user_big.gif" title="'.__('Back to user profile').'" border="0"> '.__('Back to user profile').'</a>';
      show_window('',$result);
  }
  
  function catv_LoadConfig() {
      $result=rcms_parse_ini_file(CONFIG_PATH."catv.ini");
      return ($result);
  }

  function catv_TariffAdd($name,$price,$chans='') {
      $name=mysql_real_escape_string($name);
      $price=vf($price);
      $chans=vf($chans,3);
      $query="
          INSERT INTO `catv_tariffs` (
            `id` ,
            `name` ,
            `price` ,
            `chans`
            )
            VALUES (
            NULL , '".$name."', '".$price."', '".$chans."'
            ); ";
      nr_query($query);
      log_register("CATV TARIFF ADD ".$name." ".$price);
  }
  
  function catv_TariffModify($tariffid,$name,$price,$chans) {
      $tariffid=vf($tariffid,3);
      $name=mysql_real_escape_string($name);
      $price=vf($price);
      $chans=vf($chans,3);
      $query="
          UPDATE `catv_tariffs` 
          SET `name` = '".$name."',
              `price` = '".$price."',
              `chans` = '".$chans."' 
              WHERE `id` = '".$tariffid."' ;";
      nr_query($query);
      log_register("CATV TARIFF CHANGE ".$tariffid." ".$name." ".$price);
  }
  
  function catv_TariffDelete($tariffid) {
      $tariffid=vf($tariffid,3);
      $query="DELETE FROM `catv_tariffs` WHERE `id`='".$tariffid."'";
      nr_query($query);
      log_register("CATV TARIFF DELETE ".$tariffid);
  }
  
  function catv_TariffGetAllNames() {
      $result=array();
      $query="SELECT `id`,`name` from `catv_tariffs`";
      $alltariffs=simple_queryall($query);
      if (!empty ($alltariffs)) {
          foreach ($alltariffs as $io=>$eachtariff) {
              $result[$eachtariff['id']]=$eachtariff['name'];
          }
      }
      return ($result);
  }
  
  
    function catv_TariffGetAllPrices() {
      $result=array();
      $query="SELECT `id`,`price` from `catv_tariffs`";
      $alltariffs=simple_queryall($query);
      if (!empty ($alltariffs)) {
          foreach ($alltariffs as $io=>$eachtariff) {
              $result[$eachtariff['id']]=$eachtariff['price'];
          }
      }
      return ($result);
  }
  
  function catv_TariffProtected($tariffid) {
      $tariffid=vf($tariffid,3);
      $query_now="SELECT `id` from `catv_users` WHERE `tariff`='".$tariffid."'";
      $query_nm="SELECT `id` from `catv_users` WHERE `tariff_nm`='".$tariffid."'";
      $using_now=simple_queryall($query_now);
      $using_nm=simple_queryall($query_nm);
      if ((!empty ($using_now)) OR (!empty ($using_nm))) {
          $result=true;
            } else {
            $result=false;  
          }
      return ($result);
      }
  
  function catv_TariffGetAll() {
      $query="SELECT * from `catv_tariffs`";
      $result=simple_queryall($query);
      return ($result);
  }
  
  function catv_TariffGetData($tariffid) {
      $tariffid=vf($tariffid,3);
      $query="SELECT * from `catv_tariffs` WHERE `id`='".$tariffid."'";
      $result=simple_query($query);
      return ($result);
  }
  
  function catv_TariffShowAll() {
      $alltariffs=catv_TariffGetAll();
      $titles=array('ID','Tariff name','Fee','Channels');
      $keys=array('id','name','price','chans');
      $module='catv';
      $result=web_GridEditor($titles, $keys, $alltariffs, $module, true, true,'tariff');
      show_window(__('Available tariffs'),$result);
  }
  
  function catv_TariffAddForm() {
     $inputs=wf_TextInput('newtariffname', 'Tariff name', '', true, '');
     $inputs.=wf_TextInput('newtariffprice','Fee','',true,'');
     $inputs.=wf_TextInput('newtariffchans','Channels count','',true,'');
     $inputs.=wf_Submit('Create');
     $form=wf_Form('', 'POST', $inputs,'glamour');
     show_window(__('Create new tariff'), $form);
  }
  
  
  function catv_TariffEditForm($tariffid) {
      $tariffid=vf($tariffid,3);
      $tariffdata=catv_TariffGetData($tariffid);
      $inputs=wf_HiddenInput('edittariffid', $tariffdata['id']);
      $inputs.=wf_TextInput('edittariffname', 'Tariff name', $tariffdata['name'], true, '');
      $inputs.=wf_TextInput('edittariffprice', 'Fee', $tariffdata['price'], true, '');
      $inputs.=wf_TextInput('edittariffchans', 'Channels count', $tariffdata['chans'], true, '');
      $inputs.=wf_Submit('Change');
      $form=wf_Form('', 'POST', $inputs,'glamour');
      show_window(__('Edit tariff'),$form);
  }
  
    function catv_ActivityCreate($userid,$state) {
      $userid=vf($userid,3);
      $state=vf($state,3);
      $admin=whoami();
      $date=curdatetime();
      $query="
          INSERT INTO `catv_activity` (
            `id` ,
            `userid` ,
            `state` ,
            `date` ,
            `admin`
            )
            VALUES (NULL , '".$userid."', '".$state."', '".$date."', '".$admin."');
           ";
      nr_query($query);
      log_register("CATV ACTIVITY CHANGE (".$userid.") ".$state);
  }
  
  
     function catv_ActivityCreateCustomDate($userid,$state,$customdate) {
      $userid=vf($userid,3);
      $state=vf($state,3);
      $admin=whoami();
      $query="
          INSERT INTO `catv_activity` (
            `id` ,
            `userid` ,
            `state` ,
            `date` ,
            `admin`
            )
            VALUES (NULL , '".$userid."', '".$state."', '".$customdate."', '".$admin."');
           ";
      nr_query($query);
      deb($query);
      log_register("CATV ACTIVITY CHANGE (".$userid.") ".$state);
  }
  
 function catv_UserProfileShow($userid) {
      $userdata=catv_UserGetData($userid);
      $catv_conf=catv_LoadConfig();
      $currency=$catv_conf['CURRENCY'];
      $alltariffnames=catv_TariffGetAllNames();
      $alladdress=catv_UsersGetFullAddressList();
      $tariffname=$alltariffnames[$userdata['tariff']];
      $tariffprice=catv_TariffGetData($userdata['tariff']);
      $tariffprice=$tariffprice['price'];
      if (!empty ($userdata['inetlink'])) {
          $inetlink='<a href="?module=userprofile&username='.$userdata['inetlink'].'"> '.web_profile_icon().' '.$userdata['inetlink'].'</a>';
      } else {
          $inetlink=__('No');
      }
      $activity=catv_ActivityGetLastByUser($userid);
      $activity_time=catv_ActivityGetTimeLastByUser($userid);
      
      $result='
      <table width="100%" border="0">
        <tr class="row3">
          <td class="row2" width="30%">ID</td>
          <td>'.$userdata['id'].'</td>
        </tr>
        <tr class="row3">
          <td class="row2">'.__('Contract').'</td>
           <td>'.$userdata['contract'].'</td>
        </tr>
        <tr class="row3">
           <td class="row2">'.__('Real name').'</td>
         <td>'.$userdata['realname'].'</td>
        </tr>
        <tr class="row3">
         <td class="row2">'.__('Full address').'</td>
          <td>'.@$alladdress[$userdata['id']].'</td>
        </tr>
        <tr class="row3">
            <td class="row2">'.__('Phone').'</td>
          <td>'.$userdata['phone'].'</td>
        </tr>
        <tr class="row3">
           <td class="row2">'.__('Balance').'</td>
            <td>'.$userdata['cash'].' '.$currency.'</td>
        </tr>
        <tr class="row3">
         <td class="row2">'.__('Tariff').'</td>
          <td>'.$tariffname.'</td>
        </tr>
        <tr class="row3">
         <td class="row2">'.__('Planned tariff change').'</td>
         <td>'.@$alltariffnames[$userdata['tariff_nm']].'</td>
        </tr>
        <tr class="row3">
         <td class="row2">'.__('Fee').'</td>
         <td>'.$tariffprice.' '.$currency.'</td>
        </tr>
        <tr class="row3">
         <td class="row2">'.__('Discount').'</td>
         <td>'.$userdata['discount'].' '.$currency.'</td>
        </tr>
       
        <tr class="row3">
         <td class="row2">'.__('Connected').'</td>
         <td>'.web_bool_led($activity).'</td>
        </tr>
        <tr class="row3">
         <td class="row2">'.__('Connection date').'</td>
         <td>'.$activity_time.'</td>
        </tr>
        <tr class="row3">
         <td class="row2">'.__('Decoder').'</td>
         <td>'.$userdata['decoder'].'</td>
        </tr>
        <tr class="row3">
         <td class="row2">'.__('Internet account').'</td>
         <td>'.$inetlink.'</td>
        </tr>
        <tr class="row3">
         <td class="row2">'.__('Notes').'</td>
         <td>'.$userdata['notes'].'</td>
        </tr>
      </table>
          ';
      $result.=catv_ProfileControls($userid);
      show_window(__('CaTV user profile'),$result);
  }
    
  
  
  function catv_UserRegister($contract,$realname,$street,$build,$apt,$phone,$tariff,$cash,$decoder) {
      $catvconf=catv_LoadConfig();
      $contract=mysql_real_escape_string($contract);
      $realname=mysql_real_escape_string($realname);
      $street=mysql_real_escape_string($street);
      $apt=mysql_real_escape_string($apt);
      $phone=mysql_real_escape_string($phone);
      $tariff=vf($tariff,3);
      $cash=mysql_real_escape_string($cash);
      $decoder=mysql_real_escape_string($decoder);
      $query="
          INSERT INTO `catv_users` (
                        `id` ,
                        `contract` ,
                        `realname` ,
                        `street` ,
                        `build` ,
                        `apt` ,
                        `phone` ,
                        `tariff` ,
                        `tariff_nm` ,
                        `cash` ,
                        `discount` ,
                        `notes` ,
                        `decoder` ,
                        `inetlink`
                        )
                    VALUES (
                         NULL ,
                         '".$contract."',
                         '".$realname."',
                         '".$street."',
                         '".$build."',
                         '".$apt."',
                         '".$phone."',
                         '".$tariff."',
                         '',
                         '".$cash."',
                         '0',
                         '',
                         '".$decoder."',
                         NULL
                       );  ";
      nr_query($query);
      $newuserid=simple_get_lastid('catv_users');
      $date=curdatetime();
      $admin=whoami();
      $log_signup="INSERT INTO `catv_signups` (
                    `id` ,
                    `date` ,
                    `userid` ,
                    `admin`
                        )
                        VALUES (
                    NULL , '".$date."', '".$newuserid."', '".$admin."'
                    );  ";
      nr_query($log_signup);
      log_register("CATV USER REGISTER (".$newuserid.") ".$contract);
      catv_ActivityCreate($newuserid, $catvconf['REG_ACTIVITY']);
  }
  
  function catv_UserRegisterForm() {
      $alltariffs=catv_TariffGetAllNames();
      $inputs=wf_HiddenInput('realyregister', 'true');
      $inputs.=wf_TextInput('newusercontract', 'Contract', '', true, '');
      $inputs.=wf_TextInput('newuserrealname', 'Real Name', '', true, 30);
      $inputs.=wf_TextInput('newuserstreet', 'Street', '', true, 30);
      $inputs.=wf_TextInput('newuserbuild', 'Build', '', true, 5);
      $inputs.=wf_TextInput('newuserapt', 'Apartment', '', true, 5);
      $inputs.=wf_TextInput('newuserphone', 'Phone', '', true, 30);
      $inputs.=wf_TextInput('newusercash', 'Cash', '0', true, 5);
      $inputs.=wf_TextInput('newuserdecoder', 'Decoder', '', true, 5);
      $inputs.=wf_Selector('newusertariff', $alltariffs, 'Tariff', '', true);
      $inputs.=wf_Submit('Register this user');
      $form=wf_Form('', 'POST', $inputs, 'glamour');
      show_window(__('CaTV user registration'),$form);
  }
  
  
  function catv_UsersShowList() {
      $tariffnames=catv_TariffGetAllNames();
      $query="SELECT * from `catv_users`";
      $allusers=simple_queryall($query);
      $allstates=catv_ActivityGetLastAll();
      $extendedfilters='
          <a href="javascript:showfiltercatv();">'.__('Extended filters').'</a>
          ';
      $result='
          '.$extendedfilters.'
          <table width="100%" class="sortable" id="catvusers">
                <tr class="row1">
                  <td>'.__('ID').'</td>
                  <td>'.__('Contract').'</td>
                  <td>'.__('Real name').'</td>
                  <td>'.__('Street').'</td>
                  <td>'.__('Build').'</td>
                  <td>'.__('Apartment').'</td>
                  <td>'.__('Tariff').'</td>
                  <td>'.__('Cash').'</td>
                  <td>'.__('Connected').'</td>
                  <td>'.__('Actions').'</td>
                </tr>
          ';
      if (!empty ($allusers)) {
          foreach ($allusers as $io=>$eachuser) {
              $result.='
                  <tr class="row3">
                  <td>'.$eachuser['id'].'</td>
                  <td>'.$eachuser['contract'].'</td>
                  <td>'.$eachuser['realname'].'</td>
                  <td>'.$eachuser['street'].'</td>
                  <td>'.$eachuser['build'].'</td>
                  <td>'.$eachuser['apt'].'</td>
                  <td>'.@$tariffnames[$eachuser['tariff']].'</td>
                  <td>'.$eachuser['cash'].'</td>
                  <td>'.web_bool_led(catv_ActivityCheck($eachuser['id'], $allstates),true).'</td>
                  <td valign="top">
                  <a href="?module=catv_profile&userid='.$eachuser['id'].'">'.  web_profile_icon().'</a>
                  <a href="?module=catv_addcash&userid='.$eachuser['id'].'"><img src="skins/icon_dollar.gif" title="'.__('Finance operations').'" border="0"></a>
                   <a href="?module=catv_stats&userid='.$eachuser['id'].'"><img src="skins/icon_stats.gif" title="'.__('Connection time').'" border="0"></a>
                  </td>
                  </tr>
                  ';
          }
      }
      $js_filters='
            <script language="javascript" type="text/javascript">
             //<![CDATA[
            function showfiltercatv() {
            var catvfilters = {
		btn: false,
		col_9: "none",
                col_6: "select",
                col_8: "select",
                col_0: "none",
		btn_text: ">"
                }
                setFilterGrid("catvusers",0,catvfilters);
                }
                //]]>
            </script>
          ';
      $result.='</table>'.$js_filters;
      
      show_window(__('Available users'),$result);
  }
  
  function catv_UsersGetFullAddressList() {
      $query="SELECT `id`,`street`,`build`,`apt` from `catv_users`";
      $allusers=simple_queryall($query);
      $result=array();
      if (!empty ($allusers)) {
          foreach ($allusers as $io=>$eachuser) {
              $useraddress=$eachuser['street'].' '.$eachuser['build'].'/'.$eachuser['apt'];
              $result[$eachuser['id']]=$useraddress;
          }
      }
      return ($result);
  }
  
    
  function catv_UsersGetAll() {
      $query="SELECT * from `catv_users`";
      $result=simple_queryall($query);
      return ($result);
  }
  
  function catv_UserGetData($userid) {
      $userid=vf($userid,3);
      $query="SELECT * from `catv_users` WHERE `id`='".$userid."'";
      $result=simple_query($query);
      return ($result);
  }
  
  function catv_ActivityGetAllByUser($userid) {
      $userid=vf($userid,3);
      $query="SELECT * from `catv_activity` WHERE `userid`='".$userid."'";
      $result=simple_queryall($query);
      return ($result);
  }
  
   function catv_ActivityGetAllByUserAndYear($userid,$year) {
      $userid=vf($userid,3);
      $year=vf($year);
      $query="SELECT * from `catv_activity` WHERE `userid`='".$userid."' AND `DATE` BETWEEN '".$year."-01-01 01:01:01' AND '".($year+1)."-01-01 23:59:29'";
      $result=simple_queryall($query);
      return ($result);
  }
  
  
  function catv_ActivityGetLastByUser($userid) {
      $userid=vf($userid,3);
      $query="SELECT `state` from `catv_activity` WHERE `userid`='".$userid."' ORDER by `date` DESC LIMIT 1;";
      $result=simple_query($query);
      if (!empty ($result)) {
          $result=$result['state'];
      } else {
          // user is disconnected by default
          $result=0;
      }
      return ($result);
  }
  
  
    
  function catv_ActivityGetLastAll() {
      $query="SELECT userid, state, date from catv_activity c1 where date = (select max(date) from catv_activity c2 where c1.userid = c2.userid);";
      $allusers=simple_queryall($query);
      $result=array();
      if (!empty ($allusers)) {
          foreach ($allusers as $io=>$eachstate) {
              $result[$eachstate['userid']]=$eachstate['state'];
          }
      }
      return ($result);
  }
  
  function catv_ActivityCheck ($userid,$allstates) {
      $userid=vf($userid,3);
       if (isset($allstates[$userid])) {
          $result=$allstates[$userid];
      } else {
          //disconnected by default
          $result=0;
      }
      return ($result);
  }
  
  function catv_ActivityGetTimeLastByUser($userid) {
      $userid=vf($userid,3);
      $query="SELECT `date` from `catv_activity` WHERE `userid`='".$userid."' ORDER by `date` DESC LIMIT 1;";
      $result=simple_query($query);
      if (!empty ($result)) {
          $result=$result['date'];
      } else {
          // user is disconnected by default
          $result='';
      }
      return ($result);
  }
  
  
  function catv_PaymentsGetAllByUser($userid) {
      $userid=vf($userid,3);
      $query="SELECT * from `catv_payments` WHERE `userid`='".$userid."' ";
      $result=simple_queryall($query);
      return ($result);
  }
  
  function catv_PaymentsGetData($paymentid) {
      $paymentid=vf($paymentid,3);
      $query="SELECT * from `catv_payments` WHERE `id`='".$paymentid."' ";
      $result=simple_query($query);
      return ($result);
  }
  
  function catv_ProfileControls($userid) {
      $userid=vf($userid);
      $controls='
        <table width="100%" border="0">
          <tr valign="bottom">
          <td>
           <a href="?module=catv_stats&userid='.$userid.'"><img src="skins/icon_stats_big.gif" border="0" title="'.__('Connection time').'"></a>
          </td>
          <td>
           <a href="?module=catv_addcash&userid='.$userid.'"><img src="skins/icon_cash_big.gif" border="0" title="'.__('Cash').'"></a>
          </td>
          <td>
            <a href="?module=catv_tariffedit&userid='.$userid.'"><img src="skins/icon_tariff_big.gif" border="0" title="'.__('Tariff').'"></a>
          </td>
          <td>
            <a href="?module=catv_decoderedit&userid='.$userid.'"><img src="skins/icon_decoder_big.png" border="0" title="'.__('Decoder').'"></a>
          </td>
          <td>
           <a href="?module=catv_useractivity&userid='.$userid.'"><img src="skins/icon_scissors_big.gif" border="0" title="'.__('Connection').'"></a>
          </td>
          <td>
           <a href="?module=catv_useredit&userid='.$userid.'"><img src="skins/icon_user_edit_big.gif" border="0" title="'.__('Edit').'"></a>
          </td>
          </tr>
       </table>
          ';
      return ($controls);
  }
  
  function catv_CalendarBody() {
       $monts=months_array();
       $curmonth=date("m");
       $result='<table width="100%" border="0">';
       $result.='<tr>';
       //month list
       foreach ($monts as $monthnum=>$eachmonth) {
           if ($monthnum==$curmonth) {
               $current_class='class="row1"';
           } else {
               $current_class='class="row3"';
           }
           $result.='<td '.$current_class.'>'.rcms_date_localise($eachmonth).'</td>';
           
       }
       $result.='</tr>';
       //calendar body
       $result.='<tr height="64" class="CatvBad">';
       foreach ($monts as $monthnum=>$eachmonth) {
           $result.='<td id="cmonth'.$monthnum.'"><span id="cbody'.$monthnum.'"></span></td>';
       }
       $result.='</tr>';
       
       // online row
       $result.='<tr height="16">';
       foreach ($monts as $monthnum=>$eachmonth) {
           $result.='<td id="conline'.$monthnum.'"><span id="actbody'.$monthnum.'"></span></td>';
       }
       $result.='</tr>';
       
       $result.='</table>';
       return ($result);
    }
    
    function catv_CalendarMonthColorizer($month,$class) {
        $result='
           <script type="text/javascript">
             document.getElementById("cmonth'.$month.'").setAttribute("class", "'.$class.'");
           </script>
           ';
        return ($result);
    }
    
     function catv_CalendarOnlineColorizer($month,$class) {
        $result='
           <script type="text/javascript">
             document.getElementById("conline'.$month.'").setAttribute("class", "'.$class.'");
           </script>
           ';
        return ($result);
    }
    
    
    function catv_CalendarSetGood($month) {
        $result=catv_CalendarMonthColorizer($month, 'CatvGood');
        return ($result);
    }
    
    function catv_CalendarSetGoodLong($month) {
        $result=catv_CalendarMonthColorizer($month, 'CatvGoodLong');
        return ($result);
    }
    
    function catv_CalendarSetBad($month) {
        $result=catv_CalendarMonthColorizer($month, 'CatvBad');
        return ($result);
    }
    
    function catv_CalendarSetOnline($month) {
        $result=catv_CalendarOnlineColorizer($month, 'CatvOnline');
        return ($result);
    }
    
    function catv_CalendarSetOffline($month) {
        $result=catv_CalendarOnlineColorizer($month, 'CatvOffline');
        return ($result);
    }
    
    function catv_CalendarWrite($month,$text) {
        $result='
           <script type="text/javascript">
            document.getElementById("cbody'.$month.'").innerHTML = \''.$text.'\';
           </script>
            ';
        return ($result);
    }
    
     function catv_CalendarWriteActivity($month,$text) {
        $result='
           <script type="text/javascript">
            document.getElementById("actbody'.$month.'").innerHTML = \''.$text.'\';
           </script>
            ';
        return ($result);
    }
    
       function catv_UserStatsDrawPayments($userid,$year) {
            $catvconf=catv_LoadConfig();
            $alluserpayments=catv_PaymentsGetAllByUser($userid);
            $yearactivity=catv_ActivityGetAllByUserAndYear($userid, $year);
            $targetyearpayments=array();
            $userdata=catv_UserGetData($userid);
            $usertariff=$userdata['tariff'];
            $tariffdata=catv_TariffGetData($usertariff);
            $tariffprice=$tariffdata['price'];
            $montharray=months_array();
            $actlog=array();
            $payments_table=' <h2>'.__('Previous payments').'</h2>
                            <table width="100%" border="0" class="sortable">';
            $payments_table.='
                            <tr class="row1">
                            <td>'.__('ID').'</td>
                            <td>'.__('Date').'</td>
                            <td>'.__('Cash').'</td>
                            <td>'.__('From month').'</td>
                            <td>'.__('From year').'</td>
                            <td>'.__('Notes').'</td>
                            <td>'.__('Admin').'</td>
                            <td>'.__('Actions').'</td>
                            </tr>
                            ';
            
            if (!empty ($alluserpayments)) {
                //select only payments for needed year
                foreach ($alluserpayments as $ia=>$eachpayment) {
                    if ($eachpayment['from_year']==$year) {
                        $targetyearpayments[]=$eachpayment;
                    }
                }
                
                 //coloring year online and filling activity report
                    $onlinecolorizer='';
                    $onlinewriter='';
                    if (!empty ($yearactivity)) {
                    foreach ($yearactivity as $ic=>$eachactivity) {
                        $datemonth=date("m",strtotime($eachactivity['date']));
                        if ($eachactivity['state']==0) {
                            $onlinecolorizer.=catv_CalendarSetOffline($datemonth);
                            $onlinewriter.=catv_CalendarWriteActivity($datemonth, __('Disconnected'));
                            $actlog[$datemonth]='disconnected';
                        } 
                        if ($eachactivity['state']==1) {
                            $onlinecolorizer.=catv_CalendarSetOnline($datemonth);
                            $onlinewriter.=catv_CalendarWriteActivity($datemonth, __('Connected'));
                        } 
                        
                    }
                    }
                
                //print target payments to calendar
                if (!empty ($targetyearpayments)) {
                    $printpays='';
                    $colorizer='';
                    foreach ($targetyearpayments as $io=>$eachpayment) {
                        //calculate calendar offset for payments
                        if ($eachpayment['from_month']<10) {
                            $offset='0';
                        } else {
                            $offset='';
                        }
                                           
                        //insert payment to needed month
                        $printpays.=catv_CalendarWrite($offset.$eachpayment['from_month'], $eachpayment['date'].'<br><br>'.$eachpayment['summ'].' '.$catvconf['CURRENCY']);
                        //set payment month as good                        
                        $colorizer.=catv_CalendarSetGood($offset.$eachpayment['from_month']);
                        //maybe user payed for few months?
                        if ($eachpayment['summ']>$tariffprice) {
                          $additional_month=intval($eachpayment['summ']/$tariffprice);
                          for ($coloroffset=1;$coloroffset<$additional_month;$coloroffset++) {
                              //mb user was disabled?
                              if (isset($actlog[$offset.($eachpayment['from_month']+$coloroffset)])) {
                               $coloroffset=$coloroffset+1;
                               
                           }
                      
                          $colorizer.=catv_CalendarSetGoodLong($offset.($eachpayment['from_month']+$coloroffset));
                          }
                        }
                    }
                    
                    //show payments for year in normal view
                    foreach ($targetyearpayments as $ib=>$eachpayrow) {
                    //check is payments protected?
                    if ($catvconf['PAYMENTS_PROTECT']) {
                       $paycontrols='';
                   } else {
                       $paycontrols='
                           '.  wf_JSAlert('?module=catv_addcash&userid='.$eachpayrow['userid'].'&deletepayment='.$eachpayrow['id'], web_delete_icon(), 'Removing this may lead to irreparable results').'
                           '.  wf_JSAlert('?module=catv_addcash&userid='.$eachpayrow['userid'].'&editpayment='.$eachpayrow['id'], web_edit_icon(), 'Are you serious').'
                           ';
                   }
                        $payments_table.='
                            <tr class="row3">
                            <td>'.$eachpayrow['id'].'</td>
                            <td>'.$eachpayrow['date'].'</td>
                            <td>'.$eachpayrow['summ'].'</td>
                            <td>'.$eachpayrow['from_month'].'</td>
                            <td>'.$eachpayrow['from_year'].'</td>
                            <td>'.$eachpayrow['notes'].'</td>
                            <td>'.$eachpayrow['admin'].'</td>
                            <td>
                           '.$paycontrols.'
                            </td>
                            </tr>
                            ';
                        
                    }
                    $payments_table.='</table>';
                    
                
                    
                    //draw payments and colors to calendar
                    show_window('',$printpays.$colorizer.$onlinewriter.$onlinecolorizer.$payments_table);
                     
               
                }
                
            }
        }
        
 function catv_UserStatsByYear($userid,$year) {
          $allmonth=months_array();
          $calendar=catv_CalendarBody();
          show_window(__('User activity by').' '.$year,$calendar);
          catv_UserStatsDrawPayments($userid, $year);
          }
 
 function catv_UserSetTariffNM($userid,$tariffid) {
        $userid=vf($userid,3);
        $tariffid=vf($tariffid,3);
        simple_update_field('catv_users', 'tariff_nm', $tariffid, "WHERE `id`='".$userid."'");
        log_register("CATV USER TARIFFNM CHANGE (".$userid.") ".$tariffid);
 }
 
  function catv_UserSetTariff($userid,$tariffid) {
        $userid=vf($userid,3);
        $tariffid=vf($tariffid,3);
        simple_update_field('catv_users', 'tariff', $tariffid, "WHERE `id`='".$userid."'");
        log_register("CATV USER TARIFF CHANGE (".$userid.") ".$tariffid);
 }
  
  function catv_UserEdit($userid,$contract,$realname,$street,$build,$apt,$phone,$discount,$decoder,$inetlink,$notes) {
                $userid=vf($userid,3);
                $contract=mysql_real_escape_string($contract);
                $realname=mysql_real_escape_string($realname);
                $street=mysql_real_escape_string($street);
                $build=mysql_real_escape_string($build);
                $apt=mysql_real_escape_string($apt);
                $phone=mysql_real_escape_string($phone);
                $discount=mysql_real_escape_string($discount);
                $decoder=mysql_real_escape_string($decoder);
                $inetlink=mysql_real_escape_string($inetlink);
                $notes=mysql_real_escape_string($notes);
                $query="UPDATE `catv_users` SET
                        `contract` = '".$contract."',
                        `realname` = '".$realname."',
                        `street` = '".$street."',
                         `build` = '".$build."',
                         `apt` = '".$apt."',
                         `phone` = '".$phone."',
                         `discount` = '".$discount."',
                         `notes` = '".$notes."',
                         `decoder` = '".$decoder."',
                         `inetlink` = '".$inetlink."' 
                         WHERE `id` ='".$userid."' LIMIT 1 ;
                    ";
                nr_query($query);
                log_register("CATV USER EDIT (".$userid.")");
               
           }
  
       
      function catv_CashAdd($userid,$date,$summ,$from_month,$from_year,$to_month,$to_year,$notes) {
          $userid=vf($userid,3);
          $userdata=catv_UserGetData($userid);
          $admin=whoami();
          $oldbalance=$userdata['cash'];
          $newbalance=$oldbalance+$summ;
          $date=mysql_real_escape_string($date);
          $summ=mysql_real_escape_string($summ);
          $from_month=vf($from_month,3);
          $from_year=vf($from_year,3);
          $to_month=vf($to_month,3);
          $to_year=vf($to_year,3);
          $notes=mysql_real_escape_string($notes);
          $query="INSERT INTO `catv_payments` (
                    `id` ,
                    `date` ,
                    `userid` ,
                     `summ` ,
                     `from_month` ,
                     `from_year` ,
                     `to_month` ,
                     `to_year` ,
                     `notes` ,
                     `admin`
                    )
                    VALUES (
                    NULL ,
                    '".$date."',
                    '".$userid."',
                    '".$summ."',
                    '".$from_month."',
                    '".$from_year."',
                    '".$to_month."',
                    '".$to_year."',
                    '".$notes."',
                    '".$admin."'
                    );
              ";
          nr_query($query);
          simple_update_field('catv_users', 'cash', $newbalance, "WHERE `id`='".$userid."'");
          log_register("CATV USER BALANCECHANGE (".$userid.") ON ".$summ);
      }
      
        function catv_CashEdit($paymentid,$date,$summ,$from_month,$from_year,$to_month,$to_year,$notes) {
          $paymentdata=catv_PaymentsGetData($paymentid);
          $userid=$paymentdata['userid'];
          $userdata=catv_UserGetData($userid);
          $admin=whoami();
          $oldbalance=$userdata['cash'];
          $newbalance=$oldbalance+$summ;
          $date=mysql_real_escape_string($date);
          $summ=mysql_real_escape_string($summ);
          $from_month=vf($from_month,3);
          $from_year=vf($from_year,3);
          $to_month=vf($to_month,3);
          $to_year=vf($to_year,3);
          $notes=mysql_real_escape_string($notes);
          simple_update_field('catv_payments', 'date', $date,"WHERE `id`='".$paymentid."' ");
          simple_update_field('catv_payments', 'summ', $summ,"WHERE `id`='".$paymentid."' ");
          simple_update_field('catv_payments', 'from_month', $from_month,"WHERE `id`='".$paymentid."' ");
          simple_update_field('catv_payments', 'from_year', $from_year,"WHERE `id`='".$paymentid."' ");
          simple_update_field('catv_payments', 'to_month', $to_month,"WHERE `id`='".$paymentid."' ");
          simple_update_field('catv_payments', 'to_year', $to_year,"WHERE `id`='".$paymentid."' ");
          simple_update_field('catv_payments', 'notes', $notes,"WHERE `id`='".$paymentid."' ");
          log_register("CATV PAYMENT EDIT (".$paymentid.") ON ".$summ);
      }
      
      function catv_CashPaymentGetData($paymentid) {
          $paymentid=vf($paymentid,3);
          $query="SELECT * from `catv_payments` WHERE `id`='".$paymentid."'";
          $result=simple_query($query);
          return ($result);
      }
      
      function catv_CashPaymentDelete($paymentid) {
          $paymentid=vf($paymentid,3);
          $catv_conf=catv_LoadConfig();
          $paymentdata=catv_CashPaymentGetData($paymentid);
          $paymentuser=$paymentdata['userid'];
          $paymentsumm=$paymentdata['summ'];

          //if payments is not protected
          if (!$catv_conf['PAYMENTS_PROTECT']) {
              $query="DELETE from `catv_payments` WHERE `id`='".$paymentid."';";
              nr_query($query);
              log_register("CATV PAYMENT DELETE ".$paymentid);
          }
          
          //if need go back money
          if ($catv_conf['PAYMENT_DELETE_CORRECT']) {
              $userdata=catv_UserGetData($paymentuser);
              $currentbalance=$userdata['cash'];
              $newbalance=$currentbalance-$paymentsumm;
              simple_update_field('catv_users', 'cash', $newbalance, "WHERE `id`='".$paymentuser."'");
              log_register("CATV SALDO CORECTION (".$paymentuser.") ON -".$paymentsumm);
          }
          
          
      }
      
      function catv_CashAddForm($userid) {
        $catvconf=catv_LoadConfig();
        $userid=vf($userid,3);
        $userdata=catv_UserGetData($userid);
        $usertariff=$userdata['tariff'];
        $tariffdata=catv_TariffGetData($usertariff);
        $tariffprice=$tariffdata['price'];
        $tariffname=$tariffdata['name'];
        $currentbalance=$userdata['cash'];
        $allmonth=months_array();
        $localized_month=array();
        $curyear=curyear();
        $curmonth=date("m");
        $curdatetime=curdatetime();
        
        //rebuild months array
        foreach ($allmonth as $io=>$eachmonth) {
            $localized_month[$io]=rcms_date_localise($eachmonth);
        }
        
        //build cash adding form
        $cashinputs='
            <table width="300" border="0">
            <tr>
            <td  class="row2">'.__('Balance').'</td>
            <td  class="row3">'.$currentbalance.'</td>
            </tr>
            <tr>
            <td  class="row2">'.__('Current tariff').'</td>
            <td  class="row3">'.$tariffname.'</td>
            </tr>
            <tr>
            <td  class="row2">'.__('Fee').'</td>
            <td  class="row3">'.$tariffprice.' '.$catvconf['CURRENCY'].'</td>
            </tr>
            </table>
            <br>
            ';
        $cashinputs.=wf_HiddenInput('createpayment', 'true');
        $cashinputs.='<div>'.wf_TextInput('newpayment', 'Cash', '', true, '5').'</div>';
        $cashinputs.=wf_Selector('from_month', $localized_month, 'From month', $curmonth, false);
        $cashinputs.=wf_YearSelector('from_year', 'From year', true);
        $cashinputs.='<hr>';
        $cashinputs.=__('You can also specify the following options if you wish').'<br>';
        $cashinputs.=wf_TextInput('date', 'Payment date', $curdatetime, true, 20);
        $cashinputs.=wf_Selector('to_month', $localized_month, 'To month', $curmonth, false);
        $cashinputs.=wf_YearSelector('to_year', 'To year', true);
        $cashinputs.=wf_TextInput('notes', 'Notes', '', true, 50);
        $cashinputs.=wf_Submit('Add');
        $cashaddform=wf_Form('', 'POST', $cashinputs, 'glamour', '');
        
        return ($cashaddform);
      }
      
     function catv_CashEditForm($paymentid) {
        $catvconf=catv_LoadConfig();
        $paymentdata=catv_PaymentsGetData($paymentid);
        $userid=$paymentdata['userid'];
        $userdata=catv_UserGetData($userid);
        $usertariff=$userdata['tariff'];
        $tariffdata=catv_TariffGetData($usertariff);
        $tariffprice=$tariffdata['price'];
        $tariffname=$tariffdata['name'];
        $currentbalance=$userdata['cash'];
        $allmonth=months_array();
        $localized_month=array();
        $curyear=curyear();
        $curmonth=date("m");
        $curdatetime=curdatetime();
        
        //rebuild months array
        foreach ($allmonth as $io=>$eachmonth) {
            $localized_month[$io]=rcms_date_localise($eachmonth);
        }
        
        //build cash adding form
        $editcashinputs='
            <table width="300" border="0">
            <tr>
            <td  class="row2">'.__('Balance').'</td>
            <td  class="row3">'.$currentbalance.'</td>
            </tr>
            <tr>
            <td  class="row2">'.__('Current tariff').'</td>
            <td  class="row3">'.$tariffname.'</td>
            </tr>
            <tr>
            <td  class="row2">'.__('Fee').'</td>
            <td  class="row3">'.$tariffprice.' '.$catvconf['CURRENCY'].'</td>
            </tr>
            </table>
            <br>
            ';
        
        $editcashinputs.='<div>'.wf_TextInput('editpayment', 'Cash', $paymentdata['summ'], true, '5').'</div>';
        $editcashinputs.=wf_Selector('editfrom_month', $localized_month, 'From month', $paymentdata['from_month'], false);
        $editcashinputs.=wf_YearSelector('editfrom_year', 'From year', true);
        $editcashinputs.='<hr>';
        $editcashinputs.=__('You can also specify the following options if you wish').'<br>';
        $editcashinputs.=wf_TextInput('editdate', 'Payment date', $paymentdata['date'], true, 20);
        $editcashinputs.=wf_Selector('editto_month', $localized_month, 'To month', $paymentdata['to_month'], false);
        $editcashinputs.=wf_YearSelector('editto_year', 'To year', true);
        $editcashinputs.=wf_TextInput('editnotes', 'Notes', $paymentdata['notes'], true, 50);
        $editcashinputs.=wf_Submit('Edit');
        $casheditform=wf_Form('', 'POST', $editcashinputs, 'glamour', '');
        return ($casheditform);
      }
      
      
      
function catv_TariffChangeAllPlanned() {
    $query="SELECT `id`,`tariff_nm` from `catv_users` WHERE `tariff_nm`!='0'";
    $allchanges=simple_queryall($query);
    if (!empty ($allchanges)) {
        $usercount=0;
        foreach ($allchanges as $io=>$eachuser) {
            $usercount=$usercount+1;
            simple_update_field('catv_users', 'tariff', $eachuser['tariff_nm'], "WHERE `id`='".$eachuser['id']."'");
            simple_update_field('catv_users', 'tariff_nm', '0', "WHERE `id`='".$eachuser['id']."'");
        }
        
       log_register("CATV MASSTARIFFCHANGE ".$usercount);  
    }
}


function catv_FeeChargeCheck($month,$year) {
       $month=vf($month);
       $year=vf($year);
       $query="SELECT `id` from `catv_fees` WHERE `year`='".$year."' AND `month`='".$month."'";
       $previousfees=simple_query($query);
       if (!empty ($previousfees)) {
           return (false);
       } else {
           return (true);
       }
}

function catv_FeeChargeAllUsers($month,$year) {
    $month=mysql_real_escape_string($month);
    $year=vf($year);
       
      $catv_conf=catv_LoadConfig();
      $alltariffprices=catv_TariffGetAllPrices();
      $alluseractivity=catv_ActivityGetLastAll();
      $allusers=catv_UsersGetAll();
      $date=curdatetime();
      $admin=whoami();
             
      
      if (!empty ($allusers)) {
          $usercount=0;   
          // begin user processing
          foreach ($allusers as $io=>$eachuser) {
              $usercount=$usercount+1;
              $monthlyfee=$alltariffprices[$eachuser['tariff']];
              $balance=$eachuser['cash'];
              $discount=$eachuser['discount'];
              if ($catv_conf['FEE_DISCOUNT']) {
                  $finalfee=$monthlyfee-$discount;
              } else {
                  $finalfee=$monthlyfee;
              }
              
              $newbalance=$balance-$finalfee;
              //if protect disconnected users
              if ($catv_conf['FEE_ONLY_ACTIVE']) {
                  //check is user enabled?
                  if ($alluseractivity[$eachuser['id']]!=0) {
              //do the fee
              $querycash="UPDATE `catv_users` SET `cash` = '".$newbalance."' WHERE `id` = '".$eachuser['id']."'; "."\n";
              nr_query($querycash);
              //log the fee
              $queryfee="INSERT INTO `catv_fees` (`id` ,`date` ,`userid` ,`summ` ,`balance` ,`month` ,`year` ,`admin`) VALUES (NULL , '".$date."', '".$eachuser['id']."', '".$finalfee."', '".$balance."', '".$month."', '".$year."', '".$admin."'); "."\n";
              nr_query($queryfee);
                  }
              } else {
                  //if protection disabled - just do it
             $querycash="UPDATE `catv_users` SET `cash` = '".$newbalance."' WHERE `id` = '".$eachuser['id']."'; "."\n";
             nr_query($querycash);
             $queryfee="INSERT INTO `catv_fees` (`id` ,`date` ,`userid` ,`summ` ,`balance` ,`month` ,`year` ,`admin`) VALUES (NULL , '".$date."', '".$eachuser['id']."', '".$finalfee."', '".$balance."', '".$month."', '".$year."', '".$admin."'); "."\n";
             nr_query($queryfee);
              }
          }
          
      
          log_register("CATV MONTHFEECHARGE ".$usercount);
      }
 }
 
 
 function catv_DecoderChange($userid,$decoder) {
     $userid=vf($userid,3);
     $decoder=mysql_real_escape_string($decoder);
     $date=curdatetime();
     
     simple_update_field('catv_users', 'decoder', $decoder, "WHERE `id`='".$userid."'");
     
     $query="
         INSERT INTO `catv_decoders` (
                        `id` ,
                        `date` ,
                        `userid` ,
                        `decoder`
                        )
                        VALUES (
                        NULL , '".$date."', '".$userid."', '".$decoder."'
                        );   ";
     
     nr_query($query);
     log_register("CATV DECODER CHANGE (".$userid.") ".$decoder);
 }
 
 
 function catv_DecoderGetAllByUser($userid) {
       $userid=vf($userid,3);
       $query="SELECT * from `catv_decoders` WHERE `userid`='".$userid."'";
       $result=simple_queryall($query);
       return ($result);
 }
 
 
 function catv_DecoderShowAllChanges($userid) {
     $userid=vf($userid,3);
     $allchanges=catv_DecoderGetAllByUser($userid);
     
     $result='<table width="100%" border="0" class="sortable">';
     $result.='
                    <tr class="row1">
                    <td>'.__('ID').'</td>
                    <td>'.__('Date').'</td>
                    <td>'.__('Decoder').'</td>
                    </tr>
                    ';
     if (!empty ($allchanges)) {
            foreach ($allchanges as $io=>$eachchange) {
                $result.='
                    <tr class="row3">
                    <td>'.$eachchange['id'].'</td>
                    <td>'.$eachchange['date'].'</td>
                    <td>'.$eachchange['decoder'].'</td>
                    </tr>
                    ';
            }
     }
     $result.='</table>';
     show_window(__('Previous decoder changes'), $result);
 }
  
 
      //show activity per year
        function catv_ActivityShowAll($userid) {
        $userid=vf($userid,3);
        $alluseractivity=catv_ActivityGetAllByUser($userid);
        
        $acttable='<table width="100%" border="0" class="sortable">';
        $acttable.='
                    <tr class="row1">
                    <td>'.__('ID').'</td>
                    <td>'.__('Date').'</td>
                    <td>'.__('Connected').'</td>
                    <td>'.__('Admin').'</td>
                    </tr>
                    ';
        
        if (!empty ($alluseractivity)) {
            foreach ($alluseractivity as $io=>$eachact) {
                $acttable.='
                    <tr class="row3">
                    <td>'.$eachact['id'].'</td>
                    <td>'.$eachact['date'].'</td>
                    <td>'.web_bool_led($eachact['state']).'</td>
                    <td>'.$eachact['admin'].'</td>
                    </tr>
                    ';
            }
        }
        $acttable.='</table>';
         show_window(__('All activity changes'), $acttable);
        }
        
/////////////// CaTV reports        
        function catv_ReportsShowList() {
            $replink='?module=catv&action=reports&showreport=';
            $reportslist='
                <a href="'.$replink.'current_debtors" class="ubButton">'.__('Current debtors').'</a> 
                  <a href="'.$replink.'current_debtors&printable=true" class="ubButton"><img src="skins/printer_small.gif" border="0"></a> <br><br>
                <a href="'.$replink.'period_debtors" class="ubButton">'.__('Debtors for the period').'</a> 
                    <a href="'.$replink.'period_debtors&printable=true" class="ubButton"><img src="skins/printer_small.gif" border="0"></a> <br><br>
                <a href="'.$replink.'signup" class="ubButton">'.__('Signup report').'</a> 
                    <a href="'.$replink.'signup&printable=true" class="ubButton"><img src="skins/printer_small.gif" border="0"></a> <br><br>
                <a href="'.$replink.'overpayments" class="ubButton">'.__('The amount of overpayments for the period').'</a> 
                    <a href="'.$replink.'overpayments&printable=true" class="ubButton"><img src="skins/printer_small.gif" border="0"></a> <br><br>
                <a href="'.$replink.'debtperiod" class="ubButton">'.__('The amount of debt for the period').'</a> 
                    <a href="'.$replink.'debtperiod&printable=true" class="ubButton"><img src="skins/printer_small.gif" border="0"></a> <br><br>
                ';
            show_window(__('Available reports'),$reportslist);
        }
        
        function catv_ReportDebtors() {
            if (isset($_GET['printable'])) {
                $printable=true;
            } else {
                $printable=false;
            }
            
            $alluserstates=catv_ActivityGetLastAll();
            $query="SELECT * from `catv_users` WHERE `cash`<0 ORDER BY `street` ";
            $alldebtors=simple_queryall($query);
       
        if ($printable==true) {
         $tstyle='
        <style type="text/css">
        table.printrm {
	border-width: 1px;
	border-spacing: 2px;
	border-style: outset;
	border-color: gray;
	border-collapse: separate;
	background-color: white;
        }
        table.printrm th {
	border-width: 1px;
	padding: 1px;
	border-style: dashed;
	border-color: gray;
	background-color: white;
	-moz-border-radius: ;
        }
        table.printrm td {
	border-width: 1px;
	padding: 1px;
	border-style: dashed;
	border-color: gray;
	background-color: white;
	-moz-border-radius: ;
        }
        </style>
                ';
         $tclass='printrm';
        } else {
            $tstyle='';
            $tclass='sortable';
        }
            
            
            $realdebtors=$tstyle.'<table width="100%" class="'.$tclass.'" border="0">';
            $realdebtors.='
                            <tr class="row1">
                            <td>'.__('ID').'</td>
                            <td>'.__('Contract').'</td>
                            <td>'.__('Real name').'</td>
                            <td>'.__('Street').'</td>
                            <td>'.__('Build').'</td>
                            <td>'.__('Apartment').'</td>
                            <td>'.__('Phone').'</td>
                            <td>'.__('Tariff').'</td>
                            <td>'.__('Debt').'</td>
                            </tr>
                            ';
            if (!empty ($alldebtors)) {
                foreach ($alldebtors as $io=>$eachdebt) {
                    if ($alluserstates[$eachdebt['id']]==1) {
                        $realdebtors.='
                            <tr class="row3">
                            <td>'.$eachdebt['id'].'</td>
                            <td>'.$eachdebt['contract'].'</td>
                            <td>'.$eachdebt['realname'].'</td>
                            <td>'.$eachdebt['street'].'</td>
                            <td>'.$eachdebt['build'].'</td>
                            <td>'.$eachdebt['apt'].'</td>
                            <td>'.$eachdebt['phone'].'</td>
                            <td>'.$eachdebt['tariff'].'</td>
                            <td>'.$eachdebt['cash'].'</td>
                            </tr>
                            ';
                    }
                }
            }
            $realdebtors.='</table>';
            
            if ($printable==false) {
            show_window(__('Current debtors'),$realdebtors);
            } else {
                print("<h2>".__('Current debtors')."</h2>".$realdebtors);
                die();
            }
            
        }
        
        
           
?>