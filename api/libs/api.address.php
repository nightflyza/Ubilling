<?php

/*
 * Address management API
 */

/**
 * flushes address cache - we need use this on address creation/deletion/change
 * 
 * @return void
 */
function zb_AddressCleanAddressCache() {
    $cache = new UbillingCache();
    $cache->delete('FULLADDRESSLISTCACHE');
    $cache->delete('ADDRESS_EXTEN_CACHE');
}

/**
 * Creates new city in database
 * 
 * @param string $cityname
 * @param string $cityalias
 * 
 * @return void|string
 */
function zb_AddressCreateCity($cityname, $cityalias) {
    $result = '';
    $cityname = trim($cityname);
    $cityname = ubRouting::filters($cityname, 'safe');
    $citynameF = ubRouting::filters($cityname, 'mres');
    if (!empty($citynameF)) {
        $cityalias = ubRouting::filters($cityalias, 'gigasafe');
        $query = "INSERT INTO `city` (`id`,`cityname`,`cityalias`) VALUES (NULL, '" . $citynameF . "','" . $cityalias . "'); ";
        nr_query($query);
        $newCityId =simple_get_lastid('city');
        log_register('CITY CREATE [' . $newCityId . '] `' . $cityname . '` `' . $cityalias . '`');
        zb_AddressCleanAddressCache();
        zb_UserGetAllDataCacheClean();
    } else {
        $result .= __('All fields marked with an asterisk are mandatory');
    }
    return ($result);
}

/**
 * Deletes city from database by its ID
 * 
 * @param int $cityid
 * 
 * @return void
 */
function zb_AddressDeleteCity($cityid) {
    $cityid = vf($cityid, 3);
    $query = "DELETE from `city` WHERE `id` = '" . $cityid . "';";
    nr_query($query);
    log_register('CITY DELETE [' . $cityid . ']');
    zb_AddressCleanAddressCache();
    zb_UserGetAllDataCacheClean();
}

/**
 * Changes city name in database
 * 
 * @param int $cityid
 * @param string $cityname
 * 
 * @return void|string
 */
function zb_AddressChangeCityName($cityid, $cityname) {
    $result = '';
    $cityid = ubRouting::filters($cityid, 'int');
    $cityname = trim($cityname);
    $cityname = ubRouting::filters($cityname, 'safe');
    $citynameF = ubRouting::filters($cityname, 'mres');
    if (!empty($citynameF)) {
        $query = "UPDATE `city` SET `cityname` = '" . $citynameF . "' WHERE `id`= '" . $cityid . "' ;";
        nr_query($query);
        log_register('CITY CHANGE NAME [' . $cityid . '] `' . $cityname . '`');
        zb_AddressCleanAddressCache();
        zb_UserGetAllDataCacheClean();
    } else {
        $result .= __('All fields marked with an asterisk are mandatory');
    }
    return ($result);
}

/**
 * Changes city alias by its ID
 * 
 * @param int $cityid
 * @param string $cityalias
 * 
 * @return void
 */
function zb_AddressChangeCityAlias($cityid, $cityalias) {
    $cityid = ubRouting::filters($cityid, 'int');
    $cityalias = ubRouting::filters($cityalias, 'gigasafe');
    $query = "UPDATE `city` SET `cityalias` = '" . $cityalias . "' WHERE `id`= '" . $cityid . "' ;";
    nr_query($query);
    log_register('CITY CHANGE ALIAS [' . $cityid . '] `' . $cityalias . '`');
}

/**
 * Returns city data from DB by its ID
 * 
 * @param int $cityid
 * 
 * @return array
 */
function zb_AddressGetCityData($cityid) {
    $cityid = vf($cityid, 3);
    $query = "SELECT * from `city` WHERE `id`='" . $cityid . "'";
    $city_data = simple_query($query);
    return ($city_data);
}

/**
 * Returns all available cities IDs from database
 * 
 * @return array
 */
function zb_AddressListCityAllIds() {
    $query = "SELECT `id` from `city`";
    $all_ids = simple_queryall($query);
    return ($all_ids);
}

/**
 * Returns all available cities full data
 *
 * @param int $FilterByCityId
 *
 * @return array
 */
function zb_AddressGetCityAllData($FilterByCityId = 0) {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $order = (isset($altCfg['CITY_ORDER'])) ? $altCfg['CITY_ORDER'] : 'default';
    $validStates = array('name', 'namerev', 'id', 'idrev', 'alias', 'aliasrev', 'default');
    $validStates = array_flip($validStates);
    if ((isset($validStates[$order])) and ($order != 'default')) {
        switch ($order) {
            case 'name':
                $sqlOrder = "ORDER by `cityname` ASC";
                break;
            case 'namerev':
                $sqlOrder = "ORDER by `cityname` DESC";
                break;
            case 'id':
                $sqlOrder = "ORDER by `id` ASC";
                break;
            case 'idrev':
                $sqlOrder = "ORDER by `id` DESC";
                break;
            case 'alias':
                $sqlOrder = "ORDER by `cityalias` ASC";
                break;
            case 'aliasrev':
                $sqlOrder = "ORDER by `cityalias` DESC";
                break;
            case 'default':
                $sqlOrder = "ORDER by `id` ASC";
                break;
        }
    } else {
        $sqlOrder = "ORDER by `id` ASC";
    }

    if (empty($FilterByCityId)) {
        $WREREString = '';
    } else {
        $WREREString = "WHERE `id` = '" . $FilterByCityId . "' ";
    }

    $query = "SELECT * from `city` " . $WREREString . $sqlOrder;

    $all_data = simple_queryall($query);
    return ($all_data);
}

/**
 * Returns all available cities names into id=>name
 * 
 * @return array
 */
function zb_AddressGetFullCityNames() {
    $query = "SELECT * from `city`";
    $result = array();
    $all_data = simple_queryall($query);
    if (!empty($all_data)) {
        foreach ($all_data as $io => $eachcity) {
            $result[$eachcity['id']] = $eachcity['cityname'];
        }
    }

    return ($result);
}

/**
 * Creates new street in database
 * 
 * @param int $cityid
 * @param string $streetname
 * @param string $streetalias
 * 
 * @return void
 */
function zb_AddressCreateStreet($cityid, $streetname, $streetalias) {
    $streetname = trim($streetname);
    $streetname = ubRouting::filters($streetname, 'safe');
    $streetnameF = ubRouting::filters($streetname, 'mres');
    $streetalias = ubRouting::filters($streetalias, 'gigasafe');
    $cityid = ubRouting::filters($cityid, 'int');

    $query = "INSERT INTO `street` (`id`,`cityid`,`streetname`,`streetalias`) VALUES  (NULL, '" . $cityid . "','" . $streetnameF . "','" . $streetalias . "');";
    nr_query($query);
    $newStreetId =simple_get_lastid('street');
    log_register('STREET CREATE CITYID [' . $cityid . '] STREETID [' . $newStreetId . '] `' . $streetname . '` `' . $streetalias . '`');
    zb_AddressCleanAddressCache();
    zb_UserGetAllDataCacheClean();
}

/**
 * Deletes street from database by its ID
 * 
 * @param int $streetid
 * 
 * @return void
 */
function zb_AddressDeleteStreet($streetid) {
    $streetid = vf($streetid, 3);
    $query = "DELETE from `street` WHERE `id` = '" . $streetid . "';";
    nr_query($query);
    log_register('STREET DELETE [' . $streetid . ']');
    zb_AddressCleanAddressCache();
    zb_UserGetAllDataCacheClean();
}

/**
 * Changes street name in database by its ID
 * 
 * @param int $streetid
 * @param string $streetname
 * 
 * @return void
 */
function zb_AddressChangeStreetName($streetid, $streetname) {
    $streetid = ubRouting::filters($streetid, 'int');
    $streetname = trim($streetname);
    $streetname = ubRouting::filters($streetname, 'safe');
    $streetnameF = ubRouting::filters($streetname, 'mres');

    $query = "UPDATE `street` SET `streetname` = '" . $streetnameF . "' WHERE `id`= '" . $streetid . "' ;";
    nr_query($query);
    log_register('STREET CHANGE NAME [' . $streetid . '] `' . $streetname . '`');
    zb_AddressCleanAddressCache();
    zb_UserGetAllDataCacheClean();
}

/**
 * Changes street alias in database
 * 
 * @param int $streetid
 * @param string $streetalias
 * 
 * @return void
 */
function zb_AddressChangeStreetAlias($streetid, $streetalias) {
    $streetid = ubRouting::filters($streetid, 'int');
    $streetalias = trim($streetalias);
    $streetalias = ubRouting::filters($streetalias, 'gigasafe');
    $streetaliasF = ubRouting::filters($streetalias, 'mres');

    $query = "UPDATE `street` SET `streetalias` = '" . $streetaliasF . "' WHERE `id`= '" . $streetid . "' ;";
    nr_query($query);
    log_register('STREET CHANGE ALIAS [' . $streetid . '] `' . $streetalias . '`');
}

/**
 * Returns full street data by its ID
 * 
 * @param int $streetid
 * 
 * @return array
 */
function zb_AddressGetStreetData($streetid) {
    $streetid = vf($streetid, 3);
    $query = "SELECT * from `street` WHERE `id`='" . $streetid . "'";
    $street_data = simple_query($query);
    return ($street_data);
}

/**
 * Returns all available streets IDs from database
 * 
 * @return array
 */
function zb_AddressListStreetAllIds() {
    $query = "SELECT `id` from `street`";
    $all_ids = simple_queryall($query);
    return ($all_ids);
}

/**
 * Returns all data of available streets
 * 
 * @return array
 */
function zb_AddressGetStreetAllData() {
    $query = "SELECT * from `street`";
    $all_data = simple_queryall($query);
    return ($all_data);
}

/**
 * Returns all streets data as id=>streetdata
 * 
 * @param string $queryParams
 * 
 * @return array
 */
