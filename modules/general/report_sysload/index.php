<?php
if (cfr('SYSLOAD')) {
    
  if (wf_CheckGet(array('checkupdates'))) {
      zb_BillingCheckUpdates();
  }
  
  
  if (wf_CheckGet(array('greed'))) {
      //key deletion
      if (wf_CheckGet(array('licensedelete'))) {
          $avarice=new Avarice();
          $avarice->deleteKey($_GET['licensedelete']);
          rcms_redirect('?module=report_sysload&greed=true');
      }
      
      //key installation
      if (wf_CheckPost(array('createlicense'))) {
          $avarice=new Avarice();
          if ($avarice->createKey($_POST['createlicense'])) {
              rcms_redirect('?module=report_sysload&greed=true');
          } else {
              show_window(__('Error'), __('Unacceptable license key'));
          }
      }
      //key editing
      if (wf_CheckPost(array('editlicense','editdbkey'))) {
          $avarice=new Avarice();
          if ($avarice->updateKey($_POST['editdbkey'], $_POST['editlicense'])) {
              rcms_redirect('?module=report_sysload&greed=true');
          } else {
              show_window(__('Error'), __('Unacceptable license key'));
          }
      }

   //show available license keys
  zb_LicenseLister();
  
  } else {
show_window('',  wf_Link('?module=report_sysload&greed=true', wf_img('skins/icon_dollar.gif').' '.__('Installed license keys'), true, 'ubButton'));

 zb_BillingStats(false);
 
 

 $globconf=$ubillingConfig->getBilling();

 $monit_url=$globconf['PHPSYSINFO'];
 $top=$globconf['TOP'];
 $top_output=  wf_tag('pre').shell_exec($top).  wf_tag('pre',true);
 $uptime=$globconf['UPTIME'];
 $uptime_output=wf_tag('pre').shell_exec($uptime).  wf_tag('pre',true);


 if (!empty($monit_url)) {
  $monit_code=  wf_tag('iframe', false, '', 'src="'.$monit_url.'" width="1000" height="500" frameborder="0"').wf_tag('iframe',true);
  show_window(__('System health with phpSysInfo'),  wf_modal(__('Show'), __('Show'), $monit_code, 'ubButton', 1020, 570));
 }

 show_window(__('Process'),$top_output);
 show_window(__('Uptime'),$uptime_output);
  }
  
} else {
      show_error(__('You cant control this module'));
}

?>
