<?php
// Set following option to 1 for enable debug mode
define('XHPROF', 0);
if (XHPROF) {
define("XHPROF_ROOT", __DIR__ . '/xhprof');
require_once (XHPROF_ROOT . '/xhprof_lib/utils/xhprof_lib.php');
require_once (XHPROF_ROOT . '/xhprof_lib/utils/xhprof_runs.php');
xhprof_enable(XHPROF_FLAGS_NO_BUILTINS + XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
}
////////////////////////////////////////////////////////////////////////////////
//   Copyright (C) ReloadCMS Development Team                                 //
//   http://reloadcms.com                                                  //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   This product released under GNU General Public License v2                //
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Initializations                                                            //
////////////////////////////////////////////////////////////////////////////////
define('RCMS_ROOT_PATH', './');
//maintance fix
if (file_exists("UPDATE")) {
die("Ubilling maintance in progress");
}

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

// stargazer pid control
$ubillingMainConf=  parse_ini_file(CONFIG_PATH.'billing.ini');
if (isset($ubillingMainConf['NOSTGCHECKPID'])) {
    if ($ubillingMainConf['NOSTGCHECKPID']) {
        $checkStgPid=false;
    } else {
        $checkStgPid=true;
    }
} else {
    $checkStgPid=true;
}

if ($checkStgPid) {
    $stgPidPath=$ubillingMainConf['STGPID'];
    if (!file_exists($stgPidPath)) {
        $stgPidAlert=__('Stargazer currently not running. We strongly advise against trying to use Ubilling in this case. If you are absolutely sure of what you are doing - you can turn off this alert with the option NOSTGCHECKPID');
        
        die($stgPidAlert);
    } 
}


// Loading main module
$system->setCurrentPoint('__MAIN__');
if(!empty($_GET['module'])) $module = basename($_GET['module']); else $module = 'index';
if(!empty($system->modules['main'][$module])) include_once(MODULES_PATH . $module . '/index.php');

// Load menu modules
include_once(CUR_SKIN_PATH . 'skin.php');
if(!empty($menu_points)){
   	foreach($menu_points as $point => $menus){
       	$system->setCurrentPoint($point);
       	if(!empty($menus) && isset($skin['menu_point'][$point])){
           	foreach ($menus as $menu){
               	if(substr($menu, 0, 4) == 'ucm:' && is_readable(DF_PATH . substr($menu, 4) . '.ucm')) {
                   	$file = file(DF_PATH . substr($menu, 4) . '.ucm');
                   	$title = preg_replace("/[\n\r]+/", '', $file[0]);
                   	$align = preg_replace("/[\n\r]+/", '', $file[1]);
                   	unset($file[0]);
                   	unset($file[1]);
                   	show_window($title, implode('', $file), $align);
               	} elseif (!empty($system->modules['menu'][$menu])){
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
$xhprof_data = xhprof_disable();
$xhprof_runs = new XHProfRuns_Default();
$xhprof_run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_ubilling");
$xhprof_link = wf_modal('XHPROF', 'XHPROF DEBUG DATA', '<iframe src="xhprof/xhprof_html/index.php?run='.$xhprof_run_id.'&source=xhprof_ubilling" width="100%" height="750"></iframe>', '', '1024', '768');
}
// Start output
require_once(CUR_SKIN_PATH . 'skin.general.php');
 
?>