function zb_AddressGetStreetsDataAssoc($queryParams = '') {
    $result = array();
    $query = "SELECT * from `street`";
    if ($queryParams) {
        $query .= ' ' . $queryParams;
    }
    $allData = simple_queryall($query);
    if (!empty($allData)) {
        foreach ($allData as $io => $each) {
            $result[$each['id']] = $each;
        }
    }
    return ($result);
}

/**
 * Returns all streets data assigned with some city, by the city ID
 * 
 * @param int $cityid
 * 
 * @return array
 */
function zb_AddressGetStreetAllDataByCity($cityid) {
    $cityid = vf($cityid, 3);
    $query = "SELECT * from `street` where `cityid`='" . $cityid . "' ORDER BY `streetname`";
    $all_data = simple_queryall($query);
    return ($all_data);
}

/**
 * Creates new build assigned with some street in database
 * 
 * @param int $streetid
 * @param string $buildnum
 * 
 * @return void
 */
function zb_AddressCreateBuild($streetid, $buildnum) {
    $buildnum = mysql_real_escape_string($buildnum);
    $streetid = vf($streetid, 3);
    $query = "INSERT INTO `build` (`id`,`streetid`,`buildnum`) VALUES (NULL, '" . $streetid . "','" . $buildnum . "');";
    nr_query($query);
    $newBuildId =simple_get_lastid('build');
    log_register('BUILD CREATE STREETID [' . $streetid . '] BUILDID [' . $newBuildId . '] NUM `' . $buildnum . '`');
    zb_AddressCleanAddressCache();
    zb_UserGetAllDataCacheClean();
}

/**
 * Deletes some build from database
 * 
 * @param int $buildid
 * 
 * @return void
 */
function zb_AddressDeleteBuild($buildid) {
    $buildid = vf($buildid, 3);
    $query = "DELETE from `build` WHERE `id` = '" . $buildid . "';";
    nr_query($query);
    log_register('BUILD DELETE [' . $buildid . ']');
    zb_AddressCleanAddressCache();
    zb_UserGetAllDataCacheClean();
}

/**
 * Checks is build protected from deletion?
 * 
 * @param int $buildid
 * 
 * @return bool
 */
