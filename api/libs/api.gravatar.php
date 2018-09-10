<?php

/**
 * Gravatar API
 */

/**
 * Get gravatar url by some email
 * 
 * @param string $email  user email
 * @return string
 */
function gravatar_GetUrl($email) {
    $hash = strtolower($email);
    $hash = md5($hash);
    $proto = 'http://gravatar.com/avatar/';
    $result = $proto . $hash;
    return ($result);
}

/**
 * Function that shows avatar by user email
 * 
 * @param string $email  user email
 * @param int $size   user avatar size
 * @return string
 */
function gravatar_GetAvatar($email, $size = '') {
    $altercfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
    $getsize = ($size != '') ? '?s=' . $size : '';
    if (isset($altercfg['GRAVATAR_DEFAULT'])) {
        $default = $altercfg['GRAVATAR_DEFAULT'];
    } else {
        $default = 'monsterid';
    }

    $url = gravatar_GetUrl($email);
    $result = wf_img(($url . $getsize . '&d=' . $default));
    return ($result);
}

/**
 * Get framework user email
 * 
 * @param string $username rcms user login
 * @return string
 */
function gravatar_GetUserEmail($username) {
    $storePath = DATA_PATH . "users/";
    if (file_exists($storePath . $username)) {
        $userContent = file_get_contents($storePath . $username);
        $userData = unserialize($userContent);
        $result = $userData['email'];
    } else {
        $result = '';
    }
    return ($result);
}

/**
 * Shows avatar for some framework user - use only this in production!
 * 
 * @param string $username rcms user login
 * @param int    $size - size of returning avatar
 * @return string
 */
function gravatar_ShowAdminAvatar($username, $size = '') {
    $adminEmail = gravatar_GetUserEmail($username);
    if ($adminEmail) {
        $result = gravatar_GetAvatar($adminEmail, $size);
    } else {
        $result = wf_img('skins/admava.png');
    }
    return ($result);
}

?>
