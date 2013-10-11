<?php

/*
 * Address workaround API
 *
 */

/*
 * City functions
 * Create, delete, change, get data, list
 * 
 */

function zb_AddressCreateCity($cityname,$cityalias) {
    $cityname=mysql_real_escape_string($cityname);
    $cityalias=vf($cityalias);
    $query="
    INSERT INTO `city`
    (`id`,`cityname`,`cityalias`)
    VALUES
    (NULL, '".$cityname."','".$cityalias."');
    ";
    nr_query($query);
    log_register('CREATE AddressCity '.$cityname.' '.$cityalias);
}


function zb_AddressDeleteCity($cityid) {
    $cityid=vf($cityid);
    $query="DELETE from `city` WHERE `id` = '".$cityid."';";
    nr_query($query);
    log_register('DELETE AddressCity '.$cityid);
}

function zb_AddressChangeCityName($cityid,$cityname) {
    $cityid=vf($cityid);
    $cityname=mysql_real_escape_string($cityname);
    $query="UPDATE `city` SET `cityname` = '".$cityname."' WHERE `id`= '".$cityid."' ;";
    nr_query($query);
    log_register('CHANGE AddressCityName '.$cityid.' '.$cityname);
}


function zb_AddressChangeCityAlias($cityid,$cityalias) {
    $cityid=vf($cityid);
    $cityalias=vf($cityalias);
    $query="UPDATE `city` SET `cityalias` = '".$cityalias."' WHERE `id`= '".$cityid."' ;";
    nr_query($query);
    log_register('CHANGE AddressCityAlias '.$cityid.' '.$cityalias);
}

function zb_AddressGetCityData($cityid) {
    $cityid=vf($cityid);
    $query="SELECT * from `city` WHERE `id`='".$cityid."'";
    $city_data=simple_query($query);
    return ($city_data);
}

function zb_AddressListCityAllIds() {
    $query="SELECT `id` from `city`";
    $all_ids=simple_queryall($query);
    return($all_ids);
}

function zb_AddressGetCityAllData() {
    $query="SELECT * from `city` ORDER by `id` ASC";
    $all_data=simple_queryall($query);
    return($all_data);
}

function zb_AddressGetFullCityNames() {
    $query="SELECT * from `city`";
    $result=array();
    $all_data=simple_queryall($query);
    if (!empty ($all_data)) {
        foreach ($all_data as $io=>$eachcity) {
            $result[$eachcity['id']]=$eachcity['cityname'];
        }
    }
    
    return($result);
}



/*
 * Streets functions
 * create, delete, change, get data, list
 */

function zb_AddressCreateStreet($cityid,$streetname,$streetalias) {
    $streetname=mysql_real_escape_string($streetname);
    $streetalias=vf($streetalias);
    $cityid=vf($cityid);
    $query="
    INSERT INTO `street`
    (`id`,`cityid`,`streetname`,`streetalias`)
    VALUES
    (NULL, '".$cityid."','".$streetname."','".$streetalias."');
    ";
    nr_query($query);
    log_register('CREATE AddressStreet '.$cityid.' '.$streetname.' '.$streetalias);
}


function zb_AddressDeleteStreet($streetid) {
    $streetid=vf($streetid);
    $query="DELETE from `street` WHERE `id` = '".$streetid."';";
    nr_query($query);
    log_register('DELETE AddressStreet '.$streetid);
}


function zb_AddressChangeStreetName($streetid,$streetname) {
    $streetid=vf($streetid);
    $streetname=mysql_real_escape_string($streetname);
    $query="UPDATE `street` SET `streetname` = '".$streetname."' WHERE `id`= '".$streetid."' ;";
    nr_query($query);
    log_register('CHANGE AddressStreetName '.$streetid.' '.$streetname);
}


function zb_AddressChangeStreetAlias($streetid,$streetalias) {
    $streetid=vf($streetid);
    $streetalias=mysql_real_escape_string($streetalias);
    $query="UPDATE `street` SET `streetalias` = '".$streetalias."' WHERE `id`= '".$streetid."' ;";
    nr_query($query);
    log_register('CHANGE AddressStreetAlias '.$streetid.' '.$streetalias);
}

function zb_AddressGetStreetData($streetid) {
    $streetid=vf($streetid);
    $query="SELECT * from `street` WHERE `id`='".$streetid."'";
    $street_data=simple_query($query);
    return ($street_data);
}

