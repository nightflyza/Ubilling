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
    $cachePath = 'exports/fulladdresslistcache.dat';
    if (file_exists($cachePath)) {
        unlink($cachePath);
    }
}

/**
 * Creates new city in database
 * 
 * @param string $cityname
 * @param string $cityalias
 * 
 * @return void
 */
function zb_AddressCreateCity($cityname, $cityalias) {
    $cityname = mysql_real_escape_string($cityname);
    $cityalias = vf($cityalias);
    $query = "INSERT INTO `city` (`id`,`cityname`,`cityalias`) VALUES (NULL, '" . $cityname . "','" . $cityalias . "'); ";
    nr_query($query);
    log_register('CREATE AddressCity `' . $cityname . '` `' . $cityalias . '`');
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
    log_register('DELETE AddressCity [' . $cityid . ']');
}

/**
 * Changes city name in database
 * 
 * @param int $cityid
 * @param string $cityname
 * 
 * @return void
 */
function zb_AddressChangeCityName($cityid, $cityname) {
    $cityid = vf($cityid, 3);
    $cityname = mysql_real_escape_string($cityname);
    $query = "UPDATE `city` SET `cityname` = '" . $cityname . "' WHERE `id`= '" . $cityid . "' ;";
    nr_query($query);
    log_register('CHANGE AddressCityName [' . $cityid . '] `' . $cityname . '`');
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
    $cityid = vf($cityid, 3);
    $cityalias = vf($cityalias);
    $query = "UPDATE `city` SET `cityalias` = '" . $cityalias . "' WHERE `id`= '" . $cityid . "' ;";
    nr_query($query);
    log_register('CHANGE AddressCityAlias [' . $cityid . '] `' . $cityalias . '`');
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
    return($all_ids);
}

/**
 * Returns all available cities full data
 * 
 * @return array
 */
function zb_AddressGetCityAllData() {
    $query = "SELECT * from `city` ORDER by `id` ASC";
    $all_data = simple_queryall($query);
    return($all_data);
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

    return($result);
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
    $streetname = mysql_real_escape_string($streetname);
    $streetalias = vf($streetalias);
    $cityid = vf($cityid, 3);
    $query = "INSERT INTO `street` (`id`,`cityid`,`streetname`,`streetalias`) VALUES  (NULL, '" . $cityid . "','" . $streetname . "','" . $streetalias . "');";
    nr_query($query);
    log_register('CREATE AddressStreet [' . $cityid . '] `' . $streetname . '` `' . $streetalias . '`');
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
    log_register('DELETE AddressStreet [' . $streetid . ']');
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
    $streetid = vf($streetid, 3);
    $streetname = zb_AddressFilterStreet($streetname);
    $streetname = mysql_real_escape_string($streetname);
    $query = "UPDATE `street` SET `streetname` = '" . $streetname . "' WHERE `id`= '" . $streetid . "' ;";
    nr_query($query);
    log_register('CHANGE AddressStreetName [' . $streetid . '] `' . $streetname . '`');
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
    $streetid = vf($streetid);
    $streetalias = mysql_real_escape_string($streetalias);
    $query = "UPDATE `street` SET `streetalias` = '" . $streetalias . "' WHERE `id`= '" . $streetid . "' ;";
    nr_query($query);
    log_register('CHANGE AddressStreetAlias [' . $streetid . '] `' . $streetalias . '`');
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
    return($all_ids);
}

/**
 * Returns all data of available streets
 * 
 * @return array
 */
function zb_AddressGetStreetAllData() {
    $query = "SELECT * from `street`";
    $all_data = simple_queryall($query);
    return($all_data);
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
    return($all_data);
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
    log_register('CREATE AddressBuild [' . $streetid . '] `' . $buildnum . '`');
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
    log_register('DELETE AddressBuild [' . $buildid . ']');
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
    log_register('CHANGE AddressBuildNum [' . $buildid . '] `' . $buildnum . '`');
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
    return($all_ids);
}

/**
 * Returns all available builds data from database
 * 
 * @return array
 */
function zb_AddressGetBuildAllData() {
    $query = "SELECT * from `build`";
    $all_data = simple_queryall($query);
    return($all_data);
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
    return($all_data);
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
    return($all_data);
}

