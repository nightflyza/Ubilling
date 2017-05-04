<?php
if (cfr('SPEEDCONTROL')) {

  function web_UsersLister($users) {
      $tablecells=wf_TableCell(__('Login'));
      $tablecells.=wf_TableCell(__('Real Name'));
      $tablecells.=wf_TableCell(__('Full address'));
      $tablecells.=wf_TableCell(__('Tariff'));
      $tablecells.=wf_TableCell(__('Tariff speeds'));
      $tablecells.=wf_TableCell(__('Speed override'));
      $tablecells.=wf_TableCell(__('Actions'));
      $tablerows=wf_TableRow($tablecells, 'row1');
                
      if (!empty ($users)) {
          $udata=array();
          $alluserdata=zb_UserGetAllStargazerData();
          $alladdress=zb_AddressGetFulladdresslist();
          $allrealnames=zb_UserGetAllRealnames();
          $allspeeds=zb_TariffGetAllSpeeds();
          if (!empty ($alluserdata)) {
              foreach ($alluserdata as $ia=>$eachdata) {
                  $udata[$eachdata['login']]['Tariff']=$eachdata['Tariff'];
                  @$udata[$eachdata['login']]['Address']=$alladdress[$eachdata['login']];
                  @$udata[$eachdata['login']]['RealName']=$allrealnames[$eachdata['login']];
                  @$udata[$eachdata['login']]['NormalSpeedDown']=$allspeeds[$eachdata['Tariff']]['speeddown'];
                  @$udata[$eachdata['login']]['NormalSpeedUp']=$allspeeds[$eachdata['Tariff']]['speedup'];
                  }
          }
          foreach ($users as $io=>$eachuser) {
                $tablecells=wf_TableCell(wf_Link('?module=userprofile&username='.$eachuser['login'], web_profile_icon().' '.$eachuser['login']));
                $tablecells.=wf_TableCell($udata[$eachuser['login']]['RealName']);
                $tablecells.=wf_TableCell($udata[$eachuser['login']]['Address']);
                $tablecells.=wf_TableCell($udata[$eachuser['login']]['Tariff']);
                $tablecells.=wf_TableCell($udata[$eachuser['login']]['NormalSpeedDown'].'/'.$udata[$eachuser['login']]['NormalSpeedUp']);
                $tablecells.=wf_TableCell(zb_UserGetSpeedOverride($eachuser['login']));
                $fixlink=wf_JSAlert('?module=speedcontrol&fix='.$eachuser['login'], '<img src="skins/icon_repair.gif" title='.__('Fix').'>', 'Are you serious');
                $tablecells.=wf_TableCell($fixlink);
                $tablerows.=wf_TableRow($tablecells, 'row3');
                        }
      }
      $result=wf_TableBody($tablerows, '100%', '0', 'sortable');
      return($result);
  }
  
  function zb_SpeedControlGetOverrideUsers() {
      $query="SELECT `login` from `userspeeds` WHERE `speed` NOT LIKE '0'";
      $alloverrides=simple_queryall($query);
      $result=array();
      if (!empty ($alloverrides)) {
          foreach ($alloverrides as $io=>$eachoverride) {
              $result[]=$eachoverride;
          }
      }
      return($result);
  }
  
  function zb_SpeedControlFix($login) {
      
  }
  
  //fixing speed override 
  if (isset($_GET['fix'])) {
        $login=vf($_GET['fix']);
        $speed=0;
        zb_UserDeleteSpeedOverride($login);
        zb_UserCreateSpeedOverride($login, $speed);
        log_register("SPEEDFIX (".$login.")");
        $billing->resetuser($login);
        log_register("RESET User (".$login.")");
        rcms_redirect("?module=speedcontrol");
  }
  
  $alloverrides=zb_SpeedControlGetOverrideUsers();
  show_window(__('Users with speed overrides'),web_UsersLister($alloverrides));


} else {
      show_error(__('You cant control this module'));
}

?>