function zb_AddressBuildProtected($buildid) {
    $buildid = vf($buildid, 3);
    $query = "SELECT * from `apt` WHERE `buildid`='" . $buildid . "'";
    $result = simple_queryall($query);
    if (!empty($result)) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * Checks is street protected from deletion?
 * 
 * @param int $streetid
 * 
 * @return bool
 */
function zb_AddressStreetProtected($streetid) {
    $streetid = vf($streetid, 3);
    $query = "SELECT * from `build` WHERE `streetid`='" . $streetid . "'";
    $result = simple_queryall($query);
    if (!empty($result)) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * Checks is city protected from deletion (has any streets)?
 * 
 * @param int $cityid
 * 
 * @return bool
 */
function zb_AddressCityProtected($cityid) {
    $cityid = vf($cityid, 3);
    $query = "SELECT * from `street` WHERE `cityid`='" . $cityid . "'";
    $result = simple_queryall($query);
    if (!empty($result)) {
        return (true);
    } else {
        return (false);
    }
}

/**
 * Changes build number in database
 * 
 * @param int $buildid
 * @param string $buildnum
 * 
 * @return void
 */
function zb_AddressChangeBuildNum($buildid, $buildnum) {
    $buildid = vf($buildid, 3);
    $buildnum = mysql_real_escape_string($buildnum);
    $query = "UPDATE `build` SET `buildnum` = '" . $buildnum . "' WHERE `id`= '" . $buildid . "' ;";
    nr_query($query);
    log_register('BUILD CHANGE NUM [' . $buildid . '] `' . $buildnum . '`');
    zb_AddressCleanAddressCache();
    zb_UserGetAllDataCacheClean();
}

/**
 * Changes some exising build geo location
 * 
 * @param int $buildId
 * @param string $geo
 * 
 * @return void
 */
function zb_AddressChangeBuildGeo($buildId, $geo) {
    $buildId = ubRouting::filters($buildId, 'int');
    $buildGeo = ubRouting::filters($geo, 'mres');
    $buildGeo = preg_replace('/[^0-9\.,]/i', '', $buildGeo);
    simple_update_field('build', 'geo', $buildGeo, "WHERE `id`='" . $buildId . "'");
    log_register('BUILD CHANGE GEO [' . $buildId . ']  `' . $buildGeo . '`');
}

/**
 * Returns build data by its ID
 * 
 * @param int $buildid
 * 
 * @return array
 */
function zb_AddressGetBuildData($buildid) {
    $buildid = vf($buildid, 3);
    $query = "SELECT * from `build` WHERE `id`='" . $buildid . "'";
    $build_data = simple_query($query);
    return ($build_data);
}

/**
 * Returns all of available builds IDs
 * 
 * @return array
 */
function zb_AddressListBuildAllIds() {
    $query = "SELECT `id` from `build`";
    $all_ids = simple_queryall($query);
    return ($all_ids);
}

/**
 * Returns all available builds data from database
 * 
 * @return array
 */
function zb_AddressGetBuildAllData() {
    $query = "SELECT * from `build`";
    $all_data = simple_queryall($query);
    return ($all_data);
}

/**
 * Returns all available builds data from database as id=>buildData
 * 
 * @return array
 */
function zb_AddressGetBuildAllDataAssoc() {
    $result = array();
    $query = "SELECT * from `build`";
    $allData = simple_queryall($query);
    if (!empty($allData)) {
        foreach ($allData as $io => $each) {
            $result[$each['id']] = $each;
        }
    }
    return ($result);
}

/**
 * Returns all streets + builds array
 * 
 * @return array
 */
function zb_AddressGetStreetsWithBuilds() {
    $result = array();
    $rawAddress = zb_AddressGetFulladdresslistCached();
    if (!empty($rawAddress)) {
        foreach ($rawAddress as $io => $each) {
            $cleanBuild = explode('/', $each);
            $cleanBuild = $cleanBuild[0];
            $result[$cleanBuild] = $cleanBuild;
        }
    }
    return ($result);
}

/**
 * Returns all streets available array as streetname=>streetname
 * 
 * @return array
 */
function zb_AddressGetStreets() {
    $result = array();
    $rawStreets = zb_AddressGetStreetAllData();
    if (!empty($rawStreets)) {
        foreach ($rawStreets as $io => $each) {
            $result[$each['streetname']] = $each['streetname'];
        }
    }
    return ($result);
}

/**
 * Returns all builds data by some street, naturally ordered by build number
 * 
 * @param int $streetid
 * @return array
 */
function zb_AddressGetBuildAllDataByStreet($streetid) {
    $streetid = vf($streetid, 3);
    $query = "SELECT * from `build` where `streetid`='" . $streetid . "' ORDER by `buildnum`+0 ASC";
    $all_data = simple_queryall($query);
    return ($all_data);
}

/**
 * Returns all apartments data from database
 * 
 * @return array
 */
function zb_AddressGetAptAllData() {
    $query = "SELECT * from `apt`";
    $all_data = simple_queryall($query);
    return ($all_data);
}

/**
 * Returns all apartments data from some build
 * 
 * @param int $buildid
 * @return array
 */
function zb_AddressGetAptAllDataByBuild($buildid) {
    $buildid = vf($buildid, 3);
    $query = "SELECT * from `apt` where `buildid`='" . $buildid . "' ORDER by `apt`+0 ASC";
    $all_data = simple_queryall($query);
    return ($all_data);
}

/**
 * Returns user address by some user login
 * 
 * @param string $login
 * 
 * @return string
 */
function zb_UserGetFullAddress($login) {
    $alladdress = zb_AddressGetFulladdresslistCached();
    @$address = $alladdress[$login];
    return ($address);
}

/**
 * Creates apartment in some build
 * 
 * @param int $buildid
 * @param string $entrance
 * @param string $floor
 * @param string $apt
 * 
 * @return void
 */
function zb_AddressCreateApartment($buildid, $entrance, $floor, $apt) {
    $buildid = vf($buildid, 3);
    $entrance = mysql_real_escape_string($entrance);
    $floor = mysql_real_escape_string($floor);
    $apt = mysql_real_escape_string($apt);
    $query = "INSERT INTO `apt`
         (`id`,`buildid`,`entrance`,`floor`,`apt`)
         VALUES
         (NULL,'" . $buildid . "','" . $entrance . "','" . $floor . "','" . $apt . "');
        ";
    nr_query($query);
    $newAptId =simple_get_lastid('apt');
    log_register('APARTMENT CREATE BUILDID [' . $buildid . '] APTID [' . $newAptId . '] `' . $entrance . '` `' . $floor . '` `' . $apt . '`');
    zb_AddressCleanAddressCache();
    zb_UserGetAllDataCacheClean();
}

/**
 * Deletes apartment from database by its ID
 * 
 * @param int $aptid
 * 
 * @return void
 */
function zb_AddressDeleteApartment($aptid) {
    $aptid = vf($aptid, 3);
    $query = "DELETE from `apt` WHERE `id` = '" . $aptid . "';";
    nr_query($query);
    log_register('APARTMENT DELETE [' . $aptid . ']');
    zb_AddressCleanAddressCache();
    zb_UserGetAllDataCacheClean();
}

/**
 * Changes apartment data in database 
 * 
 * @param int $aptid
 * @param int $buildid
 * @param string $entrance
 * @param string $floor
 * @param string $apt
 * 
 * @return void
 */
function zb_AddressChangeApartment($aptid, $buildid, $entrance, $floor, $apt) {
    $aptid = vf($aptid, 3);
    $buildid = vf($buildid, 3);
    $entrance = mysql_real_escape_string($entrance);
    $floor = mysql_real_escape_string($floor);
    $apt = mysql_real_escape_string($apt);
    $query = "
        UPDATE `apt`
        SET
        `buildid` = '" . $buildid . "',
        `entrance` = '" . $entrance . "',
        `floor` = '" . $floor . "',
        `apt` = '" . $apt . "'
        WHERE `id` ='" . $aptid . "';
        ";
    nr_query($query);
    log_register('APARTMENT CHANGE [' . $aptid . '] [' . $buildid . '] `' . $entrance . '` `' . $floor . '` `' . $apt . '`');
    zb_AddressCleanAddressCache();
    zb_UserGetAllDataCacheClean();
}

/**
 * Assigns some Ubilling user login with some apartment (creating address field)
 * 
 * @param string $login
 * @param int    $aptid
 * 
 * @return void
 */
function zb_AddressCreateAddress($login, $aptid) {
    // zaebis notacia - da? :) ^^^^

    $login = vf($login);
    $aptid = vf($aptid, 3);
    $query = "
    INSERT INTO `address`
    (`id`,`login`,`aptid`)
    VALUES
    (NULL, '" . $login . "','" . $aptid . "');
    ";
    nr_query($query);
    log_register('ADDRESS CREATE OCCUPANCY ('.$login.') APTID [' . $aptid . ']');
    zb_AddressCleanAddressCache();
    zb_UserGetAllDataCacheClean();
}

/**
 * Deletes user to apartment binding by apt.id
 * 
 * @param int $addrid
 * 
 * @return void
 */
function zb_AddressDeleteAddress($addrid) {
    $addrid = vf($addrid, 3);
    $query = "DELETE from `address` WHERE `id` = '" . $addrid . "';";
    nr_query($query);
    log_register('ADDRESS DELETE OCCUPANCY ADDRID [' . $addrid . ']');
    zb_AddressCleanAddressCache();
    zb_UserGetAllDataCacheClean();
}

/**
 * Deletes address binding for some login
 * 
 * @param string $login
 * 
 * @return void
 */
function zb_AddressOrphanUser($login) {
    $login = vf($login);
    $query = "DELETE from `address` WHERE `login` = '" . $login . "';";
    nr_query($query);
    log_register('ADDRESS ORPHAN OCCUPANCY (' . $login . ')');
    zb_AddressCleanAddressCache();
    zb_UserGetAllDataCacheClean();
}

/**
 * Returns last apartment(?!) ID from database
 * 
 * @return int
 */
function zb_AddressGetLastid() {
    // This is very suspicious function - it must get last address binding id but it returns apartment id. 
    // We need to do some investigations of it usage by the code. Thats realy strange ><
    $query = "SELECT * FROM `apt` ORDER BY `id` DESC LIMIT 0,1";
    $lastid = simple_query($query);
    return ($lastid['id']);
}

/**
 * Returns apartment data by some login
 * 
 * @param string $login
 * @return array
 */
function zb_AddressGetAptData($login) {
    $login = vf($login);
    $result = array();
    $aptid_query = "SELECT `aptid`,`id` from `address` where `login`='" . $login . "'";
    $aptid = simple_query($aptid_query);
    @$aptid = $aptid['aptid'];

    if (!empty($aptid)) {
        $query = "SELECT * from `apt` where `id`='" . $aptid . "'";
        $result = simple_query($query);
        $result['aptid'] = $aptid;
    }
    return ($result);
}

/**
 * Returns all address login to apt bindings data
 * 
 * @return array
 */
function zb_AddressGetAddressAllData() {
    $query = "SELECT * from `address`";
    $result = simple_queryall($query);
    return ($result);
}

/**
 * Returns some apartment data by its ID
 * 
 * @param int $aptid
 * 
 * @return array
 */
function zb_AddressGetAptDataById($aptid) {
    $aptid = vf($aptid, 3);
    $result = array();
    $query = "SELECT * from `apt` where `id`='" . $aptid . "'";
    $result = simple_query($query);
    return ($result);
}

//////////////////////////////////////////// web functions (forms etc)

/**
 * Returns available cities selector
 * @param int $FilterByCityId
 *
 * @return string
 */
function web_CitySelector($FilterByCityId = 0) {
    $allcity = array();

    if (empty($FilterByCityId)) {
        $tmpCity = zb_AddressGetCityAllData();
    } else {
        $tmpCity = zb_AddressGetCityAllData($FilterByCityId);
    }

    if (!empty($tmpCity)) {
        foreach ($tmpCity as $io => $each) {
            $allcity[$each['id']] = $each['cityname'];
        }
    }

    $selected = (wf_CheckGet(array('citypreset'))) ? vf($_GET['citypreset'], 3) : '';

    $selector = wf_Selector('citysel', $allcity, '', $selected, false);
    return ($selector);
}

/**
 * Returns auto-clicking city selector
 * 
 * @return string
 */
function web_CitySelectorAc() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $searchableFlag = (@$altCfg['CITYSEL_SEARCHBL']) ? true : false;
    $selector = '';
    if ($altCfg['BRANCHES_ENABLED']) {
        global $branchControl;
        $branchControl->loadCities();
    }

    $allcity = array();
    $tmpCity = zb_AddressGetCityAllData();
    $allcity['-'] = '-'; //placeholder

    if (!empty($tmpCity)) {
        foreach ($tmpCity as $io => $each) {
            if ($altCfg['BRANCHES_ENABLED']) {
                if ($branchControl->isMyCity($each['id'])) {
                    $allcity[$each['id']] = $each['cityname'];
                }
            } else {
                $allcity[$each['id']] = $each['cityname'];
            }
        }
    }

    if ($searchableFlag) {
        $selector .= wf_SelectorSearchableAC('citysel', $allcity, '', '', false);
    } else {
        $selector .= wf_SelectorAC('citysel', $allcity, '', '', false);
    }
    $selector .= wf_tag('a', false, '', 'href="?module=city" target="_BLANK"') . web_city_icon() . wf_tag('a', true);
    return ($selector);
}

/**
 * Returns available streets selector
 * 
 * @param int $cityid
 * @return string
 */
function web_StreetSelector($cityid) {
    $allstreets = array();
    $tmpStreets = zb_AddressGetStreetAllDataByCity($cityid);

    if (!empty($tmpStreets)) {
        foreach ($tmpStreets as $io => $each) {
            $allstreets[$each['id']] = $each['streetname'];
        }
    }
    $selector = wf_Selector('streetsel', $allstreets, '', '', false);
    return ($selector);
}

/**
 * Returns auto-clicking selector of available streets
 * 
 * @param int $cityid
 * @return string
 */
function web_StreetSelectorAc($cityid) {
    global $ubillingConfig;
    $searchableFlag = $ubillingConfig->getAlterParam('STREETSEL_SEARCHBL');
    $allstreets = array();
    $selector = '';
    $tmpStreets = zb_AddressGetStreetAllDataByCity($cityid);

    $allstreets['-'] = '-'; // placeholder
    if (!empty($tmpStreets)) {
        foreach ($tmpStreets as $io => $each) {
            $allstreets[$each['id']] = $each['streetname'];
        }
    }

    if ($searchableFlag) {
        $selector .= wf_SelectorSearchableAC('streetsel', $allstreets, '', '', false);
    } else {
        $selector .= wf_SelectorAC('streetsel', $allstreets, '', '', false);
    }
    $selector .= wf_tag('a', false, '', 'href="?module=streets&citypreset=' . $cityid . '" target="_BLANK"') . web_street_icon() . wf_tag('a', true);

    return ($selector);
}

/**
 * Returns build selector
 * 
 * @param int $streetid
 * @return string
 */
function web_BuildSelector($streetid) {
    $allbuilds = array();
    $tmpBuilds = zb_AddressGetBuildAllDataByStreet($streetid);
    if (!empty($tmpBuilds)) {
        foreach ($tmpBuilds as $io => $each) {
            $allbuilds[$each['id']] = $each['buildnum'];
        }
    }
    $selector = wf_Selector('buildsel', $allbuilds, '', '', false);
    return ($selector);
}

/**
 * Returns auto-clicking build selector
 * 
 * @param int $streetid
 * 
 * @return string
 */
function web_BuildSelectorAc($streetid) {
    global $ubillingConfig;
    $searchableFlag = $ubillingConfig->getAlterParam('BUILDSEL_SEARCHBL');
    $allbuilds = array();
    $selector = '';
    $tmpBuilds = zb_AddressGetBuildAllDataByStreet($streetid);
    $allbuilds['-'] = '-'; //placeholder

    if (!empty($tmpBuilds)) {
        foreach ($tmpBuilds as $io => $each) {
            $allbuilds[$each['id']] = $each['buildnum'];
        }
    }

    if ($searchableFlag) {
        $selector .= wf_SelectorSearchableAC('buildsel', $allbuilds, '', '', false);
    } else {
        $selector .= wf_SelectorAC('buildsel', $allbuilds, '', '', false);
    }
    $selector .= wf_tag('a', false, '', 'href="?module=builds&action=edit&streetid=' . $streetid . '" target="_BLANK"') . web_build_icon() . wf_tag('a', true);
    return ($selector);
}

/**
 * Returns auto-clicking apartment selector
 * 
 * @param int $buildid
 * @return string
 */
function web_AptSelectorAc($buildid) {
    global $ubillingConfig;
    $searchableFlag = $ubillingConfig->getAlterParam('APTSEL_SEARCHBL');
    $allapts = array();

    $tmpApts = zb_AddressGetAptAllDataByBuild($buildid);

    $allapts['-'] = '-'; //placeholder

    if (!empty($tmpApts)) {
        foreach ($tmpApts as $io => $each) {
            $allapts[$each['id']] = $each['apt'];
        }
    }

    if ($searchableFlag) {
        $selector = wf_SelectorSearchableAC('aptsel', $allapts, '', '', false);
    } else {
        $selector = wf_SelectorAC('aptsel', $allapts, '', '', false);
    }

    return ($selector);
}

/**
 * Returns street creation form
 *
 * @param int $FilterByCityId
 *
 * @return string
 */
function web_StreetCreateForm($FilterByCityId = 0) {
    if (empty($FilterByCityId)) {
        $AjaxURLStr = '?module=streets&ajax=true';
    } else {
        $AjaxURLStr = '?module=streets&ajax=true&filterbycityid=' . $FilterByCityId;
    }

    $JQDTId = 'jqdt_' . md5($AjaxURLStr);
    $FormID = 'Form_' . wf_InputId();
    $CloseFrmChkID = 'CloseFrmChkID_' . wf_InputId();
    $ErrModalWID = wf_InputId();

    $cities = simple_query("SELECT `id` FROM `city`");

    if (!empty($cities)) {
        $inputs = web_CitySelector($FilterByCityId) . ' ' . __('City') . wf_delimiter();
        $inputs .= wf_TextInput('newstreetname', __('New Street name') . wf_tag('sup') . '*' . wf_tag('sup', true), '', true, '20');
        $inputs .= wf_TextInput('newstreetalias', __('New Street alias'), '', true, '20');
        $inputs .= wf_CheckInput('FormClose', __('Close form after operation'), true, true, $CloseFrmChkID, '__StreetFormCloseChck');
        $inputs .= wf_Submit(__('Create'));
        $form = wf_Form('?module=streets', 'POST', $inputs, 'glamour __StreetCreateForm', '', $FormID);
        $form .= wf_tag('script', false, '', 'type="text/javascript"');
        $form .= '$("[name=newstreetname]").focus( function() {                    
                    if ( $(this).css("border-color") == "rgb(255, 0, 0)" ) {
                        $(this).val("");
                        $(this).css("border-color", "");
                        $(this).css("color", "");
                    }                   
                });
                
                $("[name=newstreetname]").keydown( function() {                    
                    if ( $(this).css("border-color") == "rgb(255, 0, 0)" ) {
                        $(this).val("");
                        $(this).css("border-color", "");
                        $(this).css("color", "");
                    }                   
                });
                
                $(\'#' . $FormID . '\').submit(function(evt) {                        
                        var FrmAction = $(\'#' . $FormID . '\').attr("action");
                        var FrmData = $(\'#' . $FormID . '\').serialize() + \'&errfrmid=' . $ErrModalWID . '\'; 
                        var ModalWID = $(\'#' . $FormID . '\').closest(\'div\').attr(\'id\');
                        evt.preventDefault();
                        
                        //alert( FrmData );
                        
                        if ( empty( $("[name=newstreetname]").val() ) || $("[name=newstreetname]").css("border-color") == "rgb(255, 0, 0)" ) {
                            $("[name=newstreetname]").css("border-color", "red");
                            $("[name=newstreetname]").css("color", "grey");
                            $("[name=newstreetname]").val("' . __('Mandatory field') . '"); 
                        } else {
                                                                                    
                            $.ajax({
                                type: "POST",
                                url: FrmAction,
                                data: FrmData,
                                success: function(result) {
                                            $(\'#' . $JQDTId . '\').DataTable().ajax.reload();
                                            $("[name=newstreetname]").val("");
                                            
                                            if ( $(\'#' . $CloseFrmChkID . '\').is(\':checked\') ) {
                                                $( \'#\'+ModalWID ).dialog("close");
                                            }
                                            
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);                                                
                                                $( \'#' . $ErrModalWID . '\' ).dialog("open");                                                
                                            }
                                         }
                            });
                        }
                });
            ';
        $form .= wf_tag('script', true);
    } else {
        $messages = new UbillingMessageHelper();
        $form = $messages->getStyledMessage(__('No added cities - they will need to create a street'), 'error', 'style="margin: auto 0; padding: 10px 3px; width: 100%;"');
    }

    return ($form);
}

/**
 * Returns street editing form
 *
 * @param int $streetid
 * @param string $ModalWID
 * @return string
 */
function web_StreetEditForm($streetid, $ModalWID) {
    $FormID = 'Form_' . wf_InputId();
    $streetdata = zb_AddressGetStreetData($streetid);
    $streetname = $streetdata['streetname'];
    $streetalias = $streetdata['streetalias'];
    $cityid = $streetdata['cityid'];

    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_TextInput('editstreetname', __('Street name') . $sup, $streetname, true, '', '', '__StreetEditName');
    $inputs .= wf_TextInput('editstreetalias', __('Street alias'), $streetalias, true);
    $inputs .= wf_HiddenInput('', $ModalWID, '', '__StreetEditFormModalWindowID');
    $inputs .= wf_Submit(__('Save'));
    $form = wf_Form('?module=streets&action=edit&streetid=' . $streetid . '&cityid=' . $cityid, 'POST', $inputs, 'glamour __StreetEditForm', '', $FormID);

    return ($form);
}

/**
 * Returns available streets list with editing controls
 *
 * @param int $FilterByCityId
 *
 * @return string
 */
function web_StreetLister($FilterByCityId = 0) {
    $columns = array();
    $opts = '"order": [[ 0, "desc" ]]';
    $columns[] = (__('ID'));
    $columns[] = (__('City'));
    $columns[] = (__('Street name'));
    $columns[] = (__('Street alias'));
    $columns[] = (__('Actions'));

    if (empty($FilterByCityId)) {
        $AjaxURLStr = '?module=streets&ajax=true';
    } else {
        $AjaxURLStr = '?module=streets&ajax=true&filterbycityid=' . $FilterByCityId;
    }

    $JQDTId = 'jqdt_' . md5($AjaxURLStr);
    $ErrorModalWID = wf_InputId();

    $result = wf_modalAuto(web_add_icon() . ' ' . __('Create new street'), __('Create new street'), web_StreetCreateForm($FilterByCityId), 'ubButton') . ' ';

    if (!empty($FilterByCityId)) {
        $result .= wf_Link('?module=streets', web_street_icon() . '&nbsp&nbsp' . __('Show all streets'), false, 'ubButton');
    }

    $result .= wf_delimiter();
    $result .= wf_JqDtLoader($columns, $AjaxURLStr, false, __('results'), 100, $opts);
    $result .= wf_tag('script', false, '', 'type="text/javascript"');
    $result .= '
                    // making an event binding for "Street edit form" Submit action to be able to create "Street edit form" dynamically                    
                    $(document).on("focus keydown", ".__StreetEditName", function(evt) {                    
                        if ( $(".__StreetEditName").css("border-color") == "rgb(255, 0, 0)" ) {
                            $(".__StreetEditName").val("");
                            $(".__StreetEditName").css("border-color", "");
                            $(".__StreetEditName").css("color", "");
                        }                   
                    });

                    $(document).on("submit", ".__StreetEditForm", function(evt) {
                        var FrmAction = $(".__StreetEditForm").attr("action");
                        var FrmData = $(".__StreetEditForm").serialize() + \'&errfrmid=' . $ErrorModalWID . '\';
                        evt.preventDefault();                                            
                                               
                        if ( empty( $(".__StreetEditName").val() ) || $(".__StreetEditName").css("border-color") == "rgb(255, 0, 0)" ) {                            
                            $(".__StreetEditName").css("border-color", "red");
                            $(".__StreetEditName").css("color", "grey");
                            $(".__StreetEditName").val("' . __('Mandatory field') . '"); 
                        } else {             
                            $.ajax({
                                type: "POST",
                                url: FrmAction,
                                data: FrmData,
                                success: function(result) {
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);                                                
                                                $( \'#' . $ErrorModalWID . '\' ).dialog("open");                                                
                                            } else {
                                                $(\'#' . $JQDTId . '\').DataTable().ajax.reload();
                                                $( \'#\'+$(".__StreetEditFormModalWindowID").val() ).dialog("close");
                                            }
                                        }
                            });                       
                        }
                    });
    
                    function deleteStreet(StreetID, AjaxURL, ActionName, ErrFrmID) {
                        $.ajax({
                                type: "GET",
                                url: AjaxURL,
                                data: {action:ActionName, streetid:StreetID, errfrmid:ErrFrmID},
                                success: function(result) {                                    
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);
                                                $(\'#\'+ErrFrmID).dialog("open");
                                            }
                                            
                                            $(\'#' . $JQDTId . '\').DataTable().ajax.reload();
                                         }
                        });
                    }
                ';
    $result .= wf_JSEmptyFunc();
    $result .= wf_tag('script', true);

    return ($result);
}

/**
 * Renders JSON for streets JQDT
 *
 * @param int $FilterByCityId
 */
function renderStreetJSON($FilterByCityId = 0) {
    if (empty($FilterByCityId)) {
        $allstreets = zb_AddressGetStreetAllData();
    } else {
        $allstreets = zb_AddressGetStreetAllDataByCity($FilterByCityId);
    }

    $tmpCities = zb_AddressGetCityAllData();
    $allcities = array();

    $JSONHelper = new wf_JqDtHelper();
    $data = array();

    if (!empty($tmpCities)) {
        foreach ($tmpCities as $ia => $eachcity) {
            $allcities[$eachcity['id']] = $eachcity['cityname'];
        }
    }

    if (!empty($allstreets)) {
        foreach ($allstreets as $io => $eachstreet) {
            $cityName = (isset($allcities[$eachstreet['cityid']])) ? $allcities[$eachstreet['cityid']] : __('Error');

            $LnkID = wf_InputId();
            $Actions = wf_JSAlert('#', web_delete_icon(), 'Removing this may lead to irreparable results', 'deleteStreet(' . $eachstreet['id'] . ', \'?module=streets\', \'delete\', \'' . wf_InputId() . '\')') . ' ';
            $Actions .= wf_tag('a', false, '', 'id="' . $LnkID . '" href="#"');
            $Actions .= web_edit_icon();
            $Actions .= wf_tag('a', true);
            $Actions .= wf_tag('script', false, '', 'type="text/javascript"');
            $Actions .= '
                                        $(\'#' . $LnkID . '\').click(function(evt) {
                                            $.ajax({
                                                type: "GET",
                                                url: "?module=streets",
                                                data: { 
                                                        action:"edit",
                                                        streetid:"' . $eachstreet['id'] . '",                                                                                                                
                                                        ModalWID:"dialog-modal_' . $LnkID . '", 
                                                        ModalWBID:"body_dialog-modal_' . $LnkID . '",                                                        
                                                       },
                                                success: function(result) {
                                                            $(document.body).append(result);
                                                            $(\'#dialog-modal_' . $LnkID . '\').dialog("open");
                                                         }
                                            });
                    
                                            evt.preventDefault();
                                            return false;
                                        });
                                      ';
            $Actions .= wf_tag('script', true);
            $Actions .= wf_Link('?module=builds&action=edit&streetid=' . $eachstreet['id'], web_build_icon(), false);

            $data[] = $eachstreet['id'];
            $data[] = $cityName;
            $data[] = $eachstreet['streetname'];
            $data[] = $eachstreet['streetalias'];
            $data[] = $Actions;

            $JSONHelper->addRow($data);
            unset($data);
        }
    }

    $JSONHelper->getJson();
}

/**
 * Returns list of available builds with edit control
 * 
 * @return string
 */
function web_StreetListerBuildsEdit() {
    $columns = array();
    $opts = '"order": [[ 0, "desc" ]]';
    $columns[] = (__('ID'));
    $columns[] = (__('City'));
    $columns[] = (__('Street name'));
    $columns[] = (__('Street alias'));
    $columns[] = (__('Actions'));

    $AjaxURLStr = '?module=builds&ajax=true';

    $result = wf_JqDtLoader($columns, $AjaxURLStr, false, __('results'), 100, $opts);

    return ($result);
}

/**
 *
 * Renders JSON for builds JQDT
 *
 */
function renderBuildsEditJSON() {
    $allstreets = zb_AddressGetStreetAllData();
    $JSONHelper = new wf_JqDtHelper();
    $data = array();

    if (!empty($allstreets)) {
        foreach ($allstreets as $io => $eachstreet) {
            $cityname = zb_AddressGetCityData($eachstreet['cityid']);

            $Actions = wf_Link('?module=builds&action=edit&streetid=' . $eachstreet['id'], web_build_icon(), false);

            $data[] = $eachstreet['id'];
            $data[] = $cityname['cityname'];
            $data[] = $eachstreet['streetname'];
            $data[] = $eachstreet['streetalias'];
            $data[] = $Actions;

            $JSONHelper->addRow($data);
            unset($data);
        }
    }

    $JSONHelper->getJson();
}

/**
 * Returns build lister with controls for some streetID
 * 
 * @global array $ubillingConfig
 * @param int $streetid
 * @param int $AutoEditBuildID
 *
 * @return string
 */
function web_BuildLister($streetid, $AutoEditBuildID = 0) {
    global $ubillingConfig;
    $columns = array();
    $opts = '"order": [[ 0, "desc" ]]';
    $columns[] = (__('ID'));
    $columns[] = (__('Building number'));
    if ($ubillingConfig->getAlterParam('BUILD_EXTENDED')) {
        $columns[] = (__('Owner'));
        $columns[] = (__('Owner phone'));
        $columns[] = (__('Floors') . '/' . __('Entrances') . '/' . __('Apartments'));
        $columns[] = (__('Keys available'));
    }
    $columns[] = (__('Geo location'));
    $columns[] = (__('Actions'));

    global $ubillingConfig;
    $altcfg = $ubillingConfig->getAlter();
    //build passport data processing
    if ($altcfg['BUILD_EXTENDED']) {
        $buildPassport = new BuildPassport();
    }

    if (empty($AutoEditBuildID)) {
        $AjaxURLStr = '?module=builds&action=edit&streetid=' . $streetid . '&ajax=true';
    } else {
        $opts = '"order": [[ 0, "desc" ]], "initComplete": function(settings, json) { $(\'#Link_' . $AutoEditBuildID . '\').click(); }';
        $AjaxURLStr = '?module=builds&action=editbuild&streetid=' . $streetid . '&buildid=' . $AutoEditBuildID . '&ajax=true';
    }

    $JQDTId = 'jqdt_' . md5($AjaxURLStr);
    $ErrorModalWID = wf_InputId();

    $result = wf_modalAuto(web_add_icon() . ' ' . __('Add new build number'), __('Add new build number'), web_BuildAddForm($streetid), 'ubButton') . ' ';
    $result .= wf_Link('?module=builds', web_street_icon() . web_build_icon() . '&nbsp&nbsp' . __('Back to builds on streets'), false, 'ubButton');
    $result .= wf_delimiter();
    $result .= wf_JqDtLoader($columns, $AjaxURLStr, false, __('results'), 100, $opts);

    $result .= wf_tag('script', false, '', 'type="text/javascript"');
    $result .= '
                    // making an event binding for "Build edit form" Submit action to be able to create "Build edit form" dynamically                    
                    $(document).on("focus keydown", ".__BuildEditName", function(evt) {                    
                        if ( $(".__BuildEditName").css("border-color") == "rgb(255, 0, 0)" ) {
                            $(".__BuildEditName").val("");
                            $(".__BuildEditName").css("border-color", "");
                            $(".__BuildEditName").css("color", "");
                        }                   
                    });

                    $(document).on("submit", ".__BuildEditForm", function(evt) {
                        var FrmAction = $(".__BuildEditForm").attr("action");
                        var FrmData = $(".__BuildEditForm").serialize() + \'&errfrmid=' . $ErrorModalWID . '\';
                        evt.preventDefault();
                        
                        if ( empty( $(".__BuildEditName").val() ) || $(".__BuildEditName").css("border-color") == "rgb(255, 0, 0)" ) {                            
                            $(".__BuildEditName").css("border-color", "red");
                            $(".__BuildEditName").css("color", "grey");
                            $(".__BuildEditName").val("' . __('Mandatory field') . '"); 
                        } else {                            
                            $.ajax({
                                type: "POST",
                                url: FrmAction,
                                data: FrmData,
                                success: function(result) {
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);                                                
                                                $( \'#' . $ErrorModalWID . '\' ).dialog("open");                                                
                                            } else {
                                                $(\'#' . $JQDTId . '\').DataTable().ajax.reload();                                                
                                                $( \'#\'+$(".__BuildEditFormModalWindowID").val() ).dialog("close");                                                
                                            }
                                        }
                            });                       
                        }
                    });
    
                    function deleteBuild(BuildID, AjaxURL, ActionName, ErrFrmID) {
                        $.ajax({
                                type: "GET",
                                url: AjaxURL,
                                data: {action:ActionName, buildid:BuildID, errfrmid:ErrFrmID},
                                success: function(result) {                                    
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);
                                                $(\'#\'+ErrFrmID).dialog("open");
                                            }
                                            
                                            $(\'#' . $JQDTId . '\').DataTable().ajax.reload();
                                         }
                        });
                    }    
                ';
    $result .= wf_JSEmptyFunc();
    $result .= wf_tag('script', true);

    return ($result);
}

/**
 *
 * Renders JSON for builds lister JQDT
 * @param int $streetid
 * @param int $AutoEditBuildID
 *
 */
function renderBuildsListerJSON($streetid, $AutoEditBuildID = 0) {
    global $ubillingConfig;
    $altcfg = $ubillingConfig->getAlter();
    $allbuilds = zb_AddressGetBuildAllDataByStreet($streetid);
    $JSONHelper = new wf_JqDtHelper();
    $data = array();

    if (!empty($allbuilds)) {
        //build passport data processing
        if ($altcfg['BUILD_EXTENDED']) {
            $buildPassport = new BuildPassport();
            $buildPassportFlag = true;
        } else {
            $buildPassportFlag = false;
        }

        foreach ($allbuilds as $io => $eachbuild) {
            $ModalWID = wf_InputId();
            $LnkID = 'Link_' . $eachbuild['id'];
            $RedirToURL = '';

            if (!empty($AutoEditBuildID)) {
                $RedirToURL = '
                    $(\'#dialog-modal_' . $ModalWID . '\').on( "dialogclose", function( event, ui ) {
                        //window.location = location.protocol + \'//\' + location.host + location.pathname + \'?module=builds&action=edit&streetid=' . $streetid . '\'
                        // using "replcae" method because it this case the page with URL ?module=builds&action=editbuild&streetid=streetid&buildid=AutoEditBuildID 
                        // is not included in browser\'s history and when user clicks "Back" button in browser it lands up back to MAPS page
                        window.location.replace(location.protocol + \'//\' + location.host + location.pathname + \'?module=builds&action=edit&streetid=' . $streetid . '\')
                    });
                    ';
            }

            $Actions = wf_JSAlert('#', web_delete_icon(), 'Removing this may lead to irreparable results', 'deleteBuild(' . $eachbuild['id'] . ', \'?module=builds&streetid=' . $streetid . '\', \'delete\', \'' . wf_InputId() . '\')') . ' ';
            $Actions .= wf_tag('a', false, '', 'id="' . $LnkID . '" href="#"');
            $Actions .= web_edit_icon();
            $Actions .= wf_tag('a', true);
            $Actions .= wf_tag('script', false, '', 'type="text/javascript"');
            $Actions .= '
                        $(\'#' . $LnkID . '\').click(function(evt) {
                            $.ajax({
                                type: "GET",
                                url: "?module=builds",
                                data: { 
                                        action:"editbuild",
                                        streetid:"' . $streetid . '", 
                                        buildid:"' . $eachbuild['id'] . '",                                                                                                                
                                        ModalWID:"dialog-modal_' . $ModalWID . '", 
                                        ModalWBID:"body_dialog-modal_' . $ModalWID . '",                                                        
                                       },
                                success: function(result) {
                                            $(document.body).append(result);                                            
                                            $(\'#dialog-modal_' . $ModalWID . '\').dialog("open");
                                            ' . $RedirToURL . '                                            
                                         }
                            });
    
                            evt.preventDefault();
                            return false;
                        });
                      ';
            $Actions .= wf_tag('script', true);

            if (!empty($eachbuild['geo'])) {
                $Actions .= ' ' . wf_Link("?module=usersmap&findbuild=" . $eachbuild['geo'], wf_img('skins/icon_search_small.gif', __('Find on map')), false);
            } else {
                $Actions .= ' ' . wf_Link('?module=usersmap&locfinder=true&placebld=' . $eachbuild['id'], wf_img('skins/ymaps/target.png', __('Place on map')), false, '');
            }

            if ($buildPassportFlag) {
                $buildPassportEditForm = $buildPassport->renderEditForm($eachbuild['id']);
                $buildPassportEditForm .= wf_delimiter();
                $buildPassportUrl = $buildPassport::URL_PASSPORT . '&' . $buildPassport::ROUTE_BUILD . '=' . $eachbuild['id'];
                $buildPassportUrl .= '&back=' . base64_encode('builds&action=edit&streetid=' . $streetid);
                $buildPassportEditForm .= wf_Link($buildPassportUrl, wf_img('skins/icon_buildpassport.png') . ' ' . __('Go to build passport'), false, 'ubButton');
                $Actions .= ' ' . wf_modal(wf_img('skins/icon_buildpassport.png', __('Build passport')), __('Build passport'), $buildPassportEditForm, '', '600', '550');
            }

            $data[] = $eachbuild['id'];
            $data[] = $eachbuild['buildnum'];
            if ($buildPassportFlag) {
                $passportData = $buildPassport->getPassportData($eachbuild['id']);
                $ownerLabel = (!empty($passportData)) ? $passportData['owner'] . ' ' . $passportData['ownername'] . ' ' . $passportData['ownercontact'] : '';
                $phoneLabel = (!empty($passportData)) ? $passportData['ownerphone'] : '';
                $geometryLabel = (!empty($passportData['floors'])) ? $passportData['floors'] . '/' . $passportData['entrances'] . '/' . $passportData['apts'] : '';
                $keysLabel = (isset($passportData['keys']) and $passportData['keys']) ? wf_img('skins/icon_key.gif', __('Keys available')) : '';

                $data[] = ($ownerLabel);
                $data[] = ($phoneLabel);
                $data[] = ($geometryLabel);
                $data[] = ($keysLabel);
            }
            $data[] = $eachbuild['geo'];
            $data[] = $Actions;

            $JSONHelper->addRow($data);
            unset($data);
        }
    }

    $JSONHelper->getJson();
}

/**
 * Returns build creation form
 * @param int $streetid
 * @return string
 */
function web_BuildAddForm($streetid) {
    $AjaxURLStr = '?module=builds&action=edit&streetid=' . $streetid . '&ajax=true';
    $JQDTId = 'jqdt_' . md5($AjaxURLStr);
    $FormID = 'Form_' . wf_InputId();
    $CloseFrmChkID = 'CloseFrmChkID_' . wf_InputId();
    $ErrModalWID = wf_InputId();

    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_TextInput('newbuildnum', __('New build number') . $sup, '', true, 10);
    $inputs .= wf_CheckInput('FormClose', __('Close form after operation'), true, true, $CloseFrmChkID, '__BuildFormCloseChck');
    $inputs .= wf_Submit(__('Create'));
    $form = wf_Form($AjaxURLStr, 'POST', $inputs, 'glamour __BuildCreateForm', '', $FormID);
    $form .= wf_tag('script', false, '', 'type="text/javascript"');
    $form .= '  $("[name=newbuildnum]").focus( function() {                    
                    if ( $(this).css("border-color") == "rgb(255, 0, 0)" ) {
                        $(this).val("");
                        $(this).css("border-color", "");
                        $(this).css("color", "");
                    }                   
                });
                
                $("[name=newbuildnum]").keydown( function() {                    
                    if ( $(this).css("border-color") == "rgb(255, 0, 0)" ) {
                        $(this).val("");
                        $(this).css("border-color", "");
                        $(this).css("color", "");
                    }                   
                });
                
                $(\'#' . $FormID . '\').submit(function(evt) {                        
                        var FrmAction = $(\'#' . $FormID . '\').attr("action");
                        var FrmData = $(\'#' . $FormID . '\').serialize() + \'&errfrmid=' . $ErrModalWID . '\';
                        var ModalWID = $(\'#' . $FormID . '\').closest(\'div\').attr(\'id\');
                        evt.preventDefault();
                        
                        if ( empty( $("[name=newbuildnum]").val() ) || $("[name=newbuildnum]").css("border-color") == "rgb(255, 0, 0)" ) {
                            $("[name=newbuildnum]").css("border-color", "red");
                            $("[name=newbuildnum]").css("color", "grey");
                            $("[name=newbuildnum]").val("' . __('Mandatory field') . '"); 
                        } else {
                            
                            $.ajax({
                                type: "POST",
                                url: FrmAction,
                                data: FrmData,
                                success: function(result) {
                                            $(\'#' . $JQDTId . '\').DataTable().ajax.reload();
                                            $("[name=newbuildnum]").val("");
                                            
                                            if ( $(\'#' . $CloseFrmChkID . '\').is(\':checked\') ) {
                                                $( \'#\'+ModalWID ).dialog("close");
                                            }
                                            
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);                                                
                                                $( \'#' . $ErrModalWID . '\' ).dialog("open");                                                
                                            }
                                         }
                            });
                        }
                });
            ';
    $form .= wf_tag('script', true);

    return ($form);
}

/**
 * Returns edit form for buildings
 *
 * @param int $buildid
 * @param int $streetid
 * @param string $ModalWID
 *
 * @return string
 */
function web_BuildEditForm($buildid, $streetid, $ModalWID) {
    $FormID = 'Form_' . wf_InputId();
    $builddata = zb_AddressGetBuildData($buildid);
    $streetname = zb_AddressGetStreetData($streetid);
    if (!empty($streetname)) {
        $streetname = $streetname['streetname'];
    }
    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);

    $inputs = $streetname . " " . $builddata['buildnum'] . wf_tag('hr');
    $inputs .= wf_TextInput('editbuildnum', __('Building number') . $sup, $builddata['buildnum'], true, '10', '', '__BuildEditName');
    $inputs .= wf_TextInput('editbuildgeo', 'Geo location', $builddata['geo'], true, '20', 'geo');
    $inputs .= wf_HiddenInput('', $ModalWID, '', '__BuildEditFormModalWindowID');
    $inputs .= wf_Submit('Save');

    $form = wf_Form('?module=builds&action=editbuild&streetid=' . $streetid . '&buildid=' . $buildid, 'POST', $inputs, 'glamour __BuildEditForm', '', $FormID);

    return ($form);
}

/**
 * Returns only apartemen creation inputs for future include into occupancy dialogue
 * 
 * @return string
 */
function web_AptCreateForm() {
    $inputs = wf_TextInput('entrance', __('Entrance'), '', true);
    $inputs .= wf_TextInput('floor', __('Floor'), '', true);
    $inputs .= wf_tag('input', false, '', 'type="text" id="apt" name="apt" style="margin-right: 8px;" onchange="checkapt();"') . __('Apartment') . wf_tag('br');

    return ($inputs);
}

/**
 * Returns city creation form
 * 
 * @return string
 */
function web_CityCreateForm() {
    $JQDTId = 'jqdt_' . md5('?module=city&ajax=true');
    $FormID = 'Form_' . wf_InputId();
    $CloseFrmChkID = 'CloseFrmChkID_' . wf_InputId();
    $ErrModalWID = wf_InputId();

    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_TextInput('newcityname', __('New City name') . $sup, '', true);
    $inputs .= wf_TextInput('newcityalias', __('New City alias'), '', true);
    $inputs .= wf_CheckInput('FormClose', __('Close form after operation'), true, true, $CloseFrmChkID, '__CityFormCloseChck');
    $inputs .= wf_Submit(__('Create'));
    $form = wf_Form('?module=city', 'POST', $inputs, 'glamour __CityCreateForm', '', $FormID);
    $form .= wf_tag('script', false, '', 'type="text/javascript"');
    $form .= '  $("[name=newcityname]").focus( function() {                    
                    if ( $(this).css("border-color") == "rgb(255, 0, 0)" ) {
                        $(this).val("");
                        $(this).css("border-color", "");
                        $(this).css("color", "");
                    }                   
                });
                
                $("[name=newcityname]").keydown( function() {                    
                    if ( $(this).css("border-color") == "rgb(255, 0, 0)" ) {
                        $(this).val("");
                        $(this).css("border-color", "");
                        $(this).css("color", "");
                    }                   
                });
                
                $(\'#' . $FormID . '\').submit(function(evt) {                        
                        var FrmAction = $(\'#' . $FormID . '\').attr("action");
                        var FrmData = $(\'#' . $FormID . '\').serialize() + \'&errfrmid=' . $ErrModalWID . '\';
                        var ModalWID = $(\'#' . $FormID . '\').closest(\'div\').attr(\'id\');
                        evt.preventDefault();
                        
                        if ( empty( $("[name=newcityname]").val() ) || $("[name=newcityname]").css("border-color") == "rgb(255, 0, 0)" ) {
                            $("[name=newcityname]").css("border-color", "red");
                            $("[name=newcityname]").css("color", "grey");
                            $("[name=newcityname]").val("' . __('Mandatory field') . '"); 
                        } else {
                            
                            $.ajax({
                                type: "POST",
                                url: FrmAction,
                                data: FrmData,
                                success: function(result) {
                                            $(\'#' . $JQDTId . '\').DataTable().ajax.reload();
                                            $("[name=newcityname]").val("");
                                            
                                            if ( $(\'#' . $CloseFrmChkID . '\').is(\':checked\') ) {
                                                $( \'#\'+ModalWID ).dialog("close");
                                            }
                                            
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);                                                
                                                $( \'#' . $ErrModalWID . '\' ).dialog("open");                                                
                                            }
                                         }
                            });
                        }
                });
            ';
    $form .= wf_tag('script', true);

    return ($form);
}

/**
 * Returns existing city editing form
 *
 * @param int $cityid
 * @param string $ModalWID
 *
 * @return string
 */
function web_CityEditForm($cityid, $ModalWID) {
    $FormID = 'Form_' . wf_InputId();
    $citydata = zb_AddressGetCityData($cityid);
    $cityname = $citydata['cityname'];
    $cityalias = $citydata['cityalias'];

    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_TextInput('editcityname', __('City name') . $sup, $cityname, true, '', '', '__CityEditName');
    $inputs .= wf_TextInput('editcityalias', __('City alias'), $cityalias, true);
    $inputs .= wf_HiddenInput('', $ModalWID, '', '__CityEditFormModalWindowID');
    $inputs .= wf_Submit(__('Save'));

    $form = wf_Form('?module=city&action=edit&cityid=' . $cityid, 'POST', $inputs, 'glamour __CityEditForm', '', $FormID);

    return ($form);
}

/**
 * Returns available cities lister with some controls
 * 
 * @return string
 */
function web_CityLister() {
    $columns = array();
    $opts = '"order": [[ 0, "desc" ]]';
    $columns[] = (__('ID'));
    $columns[] = (__('City name'));
    $columns[] = (__('City alias'));
    $columns[] = (__('Actions'));

    $ErrorModalWID = wf_InputId();
    $AjaxURLStr = '?module=city&ajax=true';
    $JQDTId = 'jqdt_' . md5($AjaxURLStr);

    $result = wf_modalAuto(web_add_icon() . ' ' . __('Create new city'), __('Create new city'), web_CityCreateForm(), 'ubButton') . ' ';
    $result .= wf_delimiter();
    $result .= wf_JqDtLoader($columns, $AjaxURLStr, false, __('results'), 100, $opts);
    $result .= wf_tag('script', false, '', 'type="text/javascript"');
    $result .= '
                    // making an event binding for "City edit form" Submit action to be able to create "City edit form" dynamically                    
                    $(document).on("focus keydown", ".__CityEditName", function(evt) {                    
                        if ( $(".__CityEditName").css("border-color") == "rgb(255, 0, 0)" ) {
                            $(".__CityEditName").val("");
                            $(".__CityEditName").css("border-color", "");
                            $(".__CityEditName").css("color", "");
                        }                   
                    });

                    $(document).on("submit", ".__CityEditForm", function(evt) {
                        var FrmAction = $(".__CityEditForm").attr("action");
                        var FrmData = $(".__CityEditForm").serialize() + \'&errfrmid=' . $ErrorModalWID . '\';
                        evt.preventDefault();
                        
                        if ( empty( $(".__CityEditName").val() ) || $(".__CityEditName").css("border-color") == "rgb(255, 0, 0)" ) {                            
                            $(".__CityEditName").css("border-color", "red");
                            $(".__CityEditName").css("color", "grey");
                            $(".__CityEditName").val("' . __('Mandatory field') . '"); 
                        } else {                            
                            $.ajax({
                                type: "POST",
                                url: FrmAction,
                                data: FrmData,
                                success: function(result) {
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);                                                
                                                $( \'#' . $ErrorModalWID . '\' ).dialog("open");                                                
                                            } else {
                                                $(\'#' . $JQDTId . '\').DataTable().ajax.reload();
                                                $( \'#\'+$(".__CityEditFormModalWindowID").val() ).dialog("close");
                                            }
                                        }
                            });                       
                        }
                    });
    
                    function deleteCity(CityID, AjaxURL, ActionName, ErrFrmID) {
                        $.ajax({
                                type: "GET",
                                url: AjaxURL,
                                data: {action:ActionName, cityid:CityID, errfrmid:ErrFrmID},
                                success: function(result) {                                    
                                            if ( !empty(result) ) {                                            
                                                $(document.body).append(result);
                                                $(\'#\'+ErrFrmID).dialog("open");
                                            }
                                            
                                            $(\'#' . $JQDTId . '\').DataTable().ajax.reload();
                                         }
                        });
                    }                     
                ';
    $result .= wf_JSEmptyFunc();
    $result .= wf_tag('script', true);

    return ($result);
}

/**
 *
 * Renders JSON for cities JQDT
 *
 */
function renderCityJSON() {
    $allcity = zb_AddressGetCityAllData();
    $JSONHelper = new wf_JqDtHelper();
    $data = array();

    if (!empty($allcity)) {
        foreach ($allcity as $io => $eachcity) {
            $LnkID = wf_InputId();
            $Actions = wf_JSAlert('#', web_delete_icon(), 'Removing this may lead to irreparable results', 'deleteCity(' . $eachcity['id'] . ', \'?module=city\', \'delete\', \'' . wf_InputId() . '\')') . ' ';
            $Actions .= wf_tag('a', false, '', 'id="' . $LnkID . '" href="#"');
            $Actions .= web_edit_icon();
            $Actions .= wf_tag('a', true);
            $Actions .= wf_tag('script', false, '', 'type="text/javascript"');
            $Actions .= '
                                        $(\'#' . $LnkID . '\').click(function(evt) {
                                            $.ajax({
                                                type: "GET",
                                                url: "?module=city",
                                                data: { 
                                                        action:"edit",
                                                        cityid:"' . $eachcity['id'] . '",                                                                                                                
                                                        ModalWID:"dialog-modal_' . $LnkID . '", 
                                                        ModalWBID:"body_dialog-modal_' . $LnkID . '",                                                        
                                                       },
                                                success: function(result) {
                                                            $(document.body).append(result);
                                                            $(\'#dialog-modal_' . $LnkID . '\').dialog("open");
                                                         }
                                            });
                    
                                            evt.preventDefault();
                                            return false;
                                        });
                                      ';
            $Actions .= wf_tag('script', true);
            $Actions .= wf_Link('?module=streets&filterbycityid=' . $eachcity['id'], web_street_icon(), false, '');

            $data[] = $eachcity['id'];
            $data[] = $eachcity['cityname'];
            $data[] = $eachcity['cityalias'];
            $data[] = $Actions;

            $JSONHelper->addRow($data);
            unset($data);
        }
    }

    $JSONHelper->getJson();
}

/**
 * Returns extended address info control fields
 *
 * @return string
 */
function web_AddressExtenCreateForm() {
    $inputs = wf_TextInput('postalcode', __('Postal code'), '', true);
    $inputs .= wf_TextInput('towndistr', __('Town/District/Region'), '', true);
    $inputs .= wf_TextArea('addressexten', __('Extended address'), '', true, '47x4');

    return ($inputs);
}

/**
 * Creates or edits extended address info record for $login
 *
 * @param string $login
 * @param bool $makeEdit
 * @param string $postalcode
 * @param string $towndistr
 * @param string $addr_exten
 */
function zb_AddAddressExtenSave($login, $makeEdit, $postalcode = '', $towndistr = '', $addr_exten = '') {
    $tabAddrExten = new nya_address_extended();

    // check if such login even exists
    if ($makeEdit) {
        $loginExists = zb_AddressExtenGetAll($login);
        $makeEdit = (!empty($loginExists));
    }

    if ($makeEdit) {
        $tabAddrExten->data('postal_code', $postalcode);
        $tabAddrExten->data('town_district', $towndistr);
        $tabAddrExten->data('address_exten', $addr_exten);
        $tabAddrExten->where('login', '=', $login);
        $tabAddrExten->save(true, true);

        log_register('Extended address record changed for user (' . $login . ')');
    } elseif (!empty($postalcode) or !empty($towndistr) or !empty($addr_exten)) {
        $tabAddrExten->data('login', $login);
        $tabAddrExten->data('postal_code', $postalcode);
        $tabAddrExten->data('town_district', $towndistr);
        $tabAddrExten->data('address_exten', $addr_exten);
        $tabAddrExten->create();

        log_register('Extended address record created for user (' . $login . ')');
    }
}

/**
 * Removes extended address info record from DB
 *
 * @param $login
 */
function zb_AddAddressExtenDelete($login) {
    $tabAddrExten = new nya_address_extended();
    $tabAddrExten->where('login', '=', $login);
    $tabAddrExten->delete();
    zb_AddressCleanAddressCache();
}

/**
 * Returns all extended address info data as $login => $data
 * Data can be filtered by login
 *
 * @param string $login
 *
 * @return array
 */
function zb_AddressExtenGetAll($login = '') {
    $result = array();
    $whereString = (empty($login)) ? "" : " `login` = '" . $login . "'";
    $tabAddrExten = new nya_address_extended();
    $tabAddrExten->whereRaw($whereString);
    $tabAddrExten->selectable('*');
    $allAddrExten = $tabAddrExten->getAll();

    if (!empty($allAddrExten)) {
        foreach ($allAddrExten as $eachRow) {
            $result[$eachRow['login']] = $eachRow;
        }
    }

    return ($result);
}

/**
 * Returns all extended address info data as a list of $login => postal_code . ' ' . town_district . ' ' . address_exten
 * Data can be filtered by login
 *
 * @param string $login
 *
 * @return array
 */
function zb_AddressExtenGetList($login = '') {
    $result = array();
    $whereString = (empty($login)) ? "" : " `login` = '" . $login . "'";
    $tabAddrExten = new nya_address_extended();
    $tabAddrExten->whereRaw($whereString);
    $tabAddrExten->selectable('*');
    $allAddrExten = $tabAddrExten->getAll();

    if (!empty($allAddrExten)) {
        foreach ($allAddrExten as $eachRow) {
            $result[$eachRow['login']] = $eachRow['postal_code'] . ' ' . $eachRow['town_district'] . ' ' . $eachRow['address_exten'];
        }
    }

    return ($result);
}

/**
 * Returns all extended address info data as $login => $data from cache
 *
 * @return array|string
 */
function zb_AddressExtenGetAllCached() {
    global $ubillingConfig;
    $result = '';
    $cache = new UbillingCache();
    $cacheTime = $ubillingConfig->getAlterParam('ADDRESS_CACHE_TIME') * 60; // in minutes!!!!

    $result = $cache->getCallback('ADDRESS_EXTEN_CACHE', function () {
        return (zb_AddressExtenGetAll());
    }, $cacheTime);

    return ($result);
}

/**
 * Tries to get extended address info for certain $login from cache
 *
 * @param $login
 *
 * @return array
 */
function zb_AddressExtenGetLoginFast($login) {
    $result = zb_AddressExtenGetAllCached();

    if (!empty($result) and isset($result[$login])) {
        $result = $result[$login];
    } else {
        $result = array();
    }

    return ($result);
}

/**
 *
 * returns all addres array in view like login=>address
 * 
 * @return array
 */
function zb_AddressGetFulladdresslist() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $result = array();
    $query_full = "
        SELECT `address`.`login`,`city`.`cityname`,`street`.`streetname`,`build`.`buildnum`,`apt`.`apt` FROM `address` 
        INNER JOIN `apt` ON `address`.`aptid`= `apt`.`id` 
        INNER JOIN `build` ON `apt`.`buildid`=`build`.`id` 
        INNER JOIN `street` ON `build`.`streetid`=`street`.`id` 
        INNER JOIN `city` ON `street`.`cityid`=`city`.`id`";
    $full_adress = simple_queryall($query_full);
    if (!empty($full_adress)) {
        foreach ($full_adress as $ArrayData) {
            // zero apt handle
            if ($altCfg['ZERO_TOLERANCE']) {
                $apartment_filtered = ($ArrayData['apt'] == 0) ? '' : '/' . $ArrayData['apt'];
            } else {
                $apartment_filtered = '/' . $ArrayData['apt'];
            }

            if ($altCfg['CITY_DISPLAY']) {
                $result[$ArrayData['login']] = $ArrayData['cityname'] . ' ' . $ArrayData['streetname'] . ' ' . $ArrayData['buildnum'] . $apartment_filtered;
            } else {
                $result[$ArrayData['login']] = $ArrayData['streetname'] . ' ' . $ArrayData['buildnum'] . $apartment_filtered;
            }
        }
    }

    return ($result);
}

/**
 * returns all addres array in view like login=>address
 * 
 * @global array $ubillingConfig
 * @return array
 */
function zb_AddressGetFulladdresslistCached() {
    global $ubillingConfig;
    $alterconf = $ubillingConfig->getAlter();
    $cacheTime = $alterconf['ADDRESS_CACHE_TIME'] * 60; // in minutes!!!!
    $result = '';
    $cache = new UbillingCache();
    $result = $cache->getCallback('FULLADDRESSLISTCACHE', function () {
        return (zb_AddressGetFulladdresslist());
    }, $cacheTime);

    return ($result);
}

/**
 * Returns all addres array in view like login=>city address
 * 
 * @return array
 */
function zb_AddressGetFullCityaddresslist() {
    global $ubillingConfig;
    $altCfg = $ubillingConfig->getAlter();
    $result = array();
    $query_full = "
        SELECT `address`.`login`,`city`.`cityname`,`street`.`streetname`,`build`.`buildnum`,`apt`.`apt` FROM `address` 
        INNER JOIN `apt` ON `address`.`aptid`= `apt`.`id` 
        INNER JOIN `build` ON `apt`.`buildid`=`build`.`id` 
        INNER JOIN `street` ON `build`.`streetid`=`street`.`id` 
        INNER JOIN `city` ON `street`.`cityid`=`city`.`id`";
    $full_adress = simple_queryall($query_full);
    if (!empty($full_adress)) {
        foreach ($full_adress as $ArrayData) {
            // zero apt handle
            if ($altCfg['ZERO_TOLERANCE']) {
                $apartment_filtered = ($ArrayData['apt'] == 0) ? '' : '/' . $ArrayData['apt'];
            } else {
                $apartment_filtered = '/' . $ArrayData['apt'];
            }

            //only city display option
            $result[$ArrayData['login']] = $ArrayData['cityname'] . ' ' . $ArrayData['streetname'] . ' ' . $ArrayData['buildnum'] . $apartment_filtered;
        }
    }

    return ($result);
}

/**
 * Returns all user cities as  login=>city
 * 
 * @return array
 */
function zb_AddressGetCityUsers() {
    $result = array();
    $query_full = "
        SELECT `address`.`login`,`city`.`cityname` FROM `address` 
        INNER JOIN `apt` ON `address`.`aptid`= `apt`.`id` 
        INNER JOIN `build` ON `apt`.`buildid`=`build`.`id` 
        INNER JOIN `street` ON `build`.`streetid`=`street`.`id` 
        INNER JOIN `city` ON `street`.`cityid`=`city`.`id`";
    $full_adress = simple_queryall($query_full);
    if (!empty($full_adress)) {
        foreach ($full_adress as $ArrayData) {
            //only city display option
            $result[$ArrayData['login']] = $ArrayData['cityname'];
        }
    }

    return ($result);
}

/**
 * Returns all user cities as  login=>streetname
 * 
 * @return array
 */
function zb_AddressGetStreetUsers() {
    $result = array();
    $query_full = "
        SELECT `address`.`login`,`street`.`streetname` FROM `address` 
        INNER JOIN `apt` ON `address`.`aptid`= `apt`.`id` 
        INNER JOIN `build` ON `apt`.`buildid`=`build`.`id` 
        INNER JOIN `street` ON `build`.`streetid`=`street`.`id`";
    $full_adress = simple_queryall($query_full);
    if (!empty($full_adress)) {
        foreach ($full_adress as $ArrayData) {
            //only street display option
            $result[$ArrayData['login']] = $ArrayData['streetname'];
        }
    }

    return ($result);
}

/**
 * Returns all user builds as login=>buildId
 * 
 * @return array
 */
function zb_AddressGetBuildUsers() {
    $result = array();
    $query = "
        SELECT `address`.`login`,`buildid` FROM `address` 
        INNER JOIN `apt` ON `address`.`aptid`= `apt`.`id` 
        INNER JOIN `build` ON `apt`.`buildid`=`build`.`id` 
        INNER JOIN `street` ON `build`.`streetid`=`street`.`id`";
    $rawData = simple_queryall($query);
    if (!empty($rawData)) {
        foreach ($rawData as $each) {
            $result[$each['login']] = $each['buildid'];
        }
    }

    return ($result);
}

/**
 * Returns all user builds cached data as login=>buildId
 * 
 * @return array
 */
function zb_AddressGetBuildUsersCached() {
    $result = array();
    $cacheTimeout = 86400;
    $cacheKey = 'USERBUILDS';
    $cache = new UbillingCache();
    $cachedData = $cache->get($cacheKey, $cacheTimeout);
    if (empty($cachedData)) {
        $result = zb_AddressGetBuildUsers();
        $cache->set($cacheKey, $result, $cacheTimeout);
    } else {
        $result = $cachedData;
    }

    return ($result);
}

/**
 * Filters street name for special chars
 * 
 * @param string $name
 * 
 * @return string
 */
function zb_AddressFilterStreet($name) {
    $name = str_replace('"', '``', $name);
    $name = str_replace('\'', '`', $name);
    return ($name);
}

/**
 * Searches for city name in DB and returns it's ID if exists
 *
 * @param string $CityName
 * @param int $ExcludeEditedCityID - if we edit something and want to find same record but with other ID
 *
 * @return string
 */
function checkCityExists($CityName, $ExcludeEditedCityID = 0) {
    $CityName = ubRouting::filters($CityName, 'safe');
    $CityName = trim($CityName);

    if (empty($ExcludeEditedCityID)) {
        $query = "SELECT `id` FROM `city` WHERE `cityname` = '" . $CityName . "';";
    } else {
        $query = "SELECT `id` FROM `city` WHERE `cityname` = '" . $CityName . "' AND `id` != '" . $ExcludeEditedCityID . "';";
    }

    $result = simple_queryall($query);

    return (empty($result)) ? '' : $result[0]['id'];
}

/**
 * Searches for street name with such city ID in DB and returns it's ID if exists
 *
 * @param string $StreetName
 * @param string $CityID
 * @param int $ExcludeEditedStreetID - if we edit something and want to find same record but with other ID
 *
 * @return string
 */
function checkStreetInCityExists($StreetName, $CityID, $ExcludeEditedStreetID = 0) {
    $CityID = ubRouting::filters($CityID, 'int');
    $StreetName = trim($StreetName);
    $StreetName = ubRouting::filters($StreetName, 'safe');
    $StreetName = ubRouting::filters($StreetName, 'mres');

    if (empty($ExcludeEditedStreetID)) {
        $query = "SELECT `id` FROM `street` WHERE `streetname` = '" . $StreetName . "' AND `cityid` = '" . $CityID . "';";
    } else {
        $query = "SELECT `id` FROM `street` WHERE `streetname` = '" . $StreetName . "' AND `cityid` = '" . $CityID . "' AND `id` != '" . $ExcludeEditedStreetID . "';";
    }

    $result = simple_queryall($query);

    return (empty($result)) ? '' : $result[0]['id'];
}

/**
 * Searches for build number with such street ID in DB and returns it's ID if exists
 *
 * @param string $BuildNumber
 * @param string $StreetID
 * @param int $ExcludeEditedBuildID - if we edit something and want to find same record but with other ID
 *
 * @return string
 */
function checkBuildOnStreetExists($BuildNumber, $StreetID, $ExcludeEditedBuildID = 0) {
    $BuildNumber = trim($BuildNumber);

    if (empty($ExcludeEditedBuildID)) {
        $query = "SELECT `id` FROM `build` WHERE `buildnum` = '" . $BuildNumber . "' AND `streetid` = '" . $StreetID . "';";
    } else {
        $query = "SELECT `id` FROM `build` WHERE `buildnum` = '" . $BuildNumber . "' AND `streetid` = '" . $StreetID . "' AND `id` != '" . $ExcludeEditedBuildID . "';";
    }

    $result = simple_queryall($query);

    return (empty($result)) ? '' : $result[0]['id'];
}

/**
 * Returns list of all builds address strings as buildId=>buildAddress (city/street/buildnum)
 * 
 * @return array
 */
function zb_AddressGetBuildAllAddress($full = true) {
    $result = array();
    $allBuilds = zb_AddressGetBuildAllDataAssoc();
    if (!empty($allBuilds)) {
        if ($full) {
            $allCities = zb_AddressGetFullCityNames();
        }

        $allStreets = zb_AddressGetStreetsDataAssoc();

        foreach ($allBuilds as $buildId => $buildData) {
            $buildStreetId = $buildData['streetid'];
            $buildStreetData = $allStreets[$buildStreetId];
            if ($full) {
                $buildCityId = $buildStreetData['cityid'];
                $buildCityName = $allCities[$buildCityId];
                $result[$buildId] = $buildCityName . ' ' . $buildStreetData['streetname'] . ' ' . $buildData['buildnum'];
            } else {
                $result[$buildId] = $buildStreetData['streetname'] . ' ' . $buildData['buildnum'];
            }
        }
    }
    return ($result);
}