function zb_AddressListStreetAllIds() {
    $query="SELECT `id` from `street`";
    $all_ids=simple_queryall($query);
    return($all_ids);
}

function zb_AddressGetStreetAllData() {
    $query="SELECT * from `street`";
    $all_data=simple_queryall($query);
    return($all_data);
}

function zb_AddressGetStreetAllDataByCity($cityid) {
    $query="SELECT * from `street` where `cityid`='".$cityid."' ORDER BY `streetname`";
    $all_data=simple_queryall($query);
    return($all_data);
}


/*
 * Building functions
 * Create, Delete, Change, Get, List
 */

function zb_AddressCreateBuild($streetid,$buildnum) {
    $buildnum=mysql_real_escape_string($buildnum);
    $streetid=vf($streetid);
    $query="
    INSERT INTO `build`
    (`id`,`streetid`,`buildnum`)
    VALUES
    (NULL, '".$streetid."','".$buildnum."');
    ";
    nr_query($query);
    log_register('CREATE AddressBuild '.$streetid.' '.$buildnum);
}

function zb_AddressDeleteBuild($buildid) {
    $buildid=vf($buildid);
    $query="DELETE from `build` WHERE `id` = '".$buildid."';";
    nr_query($query);
    log_register('DELETE AddressBuild '.$buildid);
}

function zb_AddressBuildProtected($buildid) {
    $buildid=vf($buildid,3);
    $query="SELECT * from `apt` WHERE `buildid`='".$buildid."'";
    $result=simple_queryall($query);
    if (!empty ($result)) {
        return (true);
    } else {
        return (false);
    }
 }
 
 function zb_AddressStreetProtected($streetid) {
    $streetid=vf($streetid,3);
    $query="SELECT * from `build` WHERE `streetid`='".$streetid."'";
    $result=simple_queryall($query);
    if (!empty ($result)) {
        return (true);
    } else {
        return (false);
    }
 }
 
 function zb_AddressCityProtected($cityid) {
    $cityid=vf($cityid,3);
    $query="SELECT * from `street` WHERE `cityid`='".$cityid."'";
    $result=simple_queryall($query);
    if (!empty ($result)) {
        return (true);
    } else {
        return (false);
    }
 }

function zb_AddressChangeBuildNum($buildid,$buildnum) {
    $buildid=vf($buildid);
    $buildnum=mysql_real_escape_string($buildnum);
    $query="UPDATE `build` SET `buildnum` = '".$buildnum."' WHERE `id`= '".$buildid."' ;";
    nr_query($query);
    log_register('CHANGE AddressBuildNum '.$buildid.' '.$buildnum);
}

function zb_AddressGetBuildData($buildid) {
    $buildid=vf($buildid);
    $query="SELECT * from `build` WHERE `id`='".$buildid."'";
    $build_data=simple_query($query);
    return ($build_data);
}

function zb_AddressListBuildAllIds() {
    $query="SELECT `id` from `build`";
    $all_ids=simple_queryall($query);
    return($all_ids);
}

function zb_AddressGetBuildAllData() {
    $query="SELECT * from `build`";
    $all_data=simple_queryall($query);
    return($all_data);
}

function zb_AddressGetBuildAllDataByStreet($streetid) {
    $query="SELECT * from `build` where `streetid`='".$streetid."' ORDER by `buildnum`+0 ASC";
    $all_data=simple_queryall($query);
    return($all_data);
}

function zb_AddressGetAptAllDataByBuild($buildid) {
    $query="SELECT * from `apt` where `buildid`='".$buildid."' ORDER by `apt`+0 ASC";
    $all_data=simple_queryall($query);
    return($all_data);
}

/*
 * Apartment functions
 * Create, Delete, Change, Get, List
 */

function zb_UserGetFullAddress($login) {
 $alladdress=zb_AddressGetFulladdresslist();
 @$address=$alladdress[$login];
 return ($address);
}

function zb_AddressCreateApartment($buildid,$entrance,$floor,$apt) {
    $buildid=vf($buildid);
    $entrance=mysql_real_escape_string($entrance);
    $floor=mysql_real_escape_string($floor);
    $apt=mysql_real_escape_string($apt);
    $query="INSERT INTO `apt`
         (`id`,`buildid`,`entrance`,`floor`,`apt`)
         VALUES
         (NULL,'".$buildid."','".$entrance."','".$floor."','".$apt."');
        ";
    nr_query($query);
    log_register('CREATE AddressApartment ['.$buildid.'] `'.$entrance.'` `'.$floor.'` `'.$apt.'`');
}


