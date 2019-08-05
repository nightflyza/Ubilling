<?php

/*
 * users data cache rebuild for external scripts
 */
if ($_GET['action'] == 'rebuilduserdatacache') {
    $cacheAddressArr = zb_AddressGetFulladdresslist();
    $cacheAddressArr = serialize($cacheAddressArr);
    $cacheIpsArr = zb_UserGetAllIPs();
    $cacheIpsArr = serialize($cacheIpsArr);
    $cacheMacArr = zb_UserGetAllIpMACs();
    $cacheMacArr = serialize($cacheMacArr);
    file_put_contents('exports/cache_address', $cacheAddressArr);
    file_put_contents('exports/cache_ips', $cacheIpsArr);
    file_put_contents('exports/cache_mac', $cacheMacArr);
    die('OK:REBUILDUSERDATACACHE');
}