<?php
////////////////////////////////////////////////////////////////////////////////
//   Copyright (C) ReloadCMS Development Team                                 //
//   http://reloadcms.sf.net                                                  //
//                                                                            //
//   This program is distributed in the hope that it will be useful,          //
//   but WITHOUT ANY WARRANTY, without even the implied warranty of           //
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     //
//                                                                            //
//   This product released under GNU General Public License v2                //
////////////////////////////////////////////////////////////////////////////////

define('RCMS_ROOT_PATH', './');
require_once(RCMS_ROOT_PATH . 'common.php');
require_once(SYSTEM_MODULES_PATH . 'rss.php');

if(!empty($_GET['m']) &&  !empty($system->config['enable_rss']) && !empty($system->feeds[$_GET['m']])){
	$module = $_GET['m'];
	header('Content-Type: text/xml');
	$feed = new rss_feed($system->config['title'] . ' - ' . $system->feeds[$module][0], $system->url, $system->feeds[$module][1], $system->config['encoding'], $system->config['language'], $system->config['copyright']);
	$m = (!empty($system->feeds[$module][2])) ? $system->feeds[$module][2] : $module;
	if(is_readable(MODULES_PATH . $m . '/rss.php')) include(MODULES_PATH . $m . '/rss.php');
	$feed->showFeed();
}
?>