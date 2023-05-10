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
    //database fix
    if (wf_CheckGet(array('dbrepairtable'))) {
        die(zb_DBRepairTable($_GET['dbrepairtable']));
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
    //cache key data preview 
    if (wf_CheckGet(array('datacachekeyview'))) {
        die(zb_CacheInformKeyView(ubRouting::get('datacachekeyview')));
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
    //cache key destroy
    if (wf_CheckGet(array('deletecachekey'))) {
        die(zb_CacheKeyDestroy($_GET['deletecachekey']));
    }

    $globconf = $ubillingConfig->getBilling();
    $alterconf = $ubillingConfig->getAlter();
    $monit_url = '';
    if (!empty($globconf['PHPSYSINFO'])) {
        $monit_url = MODULES_DOWNLOADABLE . $globconf['PHPSYSINFO'];
    }

    $cache_info = $alterconf['UBCACHE_STORAGE'];

    //custom scripts output handling. We must run this before all others.
    if (isset($alterconf['SYSLOAD_CUSTOM_SCRIPTS'])) {
        if (!empty($alterconf['SYSLOAD_CUSTOM_SCRIPTS'])) {
            $customScriptsData = web_ReportSysloadCustomScripts($alterconf['SYSLOAD_CUSTOM_SCRIPTS']);
        }
    }


    $sysInfoData = '';
    //phpinfo()
    $phpInfoCode = wf_modal(wf_img('skins/icon_puzzle.png') . ' ' . __('Check required PHP extensions'), __('Check required PHP extensions'), zb_CheckPHPExtensions(), 'ubButton', '800', '600');
    $phpInfoCode .= wf_tag('br');
    $phpInfoCode .= wf_tag('iframe', false, '', 'src="?module=report_sysload&phpinfo=true" width="1000" height="500" frameborder="0"') . wf_tag('iframe', true);
    $sysInfoData .= wf_modalAuto(wf_img('skins/icon_php.png') . ' ' . __('Information about PHP version'), __('Information about PHP version'), $phpInfoCode, 'ubButton');


    //database info
    $dbInfoCode = zb_DBStatsRenderContainer();
    $sysInfoData .= wf_modal(wf_img('skins/icon_restoredb.png') . ' ' . __('MySQL database info'), __('MySQL database info'), $dbInfoCode, 'ubButton', 1020, 570);

    //loaded modules info
    $loadedModulesCode = zb_ListLoadedModules();
    $sysInfoData .= wf_modal(wf_img('skins/icon_puzzle.png') . ' ' . __('Loaded modules'), __('Loaded modules'), $loadedModulesCode, 'ubButton', 1020, 570);

    //phpsysinfo frame
    if (!empty($monit_url)) {
        if (file_exists($monit_url . '/index.php')) {
            $monitCode = wf_tag('iframe', false, '', 'src="' . $monit_url . '" width="1000" height="500" frameborder="0"') . wf_tag('iframe', true);
            $sysInfoData .= wf_modalAuto(wf_img('skins/snmp.png') . ' ' . __('phpSysInfo'), __('System health with phpSysInfo'), $monitCode, 'ubButton');
        } else {
            //installing phpsysinfo
            if (wf_CheckGet(array('phpsysinfoinstall'))) {
                if (cfr('ROOT')) {
                    zb_InstallPhpsysinfo();
                    $installNotification = wf_tag('span', false, 'alert_success') . __('Done') . '! ' . __('Refresh page') . '.' . wf_tag('span', true);
                    die($installNotification);
                } else {
                    die(wf_tag('span', false, 'alert_error') . __('Access denied') . wf_tag('span', true));
                }
            }
            $monitCode = wf_AjaxLink('?module=report_sysload&phpsysinfoinstall=true', wf_img('skins/icon_download.png') . ' ' . __('Download') . ' ' . __('phpSysInfo'), 'phpsysinfoinstall', true, 'ubButton');
            $monitCode .= wf_AjaxContainer('phpsysinfoinstall');

            $sysInfoData .= wf_modalAuto(wf_img('skins/snmp.png') . ' ' . __('phpSysInfo'), __('System health with phpSysInfo'), $monitCode, 'ubButton');
        }
    }

    //xhprof installation
    if (ubRouting::checkGet('xhprofmoduleinstall')) {
        if (cfr('ROOT')) {
            zb_InstallXhprof();
            $installNotification = wf_tag('span', false, 'alert_success') . __('Done') . '! ' . __('Refresh page') . '.' . wf_tag('span', true);
            die($installNotification);
        } else {
            die(wf_tag('span', false, 'alert_error') . __('Access denied') . wf_tag('span', true));
        }
    }

    //Cache
    if ($cache_info == 'files' OR $cache_info = 'memcached') {
        $cacheInfo = zb_ListCacheInformRenderContainer();
        $sysInfoData .= wf_modalAuto(wf_img('skins/icon_cache.png') . ' ' . __('Cache'), __('Cache information'), $cacheInfo, 'ubButton') . ' ';
    }

    //process monitor
    if (cfr('ROOT')) {
        $sysInfoData .= wf_Link(ProcessMon::URL_ME, wf_img('skins/icon_thread.png') . ' ' . __('Background processes'), false, 'ubButton') . ' ';
    }

    //apachezen
    if (cfr('ROOT')) {
        $sysInfoData .= wf_Link(ApacheZen::URL_ME, wf_img('skins/zen.png') . ' ' . __('Apache') . ' ' . __('Zen'), true, 'ubButton');
    }

    show_window('', $sysInfoData);

//custom scripts shows data
    if (isset($alterconf['SYSLOAD_CUSTOM_SCRIPTS'])) {
        if (!empty($alterconf['SYSLOAD_CUSTOM_SCRIPTS'])) {
            show_window(__('Additional monitoring'), $customScriptsData);
        }
    }

//system health here

    $sysHealthControls = wf_AjaxLink('?module=report_sysload&ajsysload=health', wf_img('skins/icon_health.png') . ' ' . __('System health'), 'reportsysloadcontainer', false, 'ubButton') . ' ';
    $sysHealthControls .= wf_AjaxLink('?module=report_sysload&ajsysload=top', wf_img('skins/icon_process.png') . ' ' . __('Process'), 'reportsysloadcontainer', false, 'ubButton') . ' ';
    $sysHealthControls .= wf_AjaxLink('?module=report_sysload&ajsysload=uptime', wf_img('skins/icon_uptime.png') . ' ' . __('Uptime'), 'reportsysloadcontainer', false, 'ubButton') . ' ';
    $sysHealthControls .= wf_AjaxLink('?module=report_sysload&ajsysload=df', wf_img('skins/icon_disks.png') . ' ' . __('Free space'), 'reportsysloadcontainer', false, 'ubButton') . ' ';
    show_window('', $sysHealthControls);

    $defaultContainerContent = web_ReportSysloadRenderLA();
    $defaultContainerContent .= web_ReportSysloadRenderDisksCapacity();
    $sysLoadContainer = wf_AjaxContainer('reportsysloadcontainer', '', $defaultContainerContent);

    if (ubRouting::checkGet('ajsysload')) {
        $renderAjData = ubRouting::get('ajsysload');
        switch ($renderAjData) {
            case 'health':
                die($defaultContainerContent);
                break;
            case 'top':
                die(web_ReportSysloadRenderTop());
                break;
            case 'uptime':
                die(web_ReportSysloadRenderUptime());
                break;
            case 'df':
                die(web_ReportSysloadRenderDF());
                break;
        }
    }


    show_window(__('System health'), $sysLoadContainer);
} else {
    show_error(__('You cant control this module'));
}

