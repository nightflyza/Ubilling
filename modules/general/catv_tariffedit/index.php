<?php
// check for right of current admin on this module
if (cfr('CATVTARIFFEDIT')) {
    
 
  catv_GlobalControlsShow();
  
  if (wf_CheckGet(array('userid'))) {
      $userid=$_GET['userid'];
      $catv_conf=catv_LoadConfig();
      $alltariffs=catv_TariffGetAllNames();
      $userdata=catv_UserGetData($userid);
      $currenttariff=$userdata['tariff'];
      
      $realname=$userdata['realname'];
      $address=$userdata['street'].' '.$userdata['build'].'/'.$userdata['apt'];
      
      //if someone changing tariff next month
      if (wf_CheckPost(array('newusertariffnm'))) {
          catv_UserSetTariffNM($userid, $_POST['newusertariffnm']);
          rcms_redirect("?module=catv_tariffedit&userid=".$userid);
      }
      
      if ($catv_conf['TARIFF_NOW_CHANGE']) {
       //if someone changing tariff now
          if (wf_CheckPost(array('newusertariffnow'))) {
          catv_UserSetTariff($userid, $_POST['newusertariffnow']);
          rcms_redirect("?module=catv_tariffedit&userid=".$userid);
      }
          
       show_window($address, $realname);    
          
      $nowinputs=wf_Selector('newusertariffnow', $alltariffs, 'Tariff', $currenttariff, false);
      $nowinputs.=wf_Submit('Change right now');
      $nowform=wf_Form('', 'POST',  $nowinputs, 'glamour', '');
      show_window(__('Edit tariff'), $nowform);
      }

      
      $editinputs=wf_Selector('newusertariffnm', $alltariffs, 'Tariff', $currenttariff, false);
      $editinputs.=wf_Submit('Next month');
      $editform=wf_Form('', 'POST',  $editinputs, 'glamour', '');
      show_window(__('Edit tariff'), $editform);
      
      
      
      
      catv_ProfileBack($userid);
      
  }  
    
    
} else {
      show_error(__('You cant control this module'));
}

?>