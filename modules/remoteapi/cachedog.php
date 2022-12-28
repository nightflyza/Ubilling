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
        if (@$alterconf['USERBYNUM_ENABLED'] OR @$alterconf['TELEPONY_ENABLED']) {
            $cache = new UbillingCache();
            $cache->delete('PHONEDATA');
            $cache->delete('EXTMOBILES');
            $cache->delete('PHONETELEPATHY');
        }
        die('CACHEDOG:CACHE_CLEANED');
    } else {
        die('CACHEDOG:CACHE_OK');
    }
}