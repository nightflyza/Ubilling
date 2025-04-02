<?php

////////////////////////////////////////////////////////////////////////////////
//   Copyright (C) Ubilling Development Team                                  //
//   https://ubilling.net.ua                                                  //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   This product released under GNU General Public License v2                //
////////////////////////////////////////////////////////////////////////////////

/**
 * is Xhprof Hierarchical Profiler/slow page log enabled?
 */
$minimalBillingConfig = @parse_ini_file('config/billing.ini');
if (@$minimalBillingConfig['XHPROF']) {
    define('XHPROF', 1);
} else {
    define('XHPROF', 0);
}

if (@$minimalBillingConfig['SLOW_PAGE_LOG']) {
    define('SLOW_PAGE_LOG', $minimalBillingConfig['SLOW_PAGE_LOG']);
} else {
    define('SLOW_PAGE_LOG', 0);
}

if (XHPROF) {
    //xhprof installed?
    if (file_exists('modules/foreign/xhprof/xhprof_lib/utils/xhprof_lib.php')) {
        define("XHPROF_ROOT", __DIR__ . '/modules/foreign/xhprof');
        require_once(XHPROF_ROOT . '/xhprof_lib/utils/xhprof_lib.php');
        require_once(XHPROF_ROOT . '/xhprof_lib/utils/xhprof_runs.php');
        //append XHPROF_FLAGS_NO_BUILTINS if your PHP instance crashes
        xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
    }
}

////////////////////////////////////////////////////////////////////////////////
// Initializations                                                            //
////////////////////////////////////////////////////////////////////////////////
define('RCMS_ROOT_PATH', './');

require_once(RCMS_ROOT_PATH . 'common.php');
$menu_points = parse_ini_file(CONFIG_PATH . 'menus.ini', true);
// Page gentime start 
$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];


// Send main headers
header('Last-Modified: ' . gmdate('r'));
header('Content-Type: text/html; charset=' . $system->config['encoding']);
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1 
header("Pragma: no-cache");


/**
 * Maintance mode
 */
if (file_exists('UPDATE')) {
    die('Ubilling maintance in progress');
}

/**
 * Stargazer PID control
 */
$ubillingMainConf = parse_ini_file(CONFIG_PATH . 'billing.ini');
if (isset($ubillingMainConf['NOSTGCHECKPID'])) {
    if ($ubillingMainConf['NOSTGCHECKPID']) {
        $checkStgPid = false;
    } else {
        $checkStgPid = true;
    }
} else {
    $checkStgPid = true;
}

if ($checkStgPid) {
    $stgPidPath = $ubillingMainConf['STGPID'];
    if (!file_exists($stgPidPath)) {
        $stgPidAlert = __('Stargazer currently not running. We strongly advise against trying to use Ubilling in this case. If you are absolutely sure of what you are doing - you can turn off this alert with the option NOSTGCHECKPID');
        die($stgPidAlert);
    }
}

/**
 * IP ACL implementation
 */
if (@$ubillingMainConf['IPACL_ENABLED']) {
    $ipAclAllowedIps = rcms_scandir(IPACLALLOWIP_PATH);
    $ipAclAllowedNets = rcms_scandir(IPACLALLOWNETS_PATH);
    //checks only if at least one ACL exists
    if (!empty($ipAclAllowedIps) or ! empty($ipAclAllowedNets)) {
        $ipAclAllowedFlag = false;
        $ipAclAllowedIps = array_flip($ipAclAllowedIps);
        $ipAclRemoteIp = $_SERVER['REMOTE_ADDR'];
        //localhost is always allowed
        if ($ipAclRemoteIp != '127.0.0.1') {
            //checking is remote IP in allowed list
            if (isset($ipAclAllowedIps[$ipAclRemoteIp])) {
                $ipAclAllowedFlag = true;
            }
        } else {
            $ipAclAllowedFlag = true;
        }

        //if user IP isnt still allowed as-is - check on nets ACL
        if (!$ipAclAllowedFlag) {
            if (!empty($ipAclAllowedNets)) {
                foreach ($ipAclAllowedNets as $ipAclIndex => $ipAclNeteach) {
                    $ipAclNetCidr = str_replace('_', '/', $ipAclNeteach);
                    $ipAclNetParams = ipcidrToStartEndIP($ipAclNetCidr);
                    if (multinet_checkIP($ipAclRemoteIp, $ipAclNetParams['startip'], $ipAclNetParams['endip'])) {
                        $ipAclAllowedFlag = true;
                    }
                }
            }
        }

        //Interrupt execution if remote user is not allowed explicitly
        if (!$ipAclAllowedFlag) {
            require_once(SKIN_PATH . 'acldenied.html');
            die();
        }
    }
}


