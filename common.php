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
error_reporting(E_ALL);

// Unset any globals created by register_globals being turned ON
foreach ($GLOBALS as $key => $global) {
    if (!preg_match('/^(_POST|_GET|_COOKIE|_SERVER|_FILES|GLOBALS|HTTP.*|_REQUEST)$/', $key)) {
        unset($$key);
    }
}
unset($global);

////////////////////////////////////////////////////////////////////////////////
// Defining constants                                                         //
////////////////////////////////////////////////////////////////////////////////
define('RCMS_VERSION_A', '1');
define('RCMS_VERSION_B', '2');
define('RCMS_VERSION_C', '21');
if (!defined('RCMS_ROOT_PATH')) {
    die('Even though I walk through the darkest valley, I will fear no evil, for you are with me; your rod and your staff, they comfort me.'); //23:4
}
if (is_file(RCMS_ROOT_PATH . 'CURRENT')) {
    define('RCMS_VERSION_SUFFIX', '-git');
} else {
    define('RCMS_VERSION_SUFFIX', '');
}

define('RCMS_COPYRIGHT', '&copy; ' . date("Y"));
define('RCMS_POWERED', 'RCMS Framework');

// Main paths
define('SYSTEM_MODULES_PATH', RCMS_ROOT_PATH . 'modules/system/');
define('ENGINE_PATH', RCMS_ROOT_PATH . 'modules/engine/');
define('MODULES_PATH', RCMS_ROOT_PATH . 'modules/general/');
define('REMOTEAPI_PATH', RCMS_ROOT_PATH . 'modules/remoteapi/');
define('MODULES_TPL_PATH', RCMS_ROOT_PATH . 'modules/templates/');
define('MODULES_DOWNLOADABLE', RCMS_ROOT_PATH . 'modules/downloadable/');
define('CONFIG_PATH', RCMS_ROOT_PATH . 'config/');
define('LANG_PATH', RCMS_ROOT_PATH . 'languages/');
define('ADMIN_PATH', RCMS_ROOT_PATH . 'admin/');
define('SKIN_PATH', RCMS_ROOT_PATH . 'skins/');
define('SMILES_PATH', SKIN_PATH . 'smiles/');
define('BACKUP_PATH', RCMS_ROOT_PATH . 'backups/');

// Content paths
define('DATA_PATH', RCMS_ROOT_PATH . 'content/');
define('RATE_PATH', DATA_PATH . 'rate/');
define('DF_PATH', DATA_PATH . 'datafiles/');
define('USERS_PATH', DATA_PATH . 'users/');
define('FILES_PATH', DATA_PATH . 'uploads/');
define('GALLERY_PATH', DATA_PATH . 'gallery/');
define('FORUM_PATH', DATA_PATH . 'forum/');
define('LOGS_PATH', DATA_PATH . 'logs/');
define('IPACLALLOWIP_PATH', DATA_PATH . 'documents/ipacl/ip/');
define('IPACLALLOWNETS_PATH', DATA_PATH . 'documents/ipacl/nets/');

// Cookies
define('FOREVER_COOKIE', time() + 3600 * 24 * 365 * 5);

define('IGNORE_LOCK_FILES', false);


////////////////////////////////////////////////////////////////////////////////
// Loading modules                                                            //
////////////////////////////////////////////////////////////////////////////////
include_once(SYSTEM_MODULES_PATH . 'load.php');


if (empty($_SERVER['REQUEST_URI']))
    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
if (empty($_SERVER['REMOTE_ADDR']))
    $_SERVER['REMOTE_ADDR'] = '0.0.0.0';
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    $_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
if (empty($_SERVER['REMOTE_HOST']))
    $_SERVER['REMOTE_HOST'] = $_SERVER['REMOTE_ADDR'];
if (empty($_SERVER['HTTP_REFERER']))
    $_SERVER['HTTP_REFERER'] = '';
if (empty($_SERVER['HTTP_USER_AGENT']))
    $_SERVER['HTTP_USER_AGENT'] = '';

////////////////////////////////////////////////////////////////////////////////
// Loading modules                                                            //
////////////////////////////////////////////////////////////////////////////////
include("api/apiloader.php");
$em_dir = opendir(ENGINE_PATH);
while ($em = readdir($em_dir)) {
    if (substr($em, 0, 1) != '.' && is_file(ENGINE_PATH . $em)) {
        include_once(ENGINE_PATH . $em);
    }
}
closedir($em_dir);
?>