function zb_AddressDeleteApartment($aptid) {
    $aptid=vf($aptid);
    $query="DELETE from `apt` WHERE `id` = '".$aptid."';";
    nr_query($query);
    log_register('DELETE AddressApartment '.$aptid);
}

function zb_AddressChangeApartment($aptid,$buildid,$entrance,$floor,$apt) {
    $aptid=vf($aptid);
    $buildid=vf($buildid);
    $entrance=mysql_real_escape_string($entrance);
    $floor=mysql_real_escape_string($floor);
    $apt=mysql_real_escape_string($apt);
    $query="
        UPDATE `apt`
        SET
        `buildid` = '".$buildid."',
        `entrance` = '".$entrance."',
        `floor` = '".$floor."',
        `apt` = '".$apt."'
        WHERE `id` ='".$aptid."';
        ";
    nr_query($query);
    log_register('CHANGE AddressApartment '.$aptid.' '.$buildid.' '.$entrance.' '.$floor.' '.$apt);
}


function zb_AddressCreateAddress($login,$aptid) {
// zaebis notacia - da? :) ^^^^

   $login=vf($login);
   $aptid=vf($aptid);
   $query="
    INSERT INTO `address`
    (`id`,`login`,`aptid`)
    VALUES
    (NULL, '".$login."','".$aptid."');
    ";
    nr_query($query);
    log_register('CREATE AddressOccupancy ('.$login.') ['.$aptid.']');

}

function zb_AddressDeleteAddress($addrid) {
    $addrid=vf($addrid);
    $query="DELETE from `address` WHERE `id` = '".$addrid."';";
    nr_query($query);
    log_register('DELETE AddressOccupancy '.$addrid);
}


function zb_AddressOrphanUser($login) {
    $login=vf($login);
    $query="DELETE from `address` WHERE `login` = '".$login."';";
    nr_query($query);
    log_register('ORPHAN AddressOccupancy '.$login);
}

function zb_AddressGetLastid() {
    $query="SELECT * FROM `apt` ORDER BY `id` DESC LIMIT 0,1";
    $lastid=simple_query($query);
    return($lastid['id']);
}

function zb_AddressGetAptData($login) {
    $login=vf($login);
    $result=array();
    $aptid_query="SELECT `aptid`,`id` from `address` where `login`='".$login."'";
    $aptid=simple_query($aptid_query);
  //  @$addressid=$aptid['id'];
    @$aptid=$aptid['aptid'];
   
    if (!empty ($aptid)) {
        $query="SELECT * from `apt` where `id`='".$aptid."'";
        $result=simple_query($query);
        $result['aptid']=$aptid;
       // $result['addressid']=$addressid;
    }
    
    return($result);
}

