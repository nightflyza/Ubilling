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
    // Cache keys info
    if (wf_CheckGet(array('ajaxcacheinfo'))) {
        die(zb_ListCacheInform());
    }
    // Cache keys and data info
    if (wf_CheckGet(array('ajaxcachedata'))) {
        die(zb_ListCacheInform('data'));
    }
    // Clear cache
    if (wf_CheckGet(array('ajaxcacheclear'))) {
        die(zb_ListCacheInform('clear'));
    }
    //memcached stats
    if (wf_CheckGet(array('ajaxmemcachedstats'))) {
        die(web_MemCachedRenderStats());
    }
    //redis stats
    if (wf_CheckGet(array('ajaxredisstats'))) {
        die(web_RedisRenderStats());
    }
    $globconf = $ubillingConfig->getBilling();
    $alterconf = $ubillingConfig->getAlter();
    $monit_url = $globconf['PHPSYSINFO'];
    $cache_info = $alterconf['UBCACHE_STORAGE'];

    //custom scripts output handling. We must run this before all others.
    if (isset($alterconf['SYSLOAD_CUSTOM_SCRIPTS'])) {
        if (!empty($alterconf['SYSLOAD_CUSTOM_SCRIPTS'])) {
            $customScriptsData = web_ReportSysloadCustomScripts($alterconf['SYSLOAD_CUSTOM_SCRIPTS']);
        }
    }


    $sysInfoData = '';
    //phpinfo()
    $phpInfoCode = wf_modal(__('Check required PHP extensions'), __('Check required PHP extensions'), zb_CheckPHPExtensions(), 'ubButton', '800', '600');
    $phpInfoCode.= wf_tag('br');
    $phpInfoCode.= wf_tag('iframe', false, '', 'src="?module=report_sysload&phpinfo=true" width="1000" height="500" frameborder="0"') . wf_tag('iframe', true);
    $sysInfoData.= wf_modalAuto(__('Information about PHP version'), __('Information about PHP version'), $phpInfoCode, 'ubButton');


    //database info
    $dbInfoCode = zb_DBStatsRenderContainer();
    $sysInfoData.= wf_modal(__('MySQL database info'), __('MySQL database info'), $dbInfoCode, 'ubButton', 1020, 570);

    //loaded modules info
    $loadedModulesCode = zb_ListLoadedModules();
    $sysInfoData.= wf_modal(__('Loaded modules'), __('Loaded modules'), $loadedModulesCode, 'ubButton', 1020, 570);

    //phpsysinfo frame
    if (!empty($monit_url)) {
        if (file_exists($monit_url . '/index.php')) {
            $monitCode = wf_tag('iframe', false, '', 'src="' . $monit_url . '" width="1000" height="500" frameborder="0"') . wf_tag('iframe', true);
            $sysInfoData.= wf_modalAuto(__('phpSysInfo'), __('System health with phpSysInfo'), $monitCode, 'ubButton');
        } else {
            //installing phpsysinfo
            if (wf_CheckGet(array('phpsysinfoinstall'))) {
                zb_InstallPhpsysinfo();
                die(wf_tag('span',false,'alert_success').__('Done').  wf_tag('span',true));
            }
            $monitCode = wf_AjaxLink('?module=report_sysload&phpsysinfoinstall=true', __('Download') . ' ' . __('phpSysInfo'), 'phpsysinfoinstall', true, 'ubButton');
            $monitCode.= wf_AjaxContainer('phpsysinfoinstall');

            $sysInfoData.= wf_modalAuto(__('phpSysInfo'), __('System health with phpSysInfo'), $monitCode, 'ubButton');
        }
    }

    //Cache
    if ($cache_info == 'files' OR $cache_info = 'memcached') {
        $cacheInfo = zb_ListCacheInformRenderContainer();
        $sysInfoData.= wf_modalAuto(__('Cache'), __('Cache information'), $cacheInfo, 'ubButton');
    }

    show_window('', $sysInfoData);

//custom scripts shows data
    if (isset($alterconf['SYSLOAD_CUSTOM_SCRIPTS'])) {
        if (!empty($alterconf['SYSLOAD_CUSTOM_SCRIPTS'])) {
            show_window(__('Additional monitoring'), $customScriptsData);
        }
    }

    $top = $globconf['TOP'];
    $top_output = wf_tag('pre') . shell_exec($top) . wf_tag('pre', true);
    $uptime = $globconf['UPTIME'];
    $uptime_output = wf_tag('pre') . shell_exec($uptime) . wf_tag('pre', true);

    show_window(__('Process'), $top_output);
    show_window(__('Uptime'), $uptime_output);
} else {
    show_error(__('You cant control this module'));
}
?>
