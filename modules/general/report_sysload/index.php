<?php
if (cfr('SYSLOAD')) {
    
  zb_BillingStats(false);
 

 $globconf=parse_ini_file('config/billing.ini');
 $monit_url=$globconf['PHPSYSINFO'];
 $monit_code='';
 $monit_code='<iframe src="'.$monit_url.'" width="90%" height="90%" frameborder="0"></iframe>';
 $top=$globconf['TOP'];
 $top_output='<pre>'.shell_exec($top).'</pre>';
 $uptime=$globconf['UPTIME'];
 $uptime_output='<pre>'.shell_exec($uptime).'</pre>';
 if ($monit_url) {
 show_window(__('System health with phpSysInfo'),web_Overlay(__('Show'), $monit_code,0.95));
 }

 

 show_window(__('Process'),$top_output);
 show_window(__('Uptime'),$uptime_output);

} else {
      show_error(__('You cant control this module'));
}

?>
