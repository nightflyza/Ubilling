<?php

/**
 * Gravatar API
 */

/**
 * Get gravatar URL by some email
 * 
 * @param string $email user email
 * @param bool $secure use HTTPS for API interraction?
 * 
 * @return string
 */
function gravatar_GetUrl($email, $secure = false) {
    $hash = strtolower($email);
    $hash = md5($hash);
    $proto = ($secure) ? 'https' : 'http';
    $baseUrl = 'gravatar.com/avatar/';
    $result = $proto . '://' . $baseUrl . $hash;
    return ($result);
}

/**
 * Function that returns avatar code by user email
 * 
 * @global object $ubillingConfig
 * @param string $email  user email
 * @param int $size   user avatar size
 * @param string $class custom image class
 * 
 * @return string
 */
function gravatar_GetAvatar($email, $size = '64', $class = '') {
    global $ubillingConfig;
    $cachePath = DATA_PATH . 'avatars/';
    $gravatarOption = $ubillingConfig->getAlterParam('GRAVATAR_DEFAULT');
    $gravatarCacheTime = $ubillingConfig->getAlterParam('GRAVATAR_CACHETIME');
    $getsize = ($size) ? '&s=' . $size : '';
    //option not set
    if (!$gravatarOption) {
        $gravatarOption = 'monsterid';
    }

    $useSSL = ($gravatarCacheTime) ? false : true; //avoid mixed content issues on disabled caching cases
    $url = gravatar_GetUrl($email, $useSSL);
    $fullUrl = $url . '?d=' . $gravatarOption . $getsize;

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

    $result = wf_tag('img', false, $class, 'src="' . $fullUrl . '"');
    return ($result);
}

/**
 * Get framework user email
 * 
 * @param string $username rcms user login
 * 
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
 * 
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
