<?php

if (ubRouting::get('action') == 'cachedog') {
    $cachedUsersData = zb_UserGetAllDataCache();
    $currentUsersData = zb_UserGetAllData();
    if ($cachedUsersData != $currentUsersData) {
        zb_UserGetAllDataCacheClean();
        if (@$alterconf['ONLINE_HP_MODE'] == 2) {
            //invalidate online module ajax source
            $cache = new UbillingCache();
            $cache->delete('HPONLINEJSON');
        }
        die('CACHEDOG:CACHE_CLEANED');
    } else {
        die('CACHEDOG:CACHE_OK');
    }
}