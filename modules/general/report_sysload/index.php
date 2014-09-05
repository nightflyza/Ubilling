<?php
if (cfr('SYSLOAD')) {
    
  if (wf_CheckGet(array('checkupdates'))) {
      zb_BillingCheckUpdates();
  }
  
  if (wf_CheckGet(array('phpinfo'))) {
      phpinfo();
      die();
  }
 
 zb_BillingStats(false);
 
 

 $globconf=$ubillingConfig->getBilling();

 $monit_url=$globconf['PHPSYSINFO'];
 $top=$globconf['TOP'];
 $top_output=  wf_tag('pre').shell_exec($top).  wf_tag('pre',true);
 $uptime=$globconf['UPTIME'];
 $uptime_output=wf_tag('pre').shell_exec($uptime).  wf_tag('pre',true);

 $sysInfoData='';
 //phpinfo()
 $phpInfoCode=  wf_tag('iframe', false, '', 'src="?module=report_sysload&phpinfo=true" width="1000" height="500" frameborder="0"').wf_tag('iframe',true);
 $sysInfoData.= wf_modal(__('Information about PHP version'), __('Information about PHP version'), $phpInfoCode, 'ubButton', 1020, 570);

 //database info
 $dbInfoCode=  zb_DBStatsRender();
 $sysInfoData.= wf_modal(__('MySQL database info'), __('MySQL database info'), $dbInfoCode, 'ubButton', 1020, 570);
 
 //phpsysinfo frame
 if (!empty($monit_url)) {
  $monitCode=  wf_tag('iframe', false, '', 'src="'.$monit_url.'" width="1000" height="500" frameborder="0"').wf_tag('iframe',true);
  $sysInfoData.= wf_modal(__('System health with phpSysInfo'), __('phpSysInfo'), $monitCode, 'ubButton', 1020, 570);
 }
 
 show_window('', $sysInfoData);
 show_window(__('Process'),$top_output);
 show_window(__('Uptime'),$uptime_output);
  
  
} else {
      show_error(__('You cant control this module'));
}

?>
