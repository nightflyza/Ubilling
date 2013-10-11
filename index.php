<?php
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

// Start output
require_once(CUR_SKIN_PATH . 'skin.general.php');


?>