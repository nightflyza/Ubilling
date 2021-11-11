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
 * @global object $ubillingConfig
 * @param string $email  user email
 * @param int $size   user avatar size
 * 
 * @return string
 */
function gravatar_GetAvatar($email, $size = '') {
    global $ubillingConfig;
    $cachePath = DATA_PATH . 'avatars/';
    $gravatarOption = $ubillingConfig->getAlterParam('GRAVATAR_DEFAULT');
    $gravatarCacheTime = $ubillingConfig->getAlterParam('GRAVATAR_CACHETIME');
    $getsize = ($size != '') ? '?s=' . $size : '';
    //option not set
    if (!$gravatarOption) {
        $gravatarOption = 'monsterid';
    }

    $url = gravatar_GetUrl($email);
    $fullUrl = $url . $getsize . '&d=' . $gravatarOption;


    //avatar caching to local FS.
    if ($gravatarCacheTime) {
        $cacheTime = time() - ($gravatarCacheTime * 86400); //Expire time. Option in days.
        $avatarHash = md5($fullUrl) . '.jpg';
        $fullCachedPath = $cachePath . $avatarHash;
        $updateCache = true;
        if (file_exists($fullCachedPath)) {
            $updateCache = false;
            if ((filemtime($fullCachedPath) > $cacheTime)) {
                $updateCache = false;
            } else {
                $updateCache = true;
            }
        } else {
            $updateCache = true;
        }

        if ($updateCache) {
            $gravatarApi = new OmaeUrl($fullUrl);
            $remoteAvatar = $gravatarApi->response();
            if (!empty($remoteAvatar)) {
                file_put_contents($fullCachedPath, $remoteAvatar);
            }
        }

        $fullUrl = $fullCachedPath;
    }

    $result = wf_img($fullUrl);
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