function zb_AddressGetAptDataById($aptid) {
    $aptid=vf($aptid);
    $result=array();
    $query="SELECT * from `apt` where `id`='".$aptid."'";
    $result=simple_query($query);
    return($result);
}
//////////////////////////////////////////// web functions (forms etc)
    function web_CitySelector() {
        $allcity=zb_AddressGetCityAllData();
        $selector='<select name="citysel">';
        if (!empty ($allcity)) {
            foreach ($allcity as $io=>$eachcity) {
                $selector.='<option value="'.$eachcity['id'].'">'.$eachcity['cityname'].'</option>';
            }
        }
        $selector.='</select>';
        return ($selector);
    }
    
      function web_CitySelectorAc() {
        $allcity=zb_AddressGetCityAllData();
        $selector='<select name="citysel" onChange="this.form.submit();">';
        $selector.='<option value="-">-</option>';
        if (!empty ($allcity)) {
            foreach ($allcity as $io=>$eachcity) {
                $selector.='<option value="'.$eachcity['id'].'">'.$eachcity['cityname'].'</option>';
            }
        }
        $selector.='</select>  <a href="?module=city">'.  web_city_icon().'</a>';
        return ($selector);
    }
    

        function web_StreetSelector($cityid) {
        $allstreets=  zb_AddressGetStreetAllDataByCity($cityid);
        $selector='<select name="streetsel">';
        if (!empty ($allstreets)) {
            foreach ($allstreets as $io=>$eachstreet) {
                $selector.='<option value="'.$eachstreet['id'].'">'.$eachstreet['streetname'].'</option>';
            }
        }
        $selector.='</select>';
        return ($selector);
        }

         function web_StreetSelectorAc($cityid) {
        $allstreets=  zb_AddressGetStreetAllDataByCity($cityid);
        $selector='<select name="streetsel" onChange="this.form.submit();">';
         $selector.='<option value="-">-</option>';
        if (!empty ($allstreets)) {
            foreach ($allstreets as $io=>$eachstreet) {
                $selector.='<option value="'.$eachstreet['id'].'">'.$eachstreet['streetname'].'</option>';
            }
        }
        $selector.='</select> <a href="?module=streets">'.  web_street_icon().'</a>';
        return ($selector);
        }

       function web_BuildSelector($streetid) {
        $allbuilds=zb_AddressGetBuildAllDataByStreet($streetid);
        $selector='<select name="buildsel">';
        if (!empty ($allbuilds)) {
            foreach ($allbuilds as $io=>$eachbuild) {
               $selector.='<option value="'.$eachbuild['id'].'">'.$eachbuild['buildnum'].'</option>';
            }
        }
        $selector.='</select>';
        return ($selector);
    }

       function web_BuildSelectorAc($streetid) {
        $allbuilds=zb_AddressGetBuildAllDataByStreet($streetid);
        $selector='<select name="buildsel" onChange="this.form.submit();">';
         $selector.='<option value="-">-</option>';
        if (!empty ($allbuilds)) {
            foreach ($allbuilds as $io=>$eachbuild) {
               $selector.='<option value="'.$eachbuild['id'].'">'.$eachbuild['buildnum'].'</option>';
            }
        }
        $selector.='</select> <a href="?module=builds&action=edit&streetid='.$streetid.'">'.  web_build_icon().'</a>';
        return ($selector);
    }
    
      function web_AptSelectorAc($buildid) {
        $allapts=zb_AddressGetAptAllDataByBuild($buildid);
        $selector='<select name="aptsel" onChange="this.form.submit();">';
         $selector.='<option value="-">-</option>';
        if (!empty ($allapts)) {
            foreach ($allapts as $io=>$eachapt) {
               $selector.='<option value="'.$eachapt['id'].'">'.$eachapt['apt'].'</option>';
            }
        }
        $selector.='</select>';
        return ($selector);
    }

    function web_StreetCreateForm() {
      $cities=  simple_query("SELECT `id` from `city`"); 
        if (!empty($cities)) {
        $inputs=  web_CitySelector().' '.__('City').  wf_delimiter();
        $inputs.=wf_TextInput('newstreetname', __('New Street name').wf_tag('sup').'*'.  wf_tag('sup', true) , '', true, '20');
        $inputs.=wf_TextInput('newstreetalias', __('New Street alias') , '', true, '20');
        $inputs.=wf_Submit(__('Create'));
        $form=  wf_Form('', 'POST', $inputs, 'glamour');
        } else {
            $form=__('No added cities - they will need to create a street');
        }
        return($form);
    }

    function web_StreetLister() {
    $allstreets=zb_AddressGetStreetAllData();
    $form='<table width="100%" border="0" class="sortable">';
    $form.='
        <tr class="row1">
            <td>'.__('ID').'</td>
            <td>'.__('City').'</td>
            <td>'.__('Street name').'</td>
            <td>'.__('Street alias').'</td>
            <td>'.__('Actions').'</td>
        </tr>
        ';
    if (!empty ($allstreets)) {
        foreach ($allstreets as $io=>$eachstreet) {
        $cityname=zb_AddressGetCityData($eachstreet['cityid']);
        $form.='
        <tr class="row3">
            <td>'.$eachstreet['id'].'</td>
            <td>'.$cityname['cityname'].'</td>
            <td>'.$eachstreet['streetname'].'</td>
            <td>'.$eachstreet['streetalias'].'</td>
            <td>
            '.  wf_JSAlert('?module=streets&action=delete&streetid='.$eachstreet['id'], web_delete_icon(), 'Removing this may lead to irreparable results').'
            '.  wf_JSAlert('?module=streets&action=edit&streetid='.$eachstreet['id'], web_edit_icon(), 'Are you serious').'
                <a href="?module=builds&action=edit&streetid='.$eachstreet['id'].'">'.web_build_icon().'</a>
            </td>
        </tr>
        ';
        }
    }
    $form.='</table>';
    return($form);
    }

    function web_StreetListerBuildsEdit() {
    $allstreets=zb_AddressGetStreetAllData();
    
    $cells=  wf_TableCell(__('ID'));
    $cells.= wf_TableCell(__('City'));
    $cells.= wf_TableCell(__('Street name'));
    $cells.= wf_TableCell(__('Street alias'));
    $cells.= wf_TableCell(__('Actions'));
    $rows= wf_TableRow($cells, 'row1');
    
    
    if (!empty ($allstreets)) {
        foreach ($allstreets as $io=>$eachstreet) {
        $cityname=zb_AddressGetCityData($eachstreet['cityid']);

            $cells=  wf_TableCell($eachstreet['id']);
            $cells.= wf_TableCell($cityname['cityname']);
            $cells.= wf_TableCell($eachstreet['streetname']);
            $cells.= wf_TableCell($eachstreet['streetalias']);
            $actlink=  wf_Link('?module=builds&action=edit&streetid='.$eachstreet['id'], web_build_icon(), false);
            $cells.= wf_TableCell($actlink);
            $rows.=  wf_TableRow($cells, 'row3');
        }
    }
    
    $result=  wf_TableBody($rows, '100%', 0, 'sortable');
    
    return($result);
    }

    function web_BuildLister($streetid) {
        $allbuilds=zb_AddressGetBuildAllDataByStreet($streetid);
        
        $cells=  wf_TableCell(__('ID'));
        $cells.= wf_TableCell(__('Building number'));
        $cells.= wf_TableCell(__('Geo location'));
        $cells.= wf_TableCell(__('Actions'));
        $rows=  wf_TableRow($cells, 'row1');
        
        if (!empty ($allbuilds)) {
            foreach ($allbuilds as $io=>$eachbuild) {
            $cells=  wf_TableCell($eachbuild['id']);
            $cells.= wf_TableCell($eachbuild['buildnum']);
            $cells.= wf_TableCell($eachbuild['geo']);
            $acts=   wf_JSAlert('?module=builds&action=delete&streetid='.$streetid.'&buildid='.$eachbuild['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
            $acts.=''.wf_JSAlert('?module=builds&action=editbuild&streetid='.$streetid.'&buildid='.$eachbuild['id'], web_edit_icon(), 'Are you serious');
            if (!empty($eachbuild['geo'])) {
                $acts.=' '.wf_Link("?module=usersmap&findbuild=".$eachbuild['geo'], wf_img('skins/icon_search_small.gif', __('Find on map')), false);
            }
            $cells.= wf_TableCell($acts);
            $rows.=  wf_TableRow($cells, 'row3');
            
            }
          }
         $result=  wf_TableBody($rows, '100%', 0, 'sortable');
         return ($result);
    }

    function web_BuildAddForm() {
        $inputs=  wf_TextInput('newbuildnum', __('New build number'), '', true,10);
        $inputs.= wf_Submit(__('Create'));
        $form=  wf_Form("", 'POST', $inputs, 'glamour');
        return($form);
    }


    function web_StreetEditForm($streetid) {
        $streetdata=zb_AddressGetStreetData($streetid);
        $streetname=$streetdata['streetname'];
        $streetalias=$streetdata['streetalias'];
        $form='
            <form action="" method="POST">
            <input type="text" name="editstreetname" value="'.$streetname.'"> '.__('Street name').'<sup>*</sup> <br>
            <input type="text" name="editstreetalias" value="'.$streetalias.'"> '.__('Street alias').' <br>
            <input type="submit" value="'.__('Save').'">
            </form>
            ';
        return($form);
    }

    function web_AptCreateForm() {
            $form='
                <input type="text" name="entrance"> '.__('Entrance').'<br>
                <input type="text" name="floor"> '.__('Floor').'<br>
                <input type="text" id="apt" name="apt" onchange="checkapt();"> '.__('Apartment').'<br>
                ';
              return($form);
    }

// returns all addres array in view like login=>address
function zb_AddressGetFulladdresslist() {
$alterconf=rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
$result=array();
$apts=array();
$builds=array();
$city_q="SELECT * from `city`";
$adrz_q="SELECT * from `address`";
$apt_q="SELECT * from `apt`";
$build_q="SELECT * from build";
$streets_q="SELECT * from `street`";
$alladdrz=simple_queryall($adrz_q);
$allapt=simple_queryall($apt_q);
$allbuilds=simple_queryall($build_q);
$allstreets=simple_queryall($streets_q);
if (!empty ($alladdrz)) {
    $cities=zb_AddressGetFullCityNames();
    
        foreach ($alladdrz as $io1=>$eachaddress) {
        $address[$eachaddress['id']]=array('login'=>$eachaddress['login'],'aptid'=>$eachaddress['aptid']);
        }
        foreach ($allapt as $io2=>$eachapt) {
        $apts[$eachapt['id']]=array('apt'=>$eachapt['apt'],'buildid'=>$eachapt['buildid']);
        }
        foreach ($allbuilds as $io3=>$eachbuild) {
        $builds[$eachbuild['id']]=array('buildnum'=>$eachbuild['buildnum'],'streetid'=>$eachbuild['streetid']);
        }
        foreach ($allstreets as $io4=>$eachstreet) {
        $streets[$eachstreet['id']]=array('streetname'=>$eachstreet['streetname'],'cityid'=>$eachstreet['cityid']);
        }

    foreach ($address as $io5=>$eachaddress) {
        $apartment=$apts[$eachaddress['aptid']]['apt'];
        $building=$builds[$apts[$eachaddress['aptid']]['buildid']]['buildnum'];
        $streetname=$streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['streetname'];
        $cityid=$streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['cityid'];
        // zero apt handle
        if ($alterconf['ZERO_TOLERANCE']) {
            if ($apartment==0) {
            $apartment_filtered='';
            } else {
            $apartment_filtered='/'.$apartment;
            }
        } else {
        $apartment_filtered='/'.$apartment;    
        }
    
        if (!$alterconf['CITY_DISPLAY']) {
        $result[$eachaddress['login']]=$streetname.' '.$building.$apartment_filtered;
        } else {
        $result[$eachaddress['login']]=$cities[$cityid].' '.$streetname.' '.$building.$apartment_filtered;
        }
    }
}

return($result);
}

// returns all addres array in view like login=>city address
function zb_AddressGetFullCityaddresslist() {
$alterconf=rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
$result=array();
$apts=array();
$builds=array();
$city_q="SELECT * from `city`";
$adrz_q="SELECT * from `address`";
$apt_q="SELECT * from `apt`";
$build_q="SELECT * from build";
$streets_q="SELECT * from `street`";
$alladdrz=simple_queryall($adrz_q);
$allapt=simple_queryall($apt_q);
$allbuilds=simple_queryall($build_q);
$allstreets=simple_queryall($streets_q);
if (!empty ($alladdrz)) {
    $cities=zb_AddressGetFullCityNames();
    
        foreach ($alladdrz as $io1=>$eachaddress) {
        $address[$eachaddress['id']]=array('login'=>$eachaddress['login'],'aptid'=>$eachaddress['aptid']);
        }
        foreach ($allapt as $io2=>$eachapt) {
        $apts[$eachapt['id']]=array('apt'=>$eachapt['apt'],'buildid'=>$eachapt['buildid']);
        }
        foreach ($allbuilds as $io3=>$eachbuild) {
        $builds[$eachbuild['id']]=array('buildnum'=>$eachbuild['buildnum'],'streetid'=>$eachbuild['streetid']);
        }
        foreach ($allstreets as $io4=>$eachstreet) {
        $streets[$eachstreet['id']]=array('streetname'=>$eachstreet['streetname'],'cityid'=>$eachstreet['cityid']);
        }

    foreach ($address as $io5=>$eachaddress) {
        $apartment=$apts[$eachaddress['aptid']]['apt'];
        $building=$builds[$apts[$eachaddress['aptid']]['buildid']]['buildnum'];
        $streetname=$streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['streetname'];
        $cityid=$streets[$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid']]['cityid'];
        // zero apt handle
        if ($alterconf['ZERO_TOLERANCE']) {
            if ($apartment==0) {
            $apartment_filtered='';
            } else {
            $apartment_filtered='/'.$apartment;
            }
        } else {
        $apartment_filtered='/'.$apartment;    
        }
    
       //only city display option
        $result[$eachaddress['login']]=$cities[$cityid].' '.$streetname.' '.$building.$apartment_filtered;
     
    }
}

return($result);
}

?>
