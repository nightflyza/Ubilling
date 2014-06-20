<?php
if (cfr('SYSLOAD')) {
    
  if (wf_CheckGet(array('checkupdates'))) {
      zb_BillingCheckUpdates();
  }
 
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
  
  
} else {
      show_error(__('You cant control this module'));
}

?>
