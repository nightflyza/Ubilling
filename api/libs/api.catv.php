<?php

  function catv_GlobalControlsShow() {
      $controls= wf_Link("?module=catv&action=showusers",__('Users list'),false,'ubButton');
      $controls.= wf_Link("?module=catv&action=userreg",__('Users registration'),false,'ubButton');
      $controls.= wf_Link("?module=catv&action=tariffs",__('Tariffs'),false,'ubButton');
      $controls.= wf_Link("?module=catv&action=fees",__('Сharge monthly fee'),false,'ubButton');
      $controls.= wf_Link("?module=catv_banksta",__('Statements'),false,'ubButton');
      $controls.= wf_Link("?module=catv&action=reports",__('Reports'),false,'ubButton');
      
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
  
  function catv_UserDelete($userid) {
      $userid=vf($userid,3);
      $query="DELETE from `catv_users` WHERE `id`='".$userid."'";
      nr_query($query);
      log_register("CATV USER DELETE (".$userid.") ");
  }
  
  function catv_UserRegisterForm() {
      $alltariffs=catv_TariffGetAllNames();
      $inputs=wf_HiddenInput('realyregister', 'true');
      $inputs.=wf_TextInput('newusercontract', __('Contract'), '', true, '');
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
      $usercount=sizeof($allusers);
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
      $result.='<strong>'.__('Total').': '.$usercount.'</strong>';
      
      show_window(__('Available users'),$result);
  }
  
  function catv_UsersShowList_hp() {
      
      $jq_dt='
          <script type="text/javascript" charset="utf-8">
                
		$(document).ready(function() {
		$(\'#catvonlineusershp\').dataTable( {
 	       "oLanguage": {
			"sLengthMenu": "'.__('Show').' _MENU_",
			"sZeroRecords": "'.__('Nothing found').'",
			"sInfo": "'.__('Showing').' _START_ '.__('to').' _END_ '.__('of').' _TOTAL_ '.__('users').'",
			"sInfoEmpty": "'.__('Showing').' 0 '.__('to').' 0 '.__('of').' 0 '.__('users').'",
			"sInfoFiltered": "('.__('Filtered').' '.__('from').' _MAX_ '.__('Total').')",
                        "sSearch":       "'.__('Search').'",
                        "sProcessing":   "'.__('Processing').'...",
                        "sProcessing":   "' . __('Processing') . '...",
                        "oPaginate": {
                        "sFirst": "'.__('First').'",
                        "sPrevious": "'.__('Previous').'",
                        "sNext": "'.__('Next').'",
                        "sLast": "'.__('Last').'"
                    },
                            
		},
           
                "aoColumns": [
                null,
                null,
                null,
                null,
                null,
                null,
                null
            ],      
         
        "bPaginate": true,
        "bLengthChange": true,
        "bFilter": true,
        "bSort": true,
        "bInfo": true,
        "bAutoWidth": false,
        "bProcessing": true,
        "bStateSave": false,
        "iDisplayLength": 50,
        "sAjaxSource": \'?module=catv&action=showusers&ajax\',
	"bDeferRender": true,
        "bJQueryUI": true

                } );
		} );
		</script>

          ';
      $result=' '.$jq_dt.'
          <table width="100%" id="catvonlineusershp" class="sortable display compact">
          <thead>   
                <tr class="row1">
                  <td>'.__('ID').'</td>
                  <td>'.__('Real name').'</td>
                  <td>'.__('Full address').'</td>
                  <td>'.__('Tariff').'</td>
                  <td>'.__('Cash').'</td>
                  <td>'.__('Connected').'</td>
                  <td>'.__('Actions').'</td>
                </tr>
          </thead>         
          ';
     
      
      $result.='</table>';
      
      
      show_window(__('Available users'),$result);
  }
  
  function catv_AjaxOnlineDataSource() {
      $tariffnames=catv_TariffGetAllNames();
      $query="SELECT * from `catv_users`";
      $allusers=simple_queryall($query);
      $totalusers=sizeof($allusers);  
      $allstates=catv_ActivityGetLastAll();
      $ucount=0;
      
       $result='{';
       $result.='
       "aaData": [
         ';
      
     if (!empty ($allusers)) {
          foreach ($allusers as $io=>$eachuser) {
              $ucount++;
              
              if ($ucount<$totalusers) {
                     $ending=',';
                } else {
                     $ending='';
                }   
     $clearstreet=trim($eachuser['street']);
     $clearstreet=  str_replace("'", '`', $clearstreet);
     $clearstreet=  mysql_real_escape_string($clearstreet);
     
     $clearrealname=trim($eachuser['realname']);
     $clearrealname=  str_replace("'", '`', $clearrealname);
     $clearrealname=  mysql_real_escape_string($clearrealname);
     
     $act='<img src=skins/icon_active.gif>'.__('Yes');
     //activity
     if (!catv_ActivityCheck($eachuser['id'], $allstates)) {
     $act='<img src=skins/icon_inactive.gif>'.__('No');
     }
     
     //apt processing
     if (($eachuser['apt']=='') OR ($eachuser['apt']=='0')) {
         $aptdata='';
     } else {
         $aptdata='/'.$eachuser['apt'];
     }
     
     
            $result.='
         [
         "'.$eachuser['id'].'",
         "'.$clearrealname.'",
         "'.$clearstreet.' '.$eachuser['build'].''.$aptdata.'",
         "'.@$tariffnames[$eachuser['tariff']].'",
         "'.$eachuser['cash'].'",
         "'.$act.'",
         "<a href=?module=catv_profile&userid='.$eachuser['id'].'><img src=skins/icon_user.gif title='.__('Profile').' border=0></a> <a href=?module=catv_addcash&userid='.$eachuser['id'].'><img src=skins/icon_dollar.gif title='.__('Cash').' border=0></a> <a href=?module=catv_stats&userid='.$eachuser['id'].'><img src=skins/icon_stats.gif title='.__('Stats').' border=0></a>"
         ]'.$ending.'
        ';  
     
          }
          
           $result.='
    
    ]
    }
        ';
      }
      
      print($result);
      die();
      
  }
  
  //used in profile
  function catv_UsersGetFullAddressList() {
      $query="SELECT `id`,`street`,`build`,`apt` from `catv_users`";
      $allusers=simple_queryall($query);
      $result=array();
      $catvconf=  rcms_parse_ini_file(CONFIG_PATH."catv.ini");
      if (!empty ($allusers)) {
          foreach ($allusers as $io=>$eachuser) {
              //again zero tolerance
              if ($catvconf['ZERO_TOLERANCE']) {
                  if ($eachuser['apt']=='') {
                      $useraddress=$eachuser['street'].' '.$eachuser['build'].'';
                  } else {
                      $useraddress=$eachuser['street'].' '.$eachuser['build'].'/'.$eachuser['apt'];
                  }
              } else {
                  $useraddress=$eachuser['street'].' '.$eachuser['build'].'/'.$eachuser['apt'];
              }
              
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
          <td>
           '.  wf_JSAlert('?module=catv_useredit&deleteuserid='.$userid, wf_img('skins/annihilation.gif', __('Annihilation')), 'Are you serious').'</a>
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
    
    function catv_UserShowAllpayments($userid) {
        $catvconf=catv_LoadConfig();
        $alluserpayments=catv_PaymentsGetAllByUser($userid);
        $montharray_wz=months_array_wz();
        
        $cells=  wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Date'));
        $cells.= wf_TableCell(__('Cash'));
        $cells.= wf_TableCell(__('From month'));
        $cells.= wf_TableCell(__('From year'));
        $cells.= wf_TableCell(__('Notes'));
        $cells.= wf_TableCell(__('Admin'));
        $cells.= wf_TableCell(__('Actions'));
        $rows= wf_TableRow($cells, 'row1');
        
        if (!empty($alluserpayments)) {
            foreach ($alluserpayments as $io=>$eachpayrow) {
                
                   //check is payments protected?
                    if ($catvconf['PAYMENTS_PROTECT']) {
                       $paycontrols='';
                   } else {
                       $paycontrols='
                           '.  wf_JSAlert('?module=catv_addcash&userid='.$eachpayrow['userid'].'&deletepayment='.$eachpayrow['id'], web_delete_icon(), 'Removing this may lead to irreparable results').'
                           '.  wf_JSAlert('?module=catv_addcash&userid='.$eachpayrow['userid'].'&editpayment='.$eachpayrow['id'], web_edit_icon(), 'Are you serious').'
                           ';
                   }
                   
                   // month locale
                   $transmonth=$montharray_wz[$eachpayrow['from_month']];
                   $transmonth=  rcms_date_localise($transmonth);
                   
                $cells=  wf_TableCell($eachpayrow['id']);
                $cells.= wf_TableCell($eachpayrow['date']);
                $cells.= wf_TableCell($eachpayrow['summ']);
                $cells.= wf_TableCell($transmonth);
                $cells.= wf_TableCell($eachpayrow['from_year']);
                $cells.= wf_TableCell($eachpayrow['notes']);
                $cells.= wf_TableCell($eachpayrow['admin']);
                $cells.= wf_TableCell($paycontrols);
                $rows.= wf_TableRow($cells, 'row3');   
                
            }
        }
        
        $result=  wf_TableBody($rows, '100%', '0', 'sortable');
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
            $montharray_wz=  months_array_wz();
            $actlog=array();
            
            
            $payments_table=  wf_tag('h2').__('Previous payments').  wf_tag('h2', true);
            $payments_table.=catv_UserShowAllpayments($userid);
            
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
          log_register("CATV USER BALANCECHANGE ((".$userid.")) ON ".$summ);
      }
      
       function catv_CashMock($userid,$date,$summ,$from_month,$from_year,$to_month,$to_year,$notes) {
          $userid=vf($userid,3);
          $userdata=catv_UserGetData($userid);
          $admin=whoami();
          $oldbalance=$userdata['cash'];
          $newbalance=$oldbalance;
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
       
          log_register("CATV USER BALANCEMOCK ((".$userid.")) ON ".$summ);
      }
      
       function catv_CashSet($userid,$date,$summ,$from_month,$from_year,$to_month,$to_year,$notes) {
          $userid=vf($userid,3);
          $userdata=catv_UserGetData($userid);
          $admin=whoami();
          $oldbalance=$userdata['cash'];
          $newbalance=$summ;
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
          log_register("CATV USER BALANCESET ((".$userid.")) ON ".$summ);
      }
      
      function catv_CashCorrect($userid,$date,$summ,$from_month,$from_year,$to_month,$to_year,$notes) {
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
          $query="INSERT INTO `catv_paymentscorr` (
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
          log_register("CATV USER BALANCECORR ((".$userid.")) ON ".$summ);
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
        $alladdress=  catv_UsersGetFullAddressList();
        $realname=  catv_UserGetData($userid);
        $realname=$realname['realname'];
        
        //rebuild months array
        foreach ($allmonth as $io=>$eachmonth) {
            $localized_month[$io]=rcms_date_localise($eachmonth);
        }
        
        //build cash adding form
        $cells=  wf_TableCell(__('Full address'),'','row2');
        $cells.=wf_TableCell(@$alladdress[$userid], '', 'row3');
        $rows=  wf_TableRow($cells);
        
        $cells=  wf_TableCell(__('Real name'),'','row2');
        $cells.=wf_TableCell($realname, '', 'row3');
        $rows.=  wf_TableRow($cells);
        
        $cells=  wf_TableCell(__('Balance'),'','row2');
        $cells.=wf_TableCell($currentbalance, '', 'row3');
        $rows.=  wf_TableRow($cells);
        
        $cells=  wf_TableCell(__('Current tariff'),'','row2');
        $cells.=wf_TableCell($tariffname, '', 'row3');
        $rows.=  wf_TableRow($cells);
        
        $cells=  wf_TableCell(__('Fee'),'','row2');
        $cells.=wf_TableCell($tariffprice.' '.$catvconf['CURRENCY'], '', 'row3');
        $rows.=  wf_TableRow($cells);
        
        $cashinputs=wf_TableBody($rows, '650', '0', '');
        
        $cashinputs.=wf_HiddenInput('createpayment', 'true');
        $cashinputs.=wf_tag('div').wf_TextInput('newpayment', 'Cash', '', true, '5').  wf_tag('div',true);
        $cashinputs.=wf_Selector('from_month', $localized_month, 'From month', $curmonth, false);
        $cashinputs.=wf_YearSelector('from_year', 'From year', true);
          //cash operation type
        $cashinputs.=wf_RadioInput('optype', __('Add cash'), 'add', false, true);
        $cashinputs.=wf_RadioInput('optype', __('Correct saldo'), 'corr', false, false);
        $cashinputs.=wf_RadioInput('optype', __('Mock payment'), 'mock', false, false);
        $cashinputs.=wf_RadioInput('optype', __('Set cash'), 'set', false, false);
        $cashinputs.=wf_delimiter();
        
        $cashinputs.=wf_tag('hr');
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
            $reportslist=  wf_Link($replink.'current_debtors', __('Current debtors'), false, 'ubButton');
            $reportslist.=wf_delimiter();
            $reportslist.= wf_Link($replink.'current_debtors&printable=true', wf_img('skins/printer_small.gif').' '.__('Current debtors for delivery'), false, 'ubButton');
            $reportslist.=wf_delimiter();
            $reportslist.= wf_Link($replink.'current_debtorsaddr', wf_img('skins/printer_small.gif').' '.__('Current debtors for delivery by address'), false, 'ubButton');
            $reportslist.=wf_delimiter();
            $reportslist.= wf_Link($replink.'current_debtorsstreet', wf_img('skins/printer_small.gif').' '.__('Current debtors for delivery by streets'), false, 'ubButton');
            $reportslist.=wf_delimiter();
            $reportslist.= wf_Link($replink.'finance', __('Finance report'), false, 'ubButton');
            $reportslist.=wf_delimiter();
            $reportslist.= wf_Link($replink.'exportcsv', wf_img('skins/excel.gif').' '. __('Export userbase'), false, 'ubButton');
            
           
            show_window(__('Available reports'),$reportslist);
        }
        
       function catv_AjaxBuildSelector($street) {
           $street=  mysql_real_escape_string($street);
           $query="SELECT DISTINCT `build` from `catv_users` WHERE `street`='".$street."'";
           $allbuilds=  simple_queryall($query);
           $result='<select name="buildbox" id="buildbox">';
            if (!empty($allbuilds)) {
                foreach ($allbuilds as $io=>$each) {
                    $result.='<option value="'.$each['build'].'">'.$each['build'].'</option>';
                }
               }
           $result.='</select>';
           $result.='<label for="buildbox"> '.__('Build').' </label>';   
           if (!empty($allbuilds)) {
               $result.=' '.wf_Submit('Print');
           }
           
           die($result);
       }
       
        function catv_AjaxSubmit() {
           $result=' '.wf_Submit('Print');
           die($result);
       }
        
       function catv_StreetSelector($url='') {
            $query="SELECT DISTINCT `street` from `catv_users`";
            $allstreets=  simple_queryall($query);
            $streets=array();
            $result='<select name="streetbox" id="streetbox" onchange="var valuest = document.getElementById(\'streetbox\').value; goajax(\'?module=catv&action=reports&showreport='.$url.'&ajaxbuild=\'+valuest,\'dbuildbox\');">';
            $result.='<option value="NULLSTREET" >-</option>';
            
            if (!empty($allstreets)) {
                foreach ($allstreets as $io=>$each) {
                    $result.='<option value="'.$each['street'].'" >'.$each['street'].'</option>';
                }
            }
            
            $result.='</select>';
            $result.='<label for="streetbox"> '.__('Street').' </label>';

            return ($result);
       } 
        
       function catv_ReportDebtorsAddrPrintable($street,$build) {
           $street=  mysql_real_escape_string($street);
           $build=  mysql_real_escape_string($build);
           
           $alluserstates=catv_ActivityGetLastAll();
           $query="SELECT * from `catv_users` WHERE `cash`<0 AND `street`='".$street."' AND `build`='".$build."' ORDER BY `street` ";
           $alldebtors=simple_queryall($query);
           $alltariffs=  catv_TariffGetAllNames();
           $print_template=  file_get_contents(CONFIG_PATH."catv_debtors.tpl");
           
       
       
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
            
         $realdebtors=$tstyle.'<table width="100%" class="'.$tclass.'" border="0">';
            
            if (!empty ($alldebtors)) {
                foreach ($alldebtors as $io=>$eachdebt) {
                    if ($alluserstates[$eachdebt['id']]==1) {
                            //printable templating
                            $rowtemplate=$print_template;
                            
                            $rowtemplate=str_ireplace('{REALNAME}', $eachdebt['realname'], $rowtemplate);
                            $rowtemplate=str_ireplace('{STREET}', $eachdebt['street'], $rowtemplate);
                            $rowtemplate=str_ireplace('{BUILD}', $eachdebt['build'], $rowtemplate);
                            $rowtemplate=str_ireplace('{APT}', $eachdebt['apt'], $rowtemplate);
                            $rowtemplate=str_ireplace('{DEBT}', $eachdebt['cash'], $rowtemplate);
                            $rowtemplate=str_ireplace('{CURDATE}', curdate(), $rowtemplate);
                            $rowtemplate=str_ireplace('{PAYDAY}', (date("Y-m-").'01'), $rowtemplate);
                            
                            $realdebtors.=$rowtemplate;

                    }
                }
            }
            
            $realdebtors.='</table>';

            print("<h2>".__('Current debtors')."</h2>".$realdebtors);
            die();
        }
        
        function catv_ReportDebtorsStreetPrintable($street) {
           $street=  mysql_real_escape_string($street);
           
           $alluserstates=catv_ActivityGetLastAll();
           $query="SELECT * from `catv_users` WHERE `cash`<0 AND `street`='".$street."' ORDER BY `street` ";
           $alldebtors=simple_queryall($query);
           $alltariffs=  catv_TariffGetAllNames();
           $print_template=  file_get_contents(CONFIG_PATH."catv_debtors.tpl");
           
       
       
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
            
         $realdebtors=$tstyle.'<table width="100%" class="'.$tclass.'" border="0">';
            
            if (!empty ($alldebtors)) {
                foreach ($alldebtors as $io=>$eachdebt) {
                    if ($alluserstates[$eachdebt['id']]==1) {
                            //printable templating
                            $rowtemplate=$print_template;
                            
                            $rowtemplate=str_ireplace('{REALNAME}', $eachdebt['realname'], $rowtemplate);
                            $rowtemplate=str_ireplace('{STREET}', $eachdebt['street'], $rowtemplate);
                            $rowtemplate=str_ireplace('{BUILD}', $eachdebt['build'], $rowtemplate);
                            $rowtemplate=str_ireplace('{APT}', $eachdebt['apt'], $rowtemplate);
                            $rowtemplate=str_ireplace('{DEBT}', $eachdebt['cash'], $rowtemplate);
                            $rowtemplate=str_ireplace('{CURDATE}', curdate(), $rowtemplate);
                            $rowtemplate=str_ireplace('{PAYDAY}', (date("Y-m-").'01'), $rowtemplate);
                            
                            $realdebtors.=$rowtemplate;

                    }
                }
            }
            
            $realdebtors.='</table>';

            print("<h2>".__('Current debtors')."</h2>".$realdebtors);
            die();
        }
        
           function catv_ReportDebtorsAddr() {
           if (wf_CheckPost(array('streetbox','buildbox'))) {
               catv_ReportDebtorsAddrPrintable($_POST['streetbox'], $_POST['buildbox']);
               
           } else {
           $inputs=  zb_AjaxLoader();
           $inputs.=catv_StreetSelector('current_debtorsaddr');
           $inputs.=wf_tag('span', false, '', 'id="dbuildbox"');
           $inputs.=wf_tag('span', true);
           $inputs.=wf_tag('span', false, '', 'id="datpbox"');
           $inputs.=wf_tag('span', true);
           $form=  wf_Form("", "POST", $inputs, 'glamour');
           show_window(__('Current debtors for delivery by address'),$form);
           }
        }
        
        function catv_ReportDebtorsStreet() {
           if (wf_CheckPost(array('streetbox'))) {
               catv_ReportDebtorsStreetPrintable($_POST['streetbox']);
               
           } else {
           $inputs=  zb_AjaxLoader();
           $inputs.=catv_StreetSelector('current_debtorsstreet');
           $inputs.=wf_tag('span', false, '', 'id="dbuildbox"');
           $inputs.=wf_tag('span', true);
           $inputs.=wf_tag('span', false, '', 'id="datpbox"');
           $inputs.=wf_tag('span', true);
           $form=  wf_Form("", "POST", $inputs, 'glamour');
           show_window(__('Current debtors for delivery by streets'),$form);
           }
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
            $alltariffs=  catv_TariffGetAllNames();
            $print_template=  file_get_contents(CONFIG_PATH."catv_debtors.tpl");
           
       
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
            
            
            if ($printable) {
                $realdebtors=$tstyle.'<table width="100%" class="'.$tclass.'" border="0">';
                
            } else {
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
            }
            
            if (!empty ($alldebtors)) {
                foreach ($alldebtors as $io=>$eachdebt) {
                    if ($alluserstates[$eachdebt['id']]==1) {
                        
                        if (!$printable) {
                            $profilelink=wf_Link('?module=catv_profile&userid='.$eachdebt['id'], wf_img('skins/icon_user.gif', __('Profile')), false);
                        } else {
                            $profilelink='';
                        }
                        
                        if ($printable) {
                            //printable templating
                            $rowtemplate=$print_template;
                            
                            $rowtemplate=str_ireplace('{REALNAME}', $eachdebt['realname'], $rowtemplate);
                            $rowtemplate=str_ireplace('{STREET}', $eachdebt['street'], $rowtemplate);
                            $rowtemplate=str_ireplace('{BUILD}', $eachdebt['build'], $rowtemplate);
                            $rowtemplate=str_ireplace('{APT}', $eachdebt['apt'], $rowtemplate);
                            $rowtemplate=str_ireplace('{DEBT}', $eachdebt['cash'], $rowtemplate);
                            $rowtemplate=str_ireplace('{CURDATE}', curdate(), $rowtemplate);
                            $rowtemplate=str_ireplace('{PAYDAY}', (date("Y-m-").'01'), $rowtemplate);
                            
                            $realdebtors.=$rowtemplate;
                            
                        } else {
                        $realdebtors.='
                            <tr class="row3">
                            <td>'.$eachdebt['id'].' '. $profilelink.'</td>
                            <td>'.$eachdebt['contract'].'</td>
                            <td>'.$eachdebt['realname'].'</td>
                            <td>'.$eachdebt['street'].'</td>
                            <td>'.$eachdebt['build'].'</td>
                            <td>'.$eachdebt['apt'].'</td>
                            <td>'.$eachdebt['phone'].'</td>
                            <td>'.@$alltariffs[$eachdebt['tariff']].'</td>
                            <td>'.$eachdebt['cash'].'</td>
                            </tr>
                            ';
                        }
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
         
         
 //      
 // statements api
 //

  function catvbs_UploadFormBody($action,$method,$inputs,$class='') {
     if ($class!='') {
        $form_class=' class="'.$class.'" ';
    } else {
        $form_class='';
    }
    $form='
        <form action="'.$action.'" method="'.$method.'" '.$form_class.' enctype="multipart/form-data">
        '.$inputs.'
        </form>
          <div style="clear:both;"></div>
        ';
    return ($form);
    }
    
    function catvbs_UploadFileForm() {
    $uploadinputs=wf_HiddenInput('upload','true');
    $uploadinputs.=__('File').' <input id="fileselector" type="file" name="filename" size="10" /><br>';
    $uploadinputs.=wf_Submit('Upload');
    $uploadform=bs_UploadFormBody('', 'POST', $uploadinputs, 'glamour');
    return ($uploadform);
    }
    
 function catvbs_UploadFile() {
   $timestamp=time();
   //путь сохранения
   $uploaddir = DATA_PATH.'banksta/';
   //белый лист расширений
   $allowedExtensions = array("txt"); 
   //по умолчанию надеемся на худшее
   $result=false;
   
   //проверяем точно ли выписку нам подсовывают
   foreach ($_FILES as $file) {
    if ($file['tmp_name'] > '') {
      if (!in_array(end(explode(".",strtolower($file['name']))),$allowedExtensions)) {
       $errormessage='Wrong file type';
       die($errormessage);
      }
     }
   } 
 
   $filename=vf($_FILES['filename']['name']);
          $uploadfile = $uploaddir . $filename;   
           if (move_uploaded_file($_FILES['filename']['tmp_name'], $uploadfile)) {
               $result=$filename;
           }
           
  return ($result);
}

function catvbs_FilePush($filename,$rawdata) {
    $filename=vf($filename);
    $rawdata=mysql_real_escape_string($rawdata);
    $query="INSERT INTO `catv_bankstaraw` (
            `id` ,
            `filename` ,
            `rawdata`
            )
            VALUES (
            NULL , '".$filename."', '".$rawdata."'
            );
            ";
    nr_query($query);
    $lastid=  simple_get_lastid('catv_bankstaraw');
    return ($lastid);
}

function catvbs_CheckHash($hash) {
    $hash=mysql_real_escape_string($hash);
    $query="SELECT COUNT(`id`) from `catv_bankstaparsed` WHERE `hash`='".$hash."'";
    $rowcount=simple_query($query);
    $rowcount=$rowcount['COUNT(`id`)'];
    if ($rowcount>0) {
        return (false);
    } else {
        return(true);
    }
}


 function catvbs_cu_IsParent($login,$allparentusers) {
     $login=mysql_real_escape_string($login);
     if (isset($allparentusers[$login])) {
        return (true);
    } else {
        return (false);
    }
 }

function catvbs_ParseRaw($rawid) {
    $alterconf=rcms_parse_ini_file(CONFIG_PATH."catv.ini");
    $bs_options=$alterconf['BS_OPTIONS'];
    //delimiter,data,name,addr,summ
    $options=explode(',',$bs_options);
    //magic numbers, khe khe
    $data_offset=$options[1];
    $realname_offset=$options[2];
    $address_offset=$options[3];
    $summ_offset=$options[4];
    $delimiter=$options[0];
    
    $date=  curdatetime();
    $rawdata_q="SELECT `rawdata` from `catv_bankstaraw` WHERE `id`='".$rawid."'";
    $rawdata=simple_query($rawdata_q);
    $rawdata=$rawdata['rawdata'];
    $hash=md5($rawdata);
    
    $splitrows=  explodeRows($rawdata);
    if (sizeof($splitrows)>$data_offset) {
        $i=0;
        
        foreach ($splitrows as $eachrow) {
           if ($i>=$data_offset) { 
           $rowsplit=explode($delimiter,$eachrow);
           //filter ending
           if (isset($rowsplit[$summ_offset])) {
               $realname=trim(strtolower_utf8($rowsplit[$realname_offset]));
               $address=trim(strtolower_utf8($rowsplit[$address_offset]));
               $address=  mysql_real_escape_string($address);
               $realname=str_replace('  ', '', $realname);
               $summ=trim($rowsplit[$summ_offset]);
               $realname=mysql_real_escape_string($realname);
               $address=str_replace('  ', '', $address);
               $query="INSERT INTO `catv_bankstaparsed` (
                        `id` ,
                        `hash` ,
                        `date` ,
                        `row` ,
                        `realname` ,
                        `address` ,
                        `summ` ,
                        `state` ,
                        `login`
                        )
                        VALUES (
                        NULL ,
                        '".$hash."',
                        '".$date."',
                        '".$i."',
                        '".$realname."',
                        '".$address."',
                        '".$summ."',
                        '0',
                        ''
                        ); 
                        ";

               nr_query($query);
           }
           
             }
           $i++;
        }
    }
  
}

function catvbs_DeleteBanksta($hash) {
    $hash=vf($hash);
    $query="DELETE from `catv_bankstaparsed` WHERE `hash`='".$hash."'";
    nr_query($query);
    log_register("CATV_BANKSTA DELETE ".$hash);
}


function catvbs_CheckProcessed($hash) {
    $hash=vf($hash);
    $query="SELECT COUNT(`id`) from `catv_bankstaparsed` WHERE `hash`='".$hash."' and `state`='0'"; 
    $notprocessed=simple_query($query);
    if (($notprocessed['COUNT(`id`)'])!=0) {
        $result=web_bool_led(false).' <sup>('.$notprocessed['COUNT(`id`)'].')</sup>';
    } else {
        $result=web_bool_led(true).' <sup>('.$notprocessed['COUNT(`id`)'].')</sup>';
    }
    return ($result);
}

function catvbs_ShowAllStatements() {
    $query="SELECT DISTINCT `hash`,`date` from `catv_bankstaparsed` ORDER BY `date` DESC";
    $allstatements=simple_queryall($query);
    if (!empty($allstatements)) {
       $tablecells=wf_TableCell(__('Date'));
       $tablecells.=wf_TableCell(__('Payments count'));
       $tablecells.=wf_TableCell(__('Processed'));
       $tablecells.=wf_TableCell(__('Actions'));
       $tablerows=  wf_TableRow($tablecells,'row1');
       foreach ($allstatements as $io=>$eachstatement) {
           $statementlink=wf_Link("?module=catv_banksta&showhash=".$eachstatement['hash'], $eachstatement['date']);
           $rowcount_q="SELECT COUNT(`id`) from `catv_bankstaparsed` WHERE `hash`='".$eachstatement['hash']."'";
           $rowcount=  simple_query($rowcount_q);
           $rowcount=$rowcount['COUNT(`id`)'];
           $tablecells=wf_TableCell($statementlink);
           $tablecells.=wf_TableCell($rowcount);
           $tablecells.=wf_TableCell(catvbs_CheckProcessed($eachstatement['hash']));
           $tablecells.=wf_TableCell(wf_JSAlert('?module=catv_banksta&deletehash='.$eachstatement['hash'], web_delete_icon(), 'Removing this may lead to irreparable results'));
           $tablerows.=wf_TableRow($tablecells, 'row3');
       }
       $result=wf_TableBody($tablerows, '100%', '0', 'sortable');
    } else {
        $result=__('Any statements uploaded');
    }
    
    show_window(__('Previously uploaded statements'),$result);
}

function catvbs_LoginProposalForm($id,$login='') {
    $id=vf($id,3);
    if (!empty ($login)) {
        $loginform=web_bool_led(true).'<a href="?module=catv_profile&userid='.$login.'">'.web_profile_icon().' '.$login.'</a>';
    } else {
        $loginform=  web_bool_led(false);
    }
    return ($loginform);
}

function catvbs_NameEditForm($id,$name='') {
    $id=vf($id,3);
    $inputs=wf_HiddenInput('editrowid',$id);
    $inputs.=wf_TextInput('editrealname', '', $name, false, '10');
    $inputs.=wf_Submit(__('Save'));
    $form=  wf_Form("", 'POST', $inputs, '');
    return ($form);
}


function catvbs_AddressEditForm($id,$address='') {
    $id=vf($id,3);
    $inputs=wf_HiddenInput('editrowid',$id);
    $inputs.=wf_TextInput('editaddress', '', $address, false, '20');
    $inputs.=wf_Submit(__('Save'));
    $form=  wf_Form("", 'POST', $inputs, '');
    return ($form);
}


function catvbs_SearchCheckArr($alluseraddress,$allrealnames) {
    $checkarr=array();
        foreach ($alluseraddress as $addrlogin=>$eachaddr) {
            $splitname=explode(' ',$allrealnames[$addrlogin]);
            $checkarr[$addrlogin]['address']=$eachaddr;
            $checkarr[$addrlogin]['realname']=$splitname[0];
        }
    return ($checkarr);
}

function catvbs_SearchLoginByAddresspart($queryaddress,$queryname,$checkarr) {
        $queryaddress=mysql_real_escape_string($queryaddress);
        $queryaddress=strtolower_utf8($queryaddress);
        $queryname=mysql_real_escape_string($queryname);
        $queryname=strtolower_utf8($queryname);
        $result=array();


        if (!empty ($checkarr)) {
        foreach ($checkarr as $io=>$check) {
            // искаем логин по паре фамилия+адрес
            if (ispos($queryaddress,strtolower_utf8($check['address']))) {
                if (!empty ($check['realname'])) {
                if (ispos($queryname,strtolower_utf8($check['realname']))) {
                    $result[]=$io;
                 }
                }
            }
         
         
        }
        }
        return ($result);

}


function catvbs_NameEdit($id,$name) {
    $id=vf($id,3);
    $name=mysql_real_escape_string($name);
    simple_update_field('catv_bankstaparsed', 'realname', $name, "WHERE `id`='".$id."'");
}

function catvbs_AddressEdit($id,$address) {
    $id=vf($id,3);
    $address=mysql_real_escape_string($address);
    simple_update_field('catv_bankstaparsed', 'address', $address, "WHERE `id`='".$id."'");
}


//used in bank statements
function catv_GetFullAddressList() {
    $query="SELECT `id`,`street`,`build`,`apt` from `catv_users`";
    $alldata=  simple_queryall($query);
    $catvconf=  rcms_parse_ini_file(CONFIG_PATH."catv.ini");
    $result=array();
    if (!empty($alldata)) {
        foreach ($alldata as $io=>$each) {
             //ala zero_tolerance for catv
            if ($each['apt']!='') {
                $result[$each['id']]=$each['street'].' '.$each['build'].'/'.$each['apt'];
            } else {
                if ($catvconf['ZERO_TOLERANCE']) {
                     $result[$each['id']]=$each['street'].' '.$each['build'].'';
                } else {
                    $result[$each['id']]=$each['street'].' '.$each['build'].'/0';
                }
                
            }
        }
    }
    return ($result);
}

function catv_GetAllRealnames() {
    $query="SELECT `id`,`realname` from `catv_users`";
    $alldata=  simple_queryall($query);
    $result=array();
    if (!empty($alldata)) {
        foreach ($alldata as $io=>$each) {
                $result[$each['id']]=$each['realname'];
        }
    }
    return ($result);
}

function catv_GetAllContracts() {
    $query="SELECT `id`,`contract` from `catv_users`";
    $alldata=  simple_queryall($query);
    $result=array();
    if (!empty($alldata)) {
        foreach ($alldata as $io=>$each) {
                $result[$each['id']]=$each['contract'];
        }
    }
    return ($result);
}


// самый мудацкий способ угадывания месяца который вообще можно придумать
function catvbs_MonthDetect($string) {
    $string=  strtolower_utf8($string);
    $montharr=array(
        '01'=>'январь',
        '02'=>'февраль',
        '03'=>'март',
        '04'=>'апрель',
        '05'=>'май',
        '06'=>'июнь',
        '07'=>'июль',
        '08'=>'август',
        '09'=>'сентябрь',
        '10'=>'октябрь',
        '11'=>'ноябрь',
        '12'=>'декабрь');
  $result=false;
    
     foreach ($montharr as $io=>$eachmonth) {
          if (ispos($string,$eachmonth)) {
           $result=$io;
          
        } else {
           if (!$result) {
               $result=false;
           }
        }
    }
    return ($result);
}

function catvbs_ShowHash($hash) {
    $hash=vf($hash);
    $allrealnames=  catv_GetAllRealnames();
    $alladdress=  catv_GetFullAddressList();
    $montharr=  months_array();
    
    $checkarr=catvbs_SearchCheckArr($alladdress, $allrealnames);
    $alter_conf=rcms_parse_ini_file(CONFIG_PATH.'catv.ini');
    $query="SELECT * from `catv_bankstaparsed` WHERE `hash`='".$hash."' ORDER BY `id` DESC";
    $alldata=  simple_queryall($query);
   

    if (!empty($alldata)) {
        $tablecells=wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('Real Name'));
        $tablecells.=wf_TableCell(__('Address'));
        $tablecells.=wf_TableCell(__('Cash'));
        $tablecells.=wf_TableCell(__('User poroposal'));
        $tablecells.=wf_TableCell(__('Month'));
        $tablecells.=wf_TableCell(__('Processed'));
        $tablecells.=wf_TableCell(__('Actions'));
        $tablerows=wf_TableRow($tablecells, 'row1');
        
        foreach ($alldata as $io=>$eachrow) {
            
        $tablecells=wf_TableCell($eachrow['id']);
        $tablecells.=wf_TableCell(bs_NameEditForm($eachrow['id'], $eachrow['realname']));
        $tablecells.=wf_TableCell(bs_AddressEditForm($eachrow['id'], $eachrow['address']));
        $tablecells.=wf_TableCell($eachrow['summ']);
        //proposal subroutine
        if  (empty($eachrow['login'])) {
          $proposed_login=catvbs_SearchLoginByAddresspart($eachrow['address'], $eachrow['realname'], $checkarr);
          //if no one found
          if (sizeof($proposed_login)==0) {
              $proposal_form=catvbs_LoginProposalForm($eachrow['id'], '');
          }
          //if only one user found
          if (sizeof($proposed_login)==1) {
              $proposal_form=bs_LoginProposalForm($eachrow['id'], $proposed_login[0]);
              //заполним со старта что-ли
              simple_update_field('catv_bankstaparsed', 'login', $proposed_login[0], "WHERE `id`='".$eachrow['id']."'");
          }
          
          //if many users found
          if (sizeof($proposed_login)>1) {
                        $proposal_form=__('Multiple users found');
          }
          
        } else {
          $proposal_form=catvbs_LoginProposalForm($eachrow['id'], $eachrow['login']);    
        }
        $tablecells.=wf_TableCell($proposal_form);
        $procflag=  web_bool_led($eachrow['state']);
        if (!$eachrow['state']) {
            $actlink=wf_JSAlert("?module=catv_banksta&lockrow=".$eachrow['id']."&showhash=".$eachrow['hash'], web_key_icon('Lock'), __('Are you serious'));
        } else {
            $actlink='';
        }
        
        //month detection here
        $month_detected=catvbs_MonthDetect($eachrow['address']);
        if ($month_detected) {
            $monthname=  web_bool_led($month_detected).' '.rcms_date_localise($montharr[$month_detected]);
        } else {
            $monthname=  web_bool_led($month_detected);
        }
        
        $tablecells.=wf_TableCell($monthname);
        $tablecells.=wf_TableCell($procflag);
        $tablecells.=wf_TableCell($actlink);
        $tablerows.=wf_TableRow($tablecells, 'row3');
            
        }
        
        $result=  wf_TableBody($tablerows, '100%', '0', 'sortable');
        
    } else {
        $result=__('Strange exeption catched');
    }
    
    show_window('', wf_BackLink("?module=catv_banksta", 'Back', true));
    show_window(__('Bank statement processing'),$result);
}

function catvbs_ProcessHash($hash) {
    global $billing;

    $alterconf=rcms_parse_ini_file(CONFIG_PATH."catv.ini");
   
    
    $query="SELECT `id`,`summ`,`login`,`address` from `catv_bankstaparsed` WHERE `hash`='".$hash."' AND `state`='0' AND `login` !=''";
    $allinprocessed=simple_queryall($query);
    if (!empty ($allinprocessed)) {
        log_register("CATV_BANKSTA PROCESSING ".$hash." START");
        
        foreach ($allinprocessed as $io=>$eachrow) {
            //setting payment variables
            
             $cash=$eachrow['summ'];
             $note=mysql_real_escape_string("CATV_BANKSTA:".$eachrow['id']);
             $month_detect=catvbs_MonthDetect($eachrow['address']);
             if ($month_detect) {
                 $target_month=$month_detect;
             } else {
                 $target_month=date("m");
             }
             $target_year=date("Y");
             $curdate=  curdatetime();
                // standalone user cash push
                 //zb_CashAdd($eachrow['login'], $cash, $operation, $cashtype, $note);
                 //deb('DEBUG CATV adding cash'.$eachrow['login']);
                 catv_CashAdd($eachrow['login'], $curdate, $cash, $target_month, $target_year, $target_month, $target_year, $note);
                 simple_update_field('catv_bankstaparsed', 'state', '1', "WHERE `id`='".$eachrow['id']."'");
                // end of processing without linking
                
        }
        
        log_register("CATV_BANKSTA PROCESSING ".$hash." END");
    } else {
        log_register("CATV_BANKSTA PROCESSING ".$hash." EMPTY");
    }
}


function catvbs_ProcessingForm($hash) {
    $hash=vf($hash);
    
    $inputs=wf_HiddenInput('processingrequest', $hash);
    $inputs.=wf_Submit('Process all payments for which the user defined');
    $result=wf_Form("", 'POST', $inputs, 'glamour');
    show_window('',$result);
}

function catvbs_LockRow($rowid) {
    $rowid=vf($rowid,3);
    simple_update_field('catv_bankstaparsed', 'state', '1', "WHERE `id`='".$rowid."'");
    log_register("CATV_BANKSTA LOCK ROW ".$rowid);
}


//payments report
function catv_PaymentsGetYearSumm($year) {
    $year=vf($year);
    $query="SELECT SUM(`summ`) from `catv_payments` WHERE `date` LIKE '".$year."-%' AND `summ` > 0";
    $result=simple_query($query);
    return($result['SUM(`summ`)']);
}

function catv_PaymentsGetMonthSumm($year,$month) {
    $year=vf($year);
    $query="SELECT SUM(`summ`) from `catv_payments` WHERE `date` LIKE '".$year."-".$month."%' AND `summ` > 0";
    $result=simple_query($query);
    return($result['SUM(`summ`)']);
}

function catv_PaymentsGetMonthCount($year,$month) {
    $year=vf($year);
    $query="SELECT COUNT(`id`) from `catv_payments` WHERE `date` LIKE '".$year."-".$month."%' AND `summ` > 0";
    $result=simple_query($query);
    return($result['COUNT(`id`)']);
}

  function catv_PaymentsShow($query) {
    $alter_conf=rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
    $alladrs=  catv_GetFullAddressList();
    $allrealnames=  catv_GetAllRealnames();
    $alltypes=zb_CashGetAllCashTypes();
    $allapayments=simple_queryall($query);

    $total=0;
    $result='<table width="100%" border="0" class="sortable">';
      $result.='
                <tr class="row1">
                <td>'.__('ID').'</td>
                <td>'.__('IDENC').'</td>
                <td>'.__('Date').'</td>
                <td>'.__('Cash').'</td>
                <td>'.__('User').'</td>
                <td>'.__('Full address').'</td>                    
                <td>'.__('Notes').'</td>
                <td>'.__('Admin').'</td>
                </tr>
                ';
    if (!empty ($allapayments)) {
        foreach ($allapayments as $io=>$eachpayment) {
           if ($alter_conf['TRANSLATE_PAYMENTS_NOTES']) {
               if ($eachpayment['notes']=='') {
                   $eachpayment['notes']=__('CaTV');
               }
               $eachpayment['notes']=  zb_TranslatePaymentNote($eachpayment['notes'], array());
           }
        
            
            $result.='
                <tr class="row3">
                <td>'.$eachpayment['id'].'</td>
                <td>'.  zb_NumEncode($eachpayment['id']).'</td>
                <td>'.$eachpayment['date'].'</td>
                <td>'.$eachpayment['summ'].'</td>
                <td> <a href="?module=catv_profile&userid='.$eachpayment['userid'].'">'.  web_profile_icon().'</a> '.@$allrealnames[$eachpayment['userid']].'</td>                    
                <td>'.@$alladrs[$eachpayment['userid']].'</td>                    
                <td>'.$eachpayment['notes'].'</td>
                <td>'.$eachpayment['admin'].'</td>
                </tr>
                ';
            if ($eachpayment['summ']>0) {
            $total=$total+$eachpayment['summ'];
            }
        }
    }
   
    $result.='</table>';
    $result.='<strong>'.__('Total').': '.$total.'</strong>';
    return($result);
}

function catv_PaymentsShowGraph($year) {
    $months=months_array();
    $result='<table width="100%" class="sortable" border="0">';
    $year_summ=catv_PaymentsGetYearSumm($year);
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
        $month_summ=catv_PaymentsGetMonthSumm($year, $eachmonth);
        $paycount=catv_PaymentsGetMonthCount($year, $eachmonth);
        $result.='
            <tr class="row3">
                <td>'.$eachmonth.'</td>
                <td><a href="?module=catv&action=reports&showreport=finance&month='.$year.'-'.$eachmonth.'">'.rcms_date_localise($monthname).'</a></td>
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
        
function catv_FinanceReport() {
      if (!isset($_POST['yearsel'])) {
        $show_year=curyear();
        } else {
        $show_year=$_POST['yearsel'];
        }
        
      $dateform='
        <form action="?module=catv&action=reports&showreport=finance" method="POST">
        '.  web_CalendarControl('showdatepayments').'
        <input type="submit" value="'.__('Show').'">
        </form>
        <br>
        ';
      
      $yearform='
        <form action="?module=catv&action=reports&showreport=finance" method="POST">
         '.web_year_selector().'
        <input type="submit" value="'.__('Show').'">
        </form>
          ';
      
show_window(__('Year'),$yearform);
show_window(__('Payments by date'),$dateform);
catv_PaymentsShowGraph($show_year);


if (!isset($_GET['month'])) {

// payments by somedate
if (isset($_POST['showdatepayments'])) {
    $paydate=mysql_real_escape_string($_POST['showdatepayments']);
    //deb($paydate);
    show_window(__('Payments by date').' '.$paydate,  catv_PaymentsShow("SELECT * from `catv_payments` WHERE `date` LIKE '".$paydate."%'"));
} else {

// today payments
$today=curdate();
show_window(__('Today payments'),  catv_PaymentsShow("SELECT * from `catv_payments` WHERE `date` LIKE '".$today."%'"));
}

} else {
    // show monthly payments
    $paymonth=mysql_real_escape_string($_GET['month']);
    
    show_window(__('Month payments'),  catv_PaymentsShow("SELECT * from `catv_payments` WHERE `date` LIKE '".$paymonth."%'"));
}
    
}        

function catv_ExportUserbaseCsv() {
    $allusers= catv_UsersGetAll();
    $allactivity=catv_ActivityGetLastAll();
    $alltariffs=  catv_TariffGetAllNames();
    $result='';
    //options
    $delimiter=";";
    $in_charset='utf-8';
    $out_charset='windows-1251';
    /////////////////////
    if (!empty($allusers)) {
        $result.=__('ID').$delimiter.__('Contract').$delimiter.__('Real name').$delimiter.__('Street').$delimiter.__('Build').$delimiter.__('Apartment').$delimiter.__('Phone').$delimiter.__('Tariff').$delimiter.__('Planned tariff change').$delimiter.__('Cash').$delimiter.__('Discount').$delimiter.__('Notes').$delimiter.__('Decoder').$delimiter.__('Internet account').$delimiter.__('Connection')."\n";
        foreach ($allusers as $io=>$eachuser) {
           
           $result.=$eachuser['id'].$delimiter.$eachuser['contract'].$delimiter.$eachuser['realname'].$delimiter.$eachuser['street'].$delimiter.$eachuser['build'].$delimiter.$eachuser['apt'].$delimiter.$eachuser['phone'].$delimiter.@$alltariffs[$eachuser['tariff']].$delimiter.$eachuser['tariff_nm'].$delimiter.$eachuser['cash'].$delimiter.$eachuser['discount'].$delimiter.$eachuser['notes'].$delimiter.$eachuser['decoder'].$delimiter.$eachuser['inetlink'].$delimiter.@$allactivity[$eachuser['id']]."\n";
        }
    if ($in_charset!=$out_charset) {
        $result=  iconv($in_charset, $out_charset, $result);
    }
    // push data for excel handler
   header('Content-type: application/ms-excel');
   header('Content-Disposition: attachment; filename=userbase.csv');
    echo $result;
    die();
    }

}
        
           
?>