// Loading main module
$system->setCurrentPoint('__MAIN__');
if (!empty($_GET['module']))
    $module = basename($_GET['module']);
else
    $module = 'index';
if (!empty($system->modules['main'][$module]))
    include_once(MODULES_PATH . $module . '/index.php');

// Load menu modules
include_once(CUR_SKIN_PATH . 'skin.php');
if (!empty($menu_points)) {
    foreach ($menu_points as $point => $menus) {
        $system->setCurrentPoint($point);
        if (!empty($menus) && isset($skin['menu_point'][$point])) {
            foreach ($menus as $menu) {
                if (substr($menu, 0, 4) == 'ucm:' && is_readable(DF_PATH . substr($menu, 4) . '.ucm')) {
                    $file = file(DF_PATH . substr($menu, 4) . '.ucm');
                    $title = preg_replace("/[\n\r]+/", '', $file[0]);
                    $align = preg_replace("/[\n\r]+/", '', $file[1]);
                    unset($file[0]);
                    unset($file[1]);
                    show_window($title, implode('', $file), $align);
                } elseif (!empty($system->modules['menu'][$menu])) {
                    $module = $menu;
                    $module_dir = MODULES_PATH . $menu;
                    require(MODULES_PATH . $menu . '/index.php');
                } else {
                    show_window('', __('Module not found'), 'center');
                }
            }
        }
    }
}

if (XHPROF) {
    if (defined('XHPROF_ROOT')) {
        $xhprof_data = xhprof_disable();
        $xhprof_runs = new XHProfRuns_Default();
        $xhprof_run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_ubilling");
        $xhprof_content = '<iframe src="modules/foreign/xhprof/xhprof_html/index.php?run=' . $xhprof_run_id . '&source=xhprof_ubilling" width="100%" height="750"></iframe>';
        $xhprof_link = wf_modal(wf_img_sized('skins/xhprof.png', __('XHPROF'), 20), 'XHProf current page results', $xhprof_content, '', '1024', '768');
    } else {
        $xhprof_install_url = '?module=report_sysload&xhprofmoduleinstall=true';
        $xhprof_install_form = wf_AjaxLink($xhprof_install_url, wf_img('skins/icon_download.png') . ' ' . __('Download') . ' ' . __('XHProf'), 'xhprofinstall', true, 'ubButton');
        $xhprof_install_form .= wf_AjaxContainer('xhprofinstall');
        $xhprof_link = wf_modal(wf_img_sized('skins/xhprof.png', __('XHPROF'), 20), __('Download') . ' XHProf', $xhprof_install_form, '', '320', '200');
    }
}

// Start output
require_once(CUR_SKIN_PATH . 'skin.general.php');

// 
// Everything is better with unicorns
// 
// _______\)%%%%%%%%._              
//`''''-'-;   % % % % %'-._         
//        :b) \            '-.      
//        : :__)'    .'    .'       
//        :.::/  '.'   .'           
//        o_i/   :    ;             
//               :   .'             
//                ''`
// Glory to Ukraine!
//