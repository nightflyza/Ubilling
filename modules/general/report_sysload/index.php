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
 
 //ajax data loaders
 //database check
 if (wf_CheckGet(array('ajaxdbcheck'))) {
     die(zb_DBCheckRender());
 }
 //database stats
 if (wf_CheckGet(array('ajaxdbstats'))) {
     die(zb_DBStatsRender());
 }

 $globconf=$ubillingConfig->getBilling();
 $alterconf=$ubillingConfig->getAlter();
 $monit_url=$globconf['PHPSYSINFO'];

 //custom scripts output handling. We must run this before all others.
 if (isset($alterconf['SYSLOAD_CUSTOM_SCRIPTS'])) {
     if (!empty($alterconf['SYSLOAD_CUSTOM_SCRIPTS'])) {
         $customScriptsData=web_ReportSysloadCustomScripts($alterconf['SYSLOAD_CUSTOM_SCRIPTS']);     
     }
 }
 
 
 $sysInfoData='';
 //phpinfo()
 $phpInfoCode= wf_modal(__('Check required PHP extensions'), __('Check required PHP extensions'), zb_CheckPHPExtensions(), 'ubButton','800','600');
 if ($alterconf['UBCACHE_STORAGE']=='memcached') {
 $phpInfoCode.= wf_modal(__('Stats').' '.__('Memcached'), __('Stats').' '.__('Memcached'), web_MemCachedRenderStats(), 'ubButton','800','600');
 }
 $phpInfoCode.= wf_tag('br');
 $phpInfoCode.=  wf_tag('iframe', false, '', 'src="?module=report_sysload&phpinfo=true" width="1000" height="500" frameborder="0"').wf_tag('iframe',true);
 $sysInfoData.= wf_modalAuto(__('Information about PHP version'), __('Information about PHP version'), $phpInfoCode, 'ubButton');
 
 
 //database info
 $dbInfoCode= zb_DBStatsRenderContainer();
 $sysInfoData.= wf_modal(__('MySQL database info'), __('MySQL database info'), $dbInfoCode, 'ubButton', 1020, 570);
 
 //phpsysinfo frame
 if (!empty($monit_url)) {
  $monitCode=  wf_tag('iframe', false, '', 'src="'.$monit_url.'" width="1000" height="500" frameborder="0"').wf_tag('iframe',true);
  $sysInfoData.= wf_modalAuto(__('System health with phpSysInfo'), __('phpSysInfo'), $monitCode, 'ubButton');
 }
 
 show_window('', $sysInfoData);
 
//custom scripts shows data
 if (isset($alterconf['SYSLOAD_CUSTOM_SCRIPTS'])) {
     if (!empty($alterconf['SYSLOAD_CUSTOM_SCRIPTS'])) {
         show_window(__('Additional monitoring'),$customScriptsData);     
     }
 }
 
 $top=$globconf['TOP'];
 $top_output=  wf_tag('pre').shell_exec($top).  wf_tag('pre',true);
 $uptime=$globconf['UPTIME'];
 $uptime_output=wf_tag('pre').shell_exec($uptime).  wf_tag('pre',true);
 
 show_window(__('Process'),$top_output);
 show_window(__('Uptime'),$uptime_output);
  
  
} else {
      show_error(__('You cant control this module'));
}

?>
