<?php

if (ubRouting::get('action') == 'cachedog') {
    $cachedUsersData = zb_UserGetAllDataCache();
    $currentUsersData = zb_UserGetAllData();
    if ($cachedUsersData != $currentUsersData) {
        zb_UserGetAllDataCacheClean();
        die('CACHEDOG:CACHE_CLEANED');
    } else {
        die('CACHEDOG:CACHE_OK');
    }
}