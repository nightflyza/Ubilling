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
////////////////////////////////////////////////////////////////////////////////
// Ban check function                                                         //
////////////////////////////////////////////////////////////////////////////////
function ifbanned($ip) {
    if (!$banlist = @file(CONFIG_PATH . 'bans.ini')) {
        $banlist = array();
    }
    foreach ($banlist as $banstring) {
        $ban = '/^' . str_replace('*', '(\d*)', str_replace('.', '\\.', trim($banstring))) . '$/';
        if (preg_match($ban, $ip)) {
            return true;
        }
    }
    return false;
}

////////////////////////////////////////////////////////////////////////////////
// Ban check                                                                  //
////////////////////////////////////////////////////////////////////////////////
if (ifbanned($_SERVER['REMOTE_ADDR'])) {
    rcms_log_put('Notification', $this->user['username'], 'Attempt to access from banned IP');
    die('You are banned from this site');
}

// UMASK Must be 000!
umask(000);

////////////////////////////////////////////////////////////////////////////////
// Loading system libraries                                                   //
////////////////////////////////////////////////////////////////////////////////
include_once(SYSTEM_MODULES_PATH . 'filesystem.php');
include_once(SYSTEM_MODULES_PATH . 'etc.php');
include_once(SYSTEM_MODULES_PATH . 'templates.php');
include_once(SYSTEM_MODULES_PATH . 'user-classes.php');
include_once(SYSTEM_MODULES_PATH . 'tar.php');
include_once(SYSTEM_MODULES_PATH . 'system.php');
include_once(SYSTEM_MODULES_PATH . 'compatibility.php');
include_once(SYSTEM_MODULES_PATH . 'formsgen.php');

////////////////////////////////////////////////////////////////////////////////
// Initializing session                                                       //
////////////////////////////////////////////////////////////////////////////////
$language = empty($_POST['lang_form']) ? '' : $_POST['lang_form'];
$skin = empty($_POST['user_selected_skin']) ? [] : $_POST['user_selected_skin'];
$system = new rcms_system($language, $skin);
if (!empty($_POST['login_form'])) {
    $system->logInUser(@$_POST['username'], @$_POST['password'], !empty($_POST['remember']) ? true : false);
}
if (!empty($_POST['logout_form'])) {
    $system->logOutUser();
    rcms_redirect('index.php', true);
}
//additional get-request user auto logout sub
if (!empty($_GET['idleTimerAutoLogout'])) {
    $system->logOutUser();
    rcms_redirect('index.php', true);
}

//normal get-request user logout
if (!empty($_GET['forceLogout'])) {
    $system->logOutUser();
    rcms_redirect('index.php', true);
}

define('LOGGED_IN', $system->logged_in);

// Show some messages about activation or initialization
if (!empty($system->results['user_init']))
    show_window('', $system->results['user_init'], 'center');
?>