/**
 * Returns user address by some user login
 * 
 * @param string $login
 * 
 * @return string
 */
function zb_UserGetFullAddress($login) {
    $alladdress = zb_AddressGetFulladdresslist();
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
    log_register('CREATE AddressApartment [' . $buildid . '] `' . $entrance . '` `' . $floor . '` `' . $apt . '`');
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
    log_register('DELETE AddressApartment [' . $aptid . ']');
    zb_AddressCleanAddressCache();
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
    log_register('CHANGE AddressApartment [' . $aptid . '] [' . $buildid . '] `' . $entrance . '` `' . $floor . '` `' . $apt . '`');
    zb_AddressCleanAddressCache();
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
    log_register('CREATE AddressOccupancy (' . $login . ') [' . $aptid . ']');
    zb_AddressCleanAddressCache();
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
    log_register('DELETE AddressOccupancy [' . $addrid . ']');
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
    log_register('ORPHAN AddressOccupancy (' . $login . ')');
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
    return($lastid['id']);
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
    return($result);
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
    return($result);
}

//////////////////////////////////////////// web functions (forms etc)

/**
 * Returns available cities selector
 * 
 * @return string
 */
function web_CitySelector() {
    $allcity = array();
    $tmpCity = zb_AddressGetCityAllData();

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
    $allcity = array();
    $tmpCity = zb_AddressGetCityAllData();
    $allcity['-'] = '-'; //placeholder

    if (!empty($tmpCity)) {
        foreach ($tmpCity as $io => $each) {
            $allcity[$each['id']] = $each['cityname'];
        }
    }

    $selector = wf_SelectorAC('citysel', $allcity, '', '', false);
    $selector.= wf_tag('a', false, '', 'href="?module=city" target="_BLANK"') . web_city_icon() . wf_tag('a', true);
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
    $allstreets = array();
    $tmpStreets = zb_AddressGetStreetAllDataByCity($cityid);

    $allstreets['-'] = '-'; // placeholder
    if (!empty($tmpStreets)) {
        foreach ($tmpStreets as $io => $each) {
            $allstreets[$each['id']] = $each['streetname'];
        }
    }

    $selector = wf_SelectorAC('streetsel', $allstreets, '', '', false);
    $selector.= wf_tag('a', false, '', 'href="?module=streets&citypreset=' . $cityid . '" target="_BLANK"') . web_street_icon() . wf_tag('a', true);

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
    $allbuilds = array();
    $tmpBuilds = zb_AddressGetBuildAllDataByStreet($streetid);
    $allbuilds['-'] = '-'; //placeholder

    if (!empty($tmpBuilds)) {
        foreach ($tmpBuilds as $io => $each) {
            $allbuilds[$each['id']] = $each['buildnum'];
        }
    }

    $selector = wf_SelectorAC('buildsel', $allbuilds, '', '', false);
    $selector.= wf_tag('a', false, '', 'href="?module=builds&action=edit&streetid=' . $streetid . '" target="_BLANK"') . web_build_icon() . wf_tag('a', true);
    return ($selector);
}

/**
 * Returns auto-clicking apartment selector
 * 
 * @param int $buildid
 * @return string
 */
function web_AptSelectorAc($buildid) {
    $allapts = array();
    $tmpApts = zb_AddressGetAptAllDataByBuild($buildid);

    $allapts['-'] = '-'; //placeholder

    if (!empty($tmpApts)) {
        foreach ($tmpApts as $io => $each) {
            $allapts[$each['id']] = $each['apt'];
        }
    }
    $selector = wf_SelectorAC('aptsel', $allapts, '', '', false);
    return ($selector);
}

/**
 * Returns street creation form
 * 
 * @return string
 */
function web_StreetCreateForm() {
    $cities = simple_query("SELECT `id` from `city`");
    if (!empty($cities)) {
        $inputs = web_CitySelector() . ' ' . __('City') . wf_delimiter();
        $inputs.=wf_TextInput('newstreetname', __('New Street name') . wf_tag('sup') . '*' . wf_tag('sup', true), '', true, '20');
        $inputs.=wf_TextInput('newstreetalias', __('New Street alias'), '', true, '20');
        $inputs.=wf_Submit(__('Create'));
        $form = wf_Form('', 'POST', $inputs, 'glamour');
    } else {
        $form = __('No added cities - they will need to create a street');
    }
    return($form);
}

