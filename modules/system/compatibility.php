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

function user_check_right($username, $right){
    global $system;
    return $system->checkForRight($right, $username);
}

function user_set_rights($username, $root, $rights){
    global $system;
    if($userdata = load_user_info($username)){
        return $system->setRightsForUser($username, $rights, $root, (int)@$userdata['accesslevel']);
    } else {
        return false;
    }
}

function load_user_info($username){
    global $system;
    return $system->getUserData($username);
}

function user_get_list($expr = '*'){
    global $system;
    return $system->getUserList($expr);
}

function user_change_field($username, $field, $value){
    global $system;
    return $system->changeProfileField($username, $field, $value);
}

function user_delete($username){
    global $system;
    return $system->deleteUser($username);
}

function user_register_in_cache($username, $usernick, $email, &$cache){
    global $system;
    $cache = &$system->users_cache;
    return $cache->registerUser($username, $usernick, $email);
}

function user_remove_from_cache($username, &$cache){
    global $system;
    $cache = &$system->users_cache;
    return $cache->removeUser($username);
}

function user_check_nick_in_cache($username, $usernick, &$cache){
    global $system;
    $cache = &$system->users_cache;
    return $cache->checkField('nicks', $usernick);
}

function user_check_email_in_cache($username, $email, &$cache){
    global $system;
    $cache = &$system->users_cache;
    return $cache->checkField('mails', $email);
}

function user_create_link($user, $nick, $target = ''){
    global $system;
    return $system->createLink($user, $nick, $target = '');
}

function show_window($title, $data, $align = 'left'){
    global $system;
    return $system->defineWindow($title, $data, $align);
}

function show_error($data){
    return show_window('', $data, 'center');
}
?>