<?php
if (cfr('SYSLOAD')) {
    
  if (wf_CheckGet(array('checkupdates'))) {
      zb_BillingCheckUpdates();
  }
  
  zb_BillingStats(false);
 
  
  function zb_LicenseLister() {
      $avarice=new Avarice();
      $all=$avarice->getLicenseKeys();
      if (!empty($all)) {
          debarr($all);
      }
  }
  
 
  
  

 $globconf=parse_ini_file('config/billing.ini');
 $monit_url=$globconf['PHPSYSINFO'];
 $monit_code='';
 $monit_code='<iframe src="'.$monit_url.'" width="1000" height="500" frameborder="0"></iframe>';
 $top=$globconf['TOP'];
 $top_output='<pre>'.shell_exec($top).'</pre>';
 $uptime=$globconf['UPTIME'];
 $uptime_output='<pre>'.shell_exec($uptime).'</pre>';

 if ($monit_url) {
   show_window(__('System health with phpSysInfo'),  wf_modal(__('Show'), __('Show'), $monit_code, 'ubButton', 1020, 570));
 }

 

 show_window(__('Process'),$top_output);
 show_window(__('Uptime'),$uptime_output);

} else {
      show_error(__('You cant control this module'));
}

?>