/**
 * Returns available streets list with editing controls
 * 
 * @return string
 */
function web_StreetLister() {
    $allstreets = zb_AddressGetStreetAllData();
    $tmpCities = zb_AddressGetCityAllData();
    $allcities = array();
    if (!empty($tmpCities)) {
        foreach ($tmpCities as $ia => $eachcity) {
            $allcities[$eachcity['id']] = $eachcity['cityname'];
        }
    }

    $cells = wf_TableCell(__('ID'));
    $cells.= wf_TableCell(__('City'));
    $cells.= wf_tablecell(__('Street name'));
    $cells.= wf_tablecell(__('Street alias'));
    $cells.= wf_tablecell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allstreets)) {
        foreach ($allstreets as $io => $eachstreet) {
            $cityName = (isset($allcities[$eachstreet['cityid']])) ? $allcities[$eachstreet['cityid']] : __('Error');

            $cells = wf_TableCell($eachstreet['id']);
            $cells.= wf_TableCell($cityName);
            $cells.= wf_tablecell($eachstreet['streetname']);
            $cells.= wf_tablecell($eachstreet['streetalias']);
            $acts = wf_JSAlert('?module=streets&action=delete&streetid=' . $eachstreet['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            $acts.= wf_JSAlert('?module=streets&action=edit&streetid=' . $eachstreet['id'], web_edit_icon(), 'Are you serious');
            $acts.= wf_Link('?module=builds&action=edit&streetid=' . $eachstreet['id'], web_build_icon(), false);
            $cells.= wf_tablecell($acts);
            $rows.= wf_TableRow($cells, 'row3');
        }
    }
    $result = wf_TableBody($rows, '100%', 0, 'sortable');
    return($result);
}

/**
 * Returns list of available builds with edit control
 * 
 * @return string
 */
function web_StreetListerBuildsEdit() {
    $allstreets = zb_AddressGetStreetAllData();

    $cells = wf_TableCell(__('ID'));
    $cells.= wf_TableCell(__('City'));
    $cells.= wf_TableCell(__('Street name'));
    $cells.= wf_TableCell(__('Street alias'));
    $cells.= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');


    if (!empty($allstreets)) {
        foreach ($allstreets as $io => $eachstreet) {
            $cityname = zb_AddressGetCityData($eachstreet['cityid']);

            $cells = wf_TableCell($eachstreet['id']);
            $cells.= wf_TableCell($cityname['cityname']);
            $cells.= wf_TableCell($eachstreet['streetname']);
            $cells.= wf_TableCell($eachstreet['streetalias']);
            $actlink = wf_Link('?module=builds&action=edit&streetid=' . $eachstreet['id'], web_build_icon(), false);
            $cells.= wf_TableCell($actlink);
            $rows.= wf_TableRow($cells, 'row3');
        }
    }

    $result = wf_TableBody($rows, '100%', 0, 'sortable');

    return($result);
}

/**
 * Returns build lister with controls for some streetID
 * 
 * @global array $ubillingConfig
 * @param int $streetid
 * @return string
 */
function web_BuildLister($streetid) {
    global $ubillingConfig;
    $altcfg = $ubillingConfig->getAlter();

    $allbuilds = zb_AddressGetBuildAllDataByStreet($streetid);

    $cells = wf_TableCell(__('ID'));
    $cells.= wf_TableCell(__('Building number'));
    $cells.= wf_TableCell(__('Geo location'));
    $cells.= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allbuilds)) {
        //build passport data processing
        if ($altcfg['BUILD_EXTENDED']) {
            $buildPassport = new BuildPassport();
        }
        foreach ($allbuilds as $io => $eachbuild) {
            $cells = wf_TableCell($eachbuild['id']);
            $cells.= wf_TableCell($eachbuild['buildnum']);
            $cells.= wf_TableCell($eachbuild['geo']);
            $acts = wf_JSAlert('?module=builds&action=delete&streetid=' . $streetid . '&buildid=' . $eachbuild['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            $acts.='' . wf_JSAlert('?module=builds&action=editbuild&streetid=' . $streetid . '&buildid=' . $eachbuild['id'], web_edit_icon(), 'Are you serious');
            if (!empty($eachbuild['geo'])) {
                $acts.=' ' . wf_Link("?module=usersmap&findbuild=" . $eachbuild['geo'], wf_img('skins/icon_search_small.gif', __('Find on map')), false);
            }
            if ($altcfg['BUILD_EXTENDED']) {
                $acts.=' ' . wf_modal(wf_img('skins/icon_passport.gif', __('Build passport')), __('Build passport'), $buildPassport->renderEditForm($eachbuild['id']), '', '600', '450');
            }
            $cells.= wf_TableCell($acts);
            $rows.= wf_TableRow($cells, 'row3');
        }
    }
    $result = wf_TableBody($rows, '100%', 0, 'sortable');
    return ($result);
}

/**
 * Returns build creation form
 * 
 * @return string
 */
function web_BuildAddForm() {
    $inputs = wf_TextInput('newbuildnum', __('New build number'), '', true, 10);
    $inputs.= wf_Submit(__('Create'));
    $form = wf_Form("", 'POST', $inputs, 'glamour');
    return($form);
}

/**
 * Returns street editing form
 * 
 * @param int $streetid
 * @return string
 */
function web_StreetEditForm($streetid) {
    $streetdata = zb_AddressGetStreetData($streetid);
    $streetname = $streetdata['streetname'];
    $streetalias = $streetdata['streetalias'];

    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_TextInput('editstreetname', __('Street name') . $sup, $streetname, true);
    $inputs.= wf_TextInput('editstreetalias', __('Street alias') . $sup, $streetalias, true);
    $inputs.= wf_Submit(__('Save'));
    $form = wf_Form('', 'POST', $inputs, 'glamour');

    return($form);
}

/**
 * Returns only apartemen creation inputs for future include into occupancy dialogue
 * 
 * @return string
 */
function web_AptCreateForm() {
    $inputs = wf_TextInput('entrance', __('Entrance'), '', true);
    $inputs.= wf_TextInput('floor', __('Floor'), '', true);
    $inputs.= wf_tag('input', false, '', 'type="text" id="apt" name="apt" onchange="checkapt();"') . __('Apartment') . wf_tag('br');

    return($inputs);
}

/**
 * Returns city creation form
 * 
 * @return string
 */
function web_CityCreateForm() {
    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_TextInput('newcityname', __('New City name') . $sup, '', true);
    $inputs.= wf_TextInput('newcityalias', __('New City alias'), '', true);
    $inputs.= wf_Submit(__('Create'));
    $form = wf_Form('', 'POST', $inputs, 'glamour');

    return($form);
}

/**
 * Returns available cities lister with some controls
 * 
 * @return string
 */
function web_CityLister() {
    $allcity = zb_AddressGetCityAllData();

    $cells = wf_TableCell(__('ID'));
    $cells.= wf_TableCell(__('City name'));
    $cells.= wf_TableCell(__('City alias'));
    $cells.= wf_TableCell(__('Actions'));
    $rows = wf_TableRow($cells, 'row1');

    if (!empty($allcity)) {
        foreach ($allcity as $io => $eachcity) {

            $cells = wf_TableCell($eachcity['id']);
            $cells.= wf_TableCell($eachcity['cityname']);
            $cells.= wf_TableCell($eachcity['cityalias']);
            $acts = wf_JSAlert('?module=city&action=delete&cityid=' . $eachcity['id'], web_delete_icon(), 'Removing this may lead to irreparable results') . ' ';
            $acts.= wf_JSAlert('?module=city&action=edit&cityid=' . $eachcity['id'], web_edit_icon(), 'Are you serious') . ' ';
            $acts.= wf_Link('?module=streets', web_street_icon(), false, '');
            $cells.= wf_TableCell($acts);
            $rows.= wf_TableRow($cells, 'row3');
        }
    }
    $result = wf_TableBody($rows, '100%', 0, 'sortable');
    return($result);
}

/**
 * Returns existing city editing form
 * 
 * @param int $cityid
 * @return string
 */
function web_CityEditForm($cityid) {
    $citydata = zb_AddressGetCityData($cityid);
    $cityname = $citydata['cityname'];
    $cityalias = $citydata['cityalias'];

    $sup = wf_tag('sup') . '*' . wf_tag('sup', true);
    $inputs = wf_TextInput('editcityname', __('City name') . $sup, $cityname, true);
    $inputs.= wf_TextInput('editcityalias', __('City alias'), $cityalias, true);
    $inputs.= wf_Submit(__('Save'));

    $form = wf_Form('', 'POST', $inputs, 'glamour');

    $form.=wf_Link('?module=city', 'Back', true, 'ubButton');

    return($form);
}

/**
 * returns all addres array in view like login=>address
 * 
 * @return array
 */
function zb_AddressGetFulladdresslist() {
    $alterconf = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
    $result = array();
    $apts = array();
    $builds = array();
    $city_q = "SELECT * from `city`";
    $adrz_q = "SELECT * from `address`";
    $apt_q = "SELECT * from `apt`";
    $build_q = "SELECT * from build";
    $streets_q = "SELECT * from `street`";
    $alladdrz = simple_queryall($adrz_q);
    $allapt = simple_queryall($apt_q);
    $allbuilds = simple_queryall($build_q);
    $allstreets = simple_queryall($streets_q);
    if (!empty($alladdrz)) {
        $cities = zb_AddressGetFullCityNames();

        foreach ($alladdrz as $io1 => $eachaddress) {
            $address[$eachaddress['id']] = array('login' => $eachaddress['login'], 'aptid' => $eachaddress['aptid']);
        }
        foreach ($allapt as $io2 => $eachapt) {
            $apts[$eachapt['id']] = array('apt' => $eachapt['apt'], 'buildid' => $eachapt['buildid']);
        }
        foreach ($allbuilds as $io3 => $eachbuild) {
            $builds[$eachbuild['id']] = array('buildnum' => $eachbuild['buildnum'], 'streetid' => $eachbuild['streetid']);
        }
        foreach ($allstreets as $io4 => $eachstreet) {
            $streets[$eachstreet['id']] = array('streetname' => $eachstreet['streetname'], 'cityid' => $eachstreet['cityid']);
        }

        foreach ($address as $io5 => $eachaddress) {
            $apartment = $apts[$eachaddress['aptid']]['apt'];
            $building = $builds[$apts[$eachaddress['aptid']]['buildid']]['buildnum'];
            $streetname = $streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['streetname'];
            $cityid = $streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['cityid'];
            // zero apt handle
            if ($alterconf['ZERO_TOLERANCE']) {
                if ($apartment == 0) {
                    $apartment_filtered = '';
                } else {
                    $apartment_filtered = '/' . $apartment;
                }
            } else {
                $apartment_filtered = '/' . $apartment;
            }

            if (!$alterconf['CITY_DISPLAY']) {
                $result[$eachaddress['login']] = $streetname . ' ' . $building . $apartment_filtered;
            } else {
                $result[$eachaddress['login']] = $cities[$cityid] . ' ' . $streetname . ' ' . $building . $apartment_filtered;
            }
        }
    }

    return($result);
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
///////////// cache options
    $cacheTime = $alterconf['ADDRESS_CACHE_TIME'];
    $cacheTime = time() - ($cacheTime * 60);
    $cacheName = 'exports/fulladdresslistcache.dat';
    $updateCache = false;
    if (file_exists($cacheName)) {
        $updateCache = false;
        if ((filemtime($cacheName) > $cacheTime)) {
            $updateCache = false;
        } else {
            $updateCache = true;
        }
    } else {
        $updateCache = true;
    }

/////////////////////////////////////////////////

    if (!$updateCache) {
        //read data directly from cache
        $result = array();
        $rawData = file_get_contents($cacheName);
        if (!empty($rawData)) {
            $result = unserialize($rawData);
        }
        return ($result);
    } else {
//processing address extracting and store to cache
        $result = array();
        $apts = array();
        $builds = array();
        $city_q = "SELECT * from `city`";
        $adrz_q = "SELECT * from `address`";
        $apt_q = "SELECT * from `apt`";
        $build_q = "SELECT * from build";
        $streets_q = "SELECT * from `street`";
        $alladdrz = simple_queryall($adrz_q);
        $allapt = simple_queryall($apt_q);
        $allbuilds = simple_queryall($build_q);
        $allstreets = simple_queryall($streets_q);
        if (!empty($alladdrz)) {
            $cities = zb_AddressGetFullCityNames();

            foreach ($alladdrz as $io1 => $eachaddress) {
                $address[$eachaddress['id']] = array('login' => $eachaddress['login'], 'aptid' => $eachaddress['aptid']);
            }
            foreach ($allapt as $io2 => $eachapt) {
                $apts[$eachapt['id']] = array('apt' => $eachapt['apt'], 'buildid' => $eachapt['buildid']);
            }
            foreach ($allbuilds as $io3 => $eachbuild) {
                $builds[$eachbuild['id']] = array('buildnum' => $eachbuild['buildnum'], 'streetid' => $eachbuild['streetid']);
            }
            foreach ($allstreets as $io4 => $eachstreet) {
                $streets[$eachstreet['id']] = array('streetname' => $eachstreet['streetname'], 'cityid' => $eachstreet['cityid']);
            }

            foreach ($address as $io5 => $eachaddress) {
                $apartment = $apts[$eachaddress['aptid']]['apt'];
                $building = $builds[$apts[$eachaddress['aptid']]['buildid']]['buildnum'];
                $streetname = $streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['streetname'];
                $cityid = $streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['cityid'];
                // zero apt handle
                if ($alterconf['ZERO_TOLERANCE']) {
                    if ($apartment == 0) {
                        $apartment_filtered = '';
                    } else {
                        $apartment_filtered = '/' . $apartment;
                    }
                } else {
                    $apartment_filtered = '/' . $apartment;
                }

                if (!$alterconf['CITY_DISPLAY']) {
                    $result[$eachaddress['login']] = $streetname . ' ' . $building . $apartment_filtered;
                } else {
                    $result[$eachaddress['login']] = $cities[$cityid] . ' ' . $streetname . ' ' . $building . $apartment_filtered;
                }
            }
        }
        $newCacheData = serialize($result);
        file_put_contents($cacheName, $newCacheData);
        return($result);
    }
}

/**
 * Returns all addres array in view like login=>city address
 * 
 * @return array
 */
function zb_AddressGetFullCityaddresslist() {
    $alterconf = rcms_parse_ini_file(CONFIG_PATH . 'alter.ini');
    $result = array();
    $apts = array();
    $builds = array();
    $city_q = "SELECT * from `city`";
    $adrz_q = "SELECT * from `address`";
    $apt_q = "SELECT * from `apt`";
    $build_q = "SELECT * from build";
    $streets_q = "SELECT * from `street`";
    $alladdrz = simple_queryall($adrz_q);
    $allapt = simple_queryall($apt_q);
    $allbuilds = simple_queryall($build_q);
    $allstreets = simple_queryall($streets_q);
    if (!empty($alladdrz)) {
        $cities = zb_AddressGetFullCityNames();

        foreach ($alladdrz as $io1 => $eachaddress) {
            $address[$eachaddress['id']] = array('login' => $eachaddress['login'], 'aptid' => $eachaddress['aptid']);
        }
        foreach ($allapt as $io2 => $eachapt) {
            $apts[$eachapt['id']] = array('apt' => $eachapt['apt'], 'buildid' => $eachapt['buildid']);
        }
        foreach ($allbuilds as $io3 => $eachbuild) {
            $builds[$eachbuild['id']] = array('buildnum' => $eachbuild['buildnum'], 'streetid' => $eachbuild['streetid']);
        }
        foreach ($allstreets as $io4 => $eachstreet) {
            $streets[$eachstreet['id']] = array('streetname' => $eachstreet['streetname'], 'cityid' => $eachstreet['cityid']);
        }

        foreach ($address as $io5 => $eachaddress) {
            $apartment = $apts[$eachaddress['aptid']]['apt'];
            $building = $builds[$apts[$eachaddress['aptid']]['buildid']]['buildnum'];
            $streetname = $streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['streetname'];
            $cityid = $streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['cityid'];
            // zero apt handle
            if ($alterconf['ZERO_TOLERANCE']) {
                if ($apartment == 0) {
                    $apartment_filtered = '';
                } else {
                    $apartment_filtered = '/' . $apartment;
                }
            } else {
                $apartment_filtered = '/' . $apartment;
            }

            //only city display option
            $result[$eachaddress['login']] = $cities[$cityid] . ' ' . $streetname . ' ' . $building . $apartment_filtered;
        }
    }

    return($result);
}

/**
 * Returns all user cities as  login=>city
 * 
 * @return array
 */
function zb_AddressGetCityUsers() {
    $result = array();
    $apts = array();
    $builds = array();
    $city_q = "SELECT * from `city`";
    $adrz_q = "SELECT * from `address`";
    $apt_q = "SELECT * from `apt`";
    $build_q = "SELECT * from build";
    $streets_q = "SELECT * from `street`";
    $alladdrz = simple_queryall($adrz_q);
    $allapt = simple_queryall($apt_q);
    $allbuilds = simple_queryall($build_q);
    $allstreets = simple_queryall($streets_q);
    if (!empty($alladdrz)) {
        $cities = zb_AddressGetFullCityNames();

        foreach ($alladdrz as $io1 => $eachaddress) {
            $address[$eachaddress['id']] = array('login' => $eachaddress['login'], 'aptid' => $eachaddress['aptid']);
        }
        foreach ($allapt as $io2 => $eachapt) {
            $apts[$eachapt['id']] = array('apt' => $eachapt['apt'], 'buildid' => $eachapt['buildid']);
        }
        foreach ($allbuilds as $io3 => $eachbuild) {
            $builds[$eachbuild['id']] = array('buildnum' => $eachbuild['buildnum'], 'streetid' => $eachbuild['streetid']);
        }
        foreach ($allstreets as $io4 => $eachstreet) {
            $streets[$eachstreet['id']] = array('streetname' => $eachstreet['streetname'], 'cityid' => $eachstreet['cityid']);
        }

        foreach ($address as $io5 => $eachaddress) {
            $cityid = $streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['cityid'];

            $result[$eachaddress['login']] = $cities[$cityid];
        }
    }

    return($result);
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

/*
 * Build passport data base class
 */

class BuildPassport {

    private $data = array();
    private $ownersArr = array('' => '-');
    private $floorsArr = array('' => '-');
    private $entrancesArr = array('' => '-');

    const EX_NO_OWNERS = 'EMPTY_OWNERS_PARAM';
    const EX_NO_OPTS = 'NOT_ENOUGHT_OPTIONS';

    public function __construct() {
        $this->savePassport();
        $this->loadData();
        $this->loadConfig();
    }

    /**
     * loads all existing builds passport data into private prop
     * 
     * @return void
     */
    protected function loadData() {
        $query = "SELECT * from `buildpassport`";
        $all = simple_queryall($query);
        if (!empty($all)) {
            foreach ($all as $io => $each) {
                $this->data[$each['buildid']] = $each;
            }
        }
    }

    /**
     * load build passport data options
     * 
     * @return void
     */
    protected function loadConfig() {
        global $ubillingConfig;
        $altCfg = $ubillingConfig->getAlter();

        //extracting owners
        if (!empty($altCfg['BUILD_OWNERS'])) {
            $rawOwners = explode(',', $altCfg['BUILD_OWNERS']);
            foreach ($rawOwners as $ia => $eachowner) {
                $this->ownersArr[$eachowner] = $eachowner;
            }
        } else {
            throw new Exception(self::EX_NO_OWNERS);
        }

        //extracting floors and entrances
        if (!empty($altCfg['BUILD_EXTOPTS'])) {
            $rawOpts = explode(',', $altCfg['BUILD_EXTOPTS']);
            if (sizeof($rawOpts) < 3) {
                $maxFloors = $rawOpts[0];
                $maxEntrances = $rawOpts[1];

                for ($floors = 1; $floors <= $maxFloors; $floors++) {
                    $this->floorsArr[$floors] = $floors;
                }

                for ($entrances = 1; $entrances <= $maxEntrances; $entrances++) {
                    $this->entrancesArr[$entrances] = $entrances;
                }
            } else {
                throw new Exception(self::EX_NO_OPTS);
            }
        } else {
            throw new Exception(self::EX_NO_OPTS);
        }
    }

    /**
     * returns some build passport edit form
     * 
     * @praram $buildid existing build id
     * 
     * @return string
     */
    public function renderEditForm($buildid) {

        $buildid = vf($buildid, 3);

        if (isset($this->data[$buildid])) {
            $currentData = $this->data[$buildid];
        } else {
            $currentData = array();
        }

        $inputs = wf_HiddenInput('savebuildpassport', $buildid);
        $inputs.= wf_Selector('powner', $this->ownersArr, __('Owner'), @$currentData['owner'], true);
        $inputs.= wf_TextInput('pownername', __('Owner name'), @$currentData['ownername'], true, 30);
        $inputs.= wf_TextInput('pownerphone', __('Owner phone'), @$currentData['ownerphone'], true, 30);
        $inputs.= wf_TextInput('pownercontact', __('Owner contact person'), @$currentData['ownercontact'], true, 30);
        $keys = (@$currentData['keys'] == 1) ? true : false;
        $inputs.= wf_CheckInput('pkeys', __('Keys available'), true, $keys);
        $inputs.= wf_TextInput('paccessnotices', __('Build access notices'), @$currentData['accessnotices'], true, 40);
        $inputs.= wf_Selector('pfloors', $this->floorsArr, __('Floors'), @$currentData['floors'], false);
        $inputs.= wf_Selector('pentrances', $this->entrancesArr, __('Entrances'), @$currentData['entrances'], false);
        $inputs.= wf_TextInput('papts', __('Apartments'), @$currentData['apts'], true, 5);

        $inputs.= __('Notes') . wf_tag('br');
        $inputs.= wf_TextArea('pnotes', '', @$currentData['notes'], true, '50x6');
        $inputs.= wf_Submit(__('Save'));

        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    /**
     * saves new passport data for some build
     * 
     * @return void
     */
    protected function savePassport() {
        if (wf_CheckPost(array('savebuildpassport'))) {
            $buildid = vf($_POST['savebuildpassport'], 3);

            // Yep, im know - thats shitty solution. Need to refactor this later.
            $clean_query = "DELETE FROM `buildpassport` WHERE `buildid`='" . $buildid . "';";
            nr_query($clean_query);

            $owner = mysql_real_escape_string($_POST['powner']);
            $ownername = mysql_real_escape_string($_POST['pownername']);
            $ownerphone = mysql_real_escape_string($_POST['pownerphone']);
            $ownercontact = mysql_real_escape_string($_POST['pownercontact']);
            $keys = (isset($_POST['pkeys'])) ? 1 : 0;
            $accessnotices = mysql_real_escape_string($_POST['paccessnotices']);
            $floors = mysql_real_escape_string($_POST['pfloors']);
            $entrances = mysql_real_escape_string($_POST['pentrances']);
            $apts = mysql_real_escape_string($_POST['papts']);
            $notes = mysql_real_escape_string($_POST['pnotes']);

            $query = "INSERT INTO `buildpassport` (
                                `id` ,
                                `buildid` ,
                                `owner` ,
                                `ownername` ,
                                `ownerphone` ,
                                `ownercontact` ,
                                `keys` ,
                                `accessnotices` ,
                                `floors` ,
                                `apts` ,
                                `entrances` ,
                                `notes`
                                )
                                VALUES (
                                NULL ,
                                '" . $buildid . "',
                                '" . $owner . "',
                                '" . $ownername . "',
                                '" . $ownerphone . "',
                                '" . $ownercontact . "',
                                '" . $keys . "',
                                '" . $accessnotices . "',
                                '" . $floors . "',
                                '" . $apts . "',
                                '" . $entrances . "',
                                '" . $notes . "'
                                );
                        ";
            nr_query($query);
            log_register('BUILD PASSPORT SAVE [' . $buildid . ']');
        }
    }

}

?>
