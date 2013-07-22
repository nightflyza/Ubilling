<?php

////////////////////// switch models managment

/*
 * Returns array of all currently dead devices
 * 
 * @return array
 */

function zb_SwitchesGetAllDead() {
        $dead_switches_raw=  zb_StorageGet('SWDEAD');
        if (!$dead_switches_raw) {
            $result=array();
        } else {
            $result=  unserialize($dead_switches_raw);
        }
        return ($result);
}

/*
 * Returns array of each curently dead switches death time
 * 
 * @return array
 */

function zb_SwitchesGetAllDeathTime() {
    $result=array();
    $query="SELECT `ip`,`date` from `deathtime`";
    $all=  simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io=>$each) {
            $result[$each['ip']]=$each['date'];
        }
    }
    
    return ($result);
}

/*
 * Function than sets dead switch time
 * 
 * @param $ip Switch IP
 * 
 * @return void
 */

function zb_SwitchDeathTimeSet($ip) {
    $ip=  mysql_real_escape_string($ip);
    $curdatetime=  curdatetime();
    $query="INSERT INTO `deathtime` (`id` ,`ip` ,`date`) VALUES (NULL , '".$ip."', '".$curdatetime."');";
    nr_query($query);
}

/*
 * Function than resurrects dead switch :)
 * 
 * @param $ip Switch IP
 * 
 * @return void
 */

function zb_SwitchDeathTimeResurrection($ip) {
    $ip=  mysql_real_escape_string($ip);
    $query="DELETE from `deathtime` WHERE `ip`='".$ip."'";
    nr_query($query);
}

function zb_SwitchModelsSnmpTemplatesGetAll() {
    $allSnmpTemplates_raw=  sp_SnmpGetAllModelTemplates();
    $allSnmpTemplates=array(''=>__('No'));
    if (!empty($allSnmpTemplates_raw)) {
        foreach ($allSnmpTemplates_raw as $io=>$each) {
            $allSnmpTemplates[$io]=$each['define']['DEVICE'];
        }
    }
    return ($allSnmpTemplates);
}

function web_SwitchModelAddForm() {
    $allSnmpTemplates=zb_SwitchModelsSnmpTemplatesGetAll();
    $addinputs=wf_TextInput('newsm', 'Model', '', true);
    $addinputs.=wf_TextInput('newsmp', 'Ports', '', true,'5');
    $addinputs.=wf_Selector('newsst', $allSnmpTemplates, 'SNMP template', '');
    $addinputs.=wf_delimiter().web_add_icon().' '.wf_Submit('Create');
    $addform=wf_Form('', 'POST', $addinputs, 'glamour');
    $result=$addform;
    return ($result);
}

function web_SwitchModelsShow() {
	$query='SELECT * from `switchmodels`';
	$allmodels=simple_queryall($query);
                
        $tablecells=wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('Model'));
        $tablecells.=wf_TableCell(__('Ports'));
        $tablecells.=wf_TableCell(__('SNMP template'));
        $tablecells.=wf_TableCell(__('Actions'));
        $tablerows=wf_TableRow($tablecells, 'row1');
        
        
	if (!empty($allmodels)) {
	foreach ($allmodels as $io=>$eachmodel) {
            
        $tablecells=wf_TableCell($eachmodel['id']);
        $tablecells.=wf_TableCell($eachmodel['modelname']);
        $tablecells.=wf_TableCell($eachmodel['ports']);
        $tablecells.=wf_TableCell($eachmodel['snmptemplate']);
        $switchmodelcontrols=wf_JSAlert('?module=switchmodels&deletesm='.$eachmodel['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
        $switchmodelcontrols.=wf_Link('?module=switchmodels&edit='.$eachmodel['id'], web_edit_icon());
        $tablecells.=wf_TableCell($switchmodelcontrols);
        $tablerows.=wf_TableRow($tablecells, 'row3');
 	   }
	}
        
    $result=wf_TableBody($tablerows, '100%', '0', 'sortable');




return ($result);
}

function zb_SwitchModelsGetAll() {
    $query="SELECT * from `switchmodels`";
    $result=simple_queryall($query);
    return ($result);
}

function zb_SwitchModelGetData($modelid) {
    $modelid=vf($modelid,3);
    $query="SELECT * from `switchmodels` where `id`='".$modelid."'";
    $result=simple_query($query);
    return ($result);
}

function web_SwitchSelector($name='switchid') {
    $allswitches=zb_SwitchesGetAll();
    $selector='<select name="'.$name.'">';
    if (!empty ($allswitches)) {
        foreach ($allswitches as $io=>$eachswitch) {
            $selector.='<option value="'.$eachswitch['id'].'">'.$eachswitch['location'].'</option>';
            }
    }
    $selector.='</select>';    
    return ($selector);
}


function web_SwitchModelSelector($selectname='switchmodelid') {
    $allmodels=zb_SwitchModelsGetAll();
    $selector='<select name="'.$selectname.'">';
            if (!empty ($allmodels)) {
                foreach ($allmodels as $io=>$eachmodel) {
                    $selector.='<option value="'.$eachmodel['id'].'">'.$eachmodel['modelname'].'</option>';
                }
            }        
    $selector.='</select>';
    return ($selector);
}

function ub_SwitchModelAdd($name,$ports,$snmptemplate='') {
    $ports=vf($ports);
    $name=mysql_real_escape_string($name);
    $snmptemplate=  mysql_real_escape_string($snmptemplate);
	$query='
	INSERT INTO `switchmodels` (
                `id` ,
                `modelname` ,
                `ports`,
                `snmptemplate`
                )
                VALUES (
                NULL , "'.$name.'", "'.$ports.'","'.$snmptemplate.'");';
	nr_query($query);
	stg_putlogevent('SWITCHMODEL ADD '.$name);
}

function ub_SwitchModelDelete($modelid) {
        $modelid=vf($modelid);
        $query='DELETE FROM `switchmodels` WHERE `id` = "'.$modelid.'"';
	nr_query($query);
	stg_putlogevent('SWITCHMODEL DELETE  ['.$modelid.']');
	}

function web_SwitchFormAdd() {
    $addinputs=wf_TextInput('newip', 'IP', '', true,20);
    $addinputs.=wf_TextInput('newlocation', 'Location', '', true,30);
    $addinputs.=wf_TextInput('newdesc', 'Description', '', true,30);
    $addinputs.=wf_TextInput('newsnmp', 'SNMP community', '', true,20);
    $addinputs.=wf_TextInput('newgeo', 'Geo location', '', true,20);
    $addinputs.=web_SwitchModelSelector('newswitchmodel').' '.__('Model');
    $addinputs.='<br>';
    $addinputs.=web_add_icon().' '.wf_Submit('Save');
    $addform=wf_Form("", 'POST', $addinputs, 'glamour');
    return($addform);
}

function web_SwitchFormDelete() {
$delform='
    <form action="" METHOD="POST" class="row3">
	'.web_delete_icon().' '.web_SwitchSelector('switchdelete').'
	 <input type="submit" value="'.__('Delete').'">
	</form>';
    return($delform);
}


function zb_SwitchesGetAll() {
    	$query='SELECT * FROM `switches` ORDER BY `id` DESC';
	$allswitches=simple_queryall($query);
        return ($allswitches);
}
//return geo data in ip->geo format
function zb_SwitchesGetAllGeo() {
    $query="SELECT `ip`,`geo` from `switches`";
    $alldata=  simple_queryall($query);
    $result=array();
    if (!empty($alldata)) {
        foreach ($alldata as $io=>$each) {
            $result[$each['ip']]=$each['geo'];
        }
    }
    return ($result);
}

function zb_SwitchGetData($switchid) {
        $switchid=vf($switchid,3);
    	$query="SELECT * FROM `switches` WHERE `id`='".$switchid."' ";
	$result=simple_query($query);
        return ($result);
}


function zb_SwitchModelsGetAllTag() {
    $allmodels=zb_SwitchModelsGetAll();
    $result=array();
    if (!empty ($allmodels)) {
        foreach ($allmodels as $io=>$eachmodel) {
            $result[$eachmodel['id']]=$eachmodel['modelname'];
        }
    }
    return ($result);
}

function zb_PingICMP($ip) {
    $globconf=parse_ini_file(CONFIG_PATH."billing.ini");
    $ping=$globconf['PING'];
    $sudo=$globconf['SUDO'];
    $ping_command=$sudo.' '.$ping.' -i 0.01 -c 1 '.$ip;
    $ping_result=shell_exec($ping_command);
    if (strpos($ping_result, 'ttl')) {
        return (true);
    } else {
        return(false);
    }
}

function zb_SwitchAlive($ip) {
    if (zb_PingICMP($ip)) {
        $result=web_green_led();
    } else {
        $result=web_red_led();
    }
    return ($result);
}

function zb_SwitchesDeadLog($currenttime,$deadSwitches) {
    $date=curdatetime();
    $timestamp=$currenttime;
    $logData=  serialize($deadSwitches);
    $query="INSERT INTO `switchdeadlog` (
                    `id` ,
                    `date` ,
                    `timestamp` ,
                    `swdead`
                    )
                    VALUES (
                    NULL , '".$date."', '".$timestamp."', '".$logData."'
                    );";
    nr_query($query);
}

function zb_SwitchesRepingAll() {
    $allswitches=zb_SwitchesGetAll();
    $deadswitches=array();
    $deathTime=  zb_SwitchesGetAllDeathTime();
    
    
    if (!empty($allswitches)) {
        foreach ($allswitches as $io=>$eachswitch) {
            
              if (!ispos($eachswitch['desc'], 'NP')) {
                    if (!zb_PingICMP($eachswitch['ip'])) {
                    $secondChance=zb_PingICMP($eachswitch['ip']);
                    if (!$secondChance) {
                        $lastChance=zb_PingICMP($eachswitch['ip']);
                        if (!$lastChance) {
                            //yep, switch looks like it really down
                             $deadswitches[$eachswitch['ip']]=$eachswitch['location'];
                             if (!isset($deathTime[$eachswitch['ip']])) {
                                 zb_SwitchDeathTimeSet($eachswitch['ip']);
                             }
                        }
                    }  else {
                        zb_SwitchDeathTimeResurrection($eachswitch['ip']);
                    }
                 
                    } else {
                        zb_SwitchDeathTimeResurrection($eachswitch['ip']);
                    }
                } 
        }
    }
    
    $newdata=serialize($deadswitches);
    zb_StorageSet('SWDEAD', $newdata);
    return ($deadswitches);
    
}
    
        
function web_SwitchesShow() {
    $alterconf=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
        $allswitches=zb_SwitchesGetAll();
        $modelnames=zb_SwitchModelsGetAllTag();
        $currenttime=time();
        $reping_timeout=$alterconf['SW_PINGTIMEOUT'];
        $deathTime=zb_SwitchesGetAllDeathTime();
        
        //non realtime switches pinging
        $last_pingtime=zb_StorageGet('SWPINGTIME');
        
        if (!$last_pingtime) {
            zb_SwitchesRepingAll();
            zb_StorageSet('SWPINGTIME', $currenttime);
            $last_pingtime=$currenttime;
        } else {
            if ($currenttime>($last_pingtime+($reping_timeout*60))) {
            // normal timeout reping sub here
            zb_SwitchesRepingAll();
            zb_StorageSet('SWPINGTIME', $currenttime);
            }
        }
        
        //force total reping and update cache
        if (wf_CheckGet(array('forcereping'))) {
            zb_SwitchesRepingAll();
            zb_StorageSet('SWPINGTIME', $currenttime);
            if (wf_CheckGet(array('ajaxping'))) {
                $dead_raw=zb_StorageGet('SWDEAD');
                $deathTime=  zb_SwitchesGetAllDeathTime();
                $deadarr=array();
                $ajaxResult='';
                if ($dead_raw) {
                    $deadarr=unserialize($dead_raw);
                    if (!empty($deadarr)) {
                    //there is some dead switches
                    $deadcount=sizeof($deadarr);    
                    if ($alterconf['SWYMAP_ENABLED']) {
                        //getting geodata
                        $switchesGeo=  zb_SwitchesGetAllGeo();
                    }
                    //ajax container
                    $ajaxResult.=wf_tag('div', false, '', 'id="switchping"');

                    foreach ($deadarr as $ip=>$switch) {
                        if ($alterconf['SWYMAP_ENABLED']) {
                            if (isset($switchesGeo[$ip])) {
                              if (!empty($switchesGeo[$ip])) {
                              $devicefind= wf_Link('?module=switchmap&finddevice='.$switchesGeo[$ip], wf_img('skins/icon_search_small.gif',__('Find on map'))).' ';   
                              } else {
                                  $devicefind='';
                              }
                            } else {
                              $devicefind='';
                            }

                        } else {
                            $devicefind='';
                        }
                        //check morgue records for death time
                        if (isset($deathTime[$ip])) {
                            $deathClock=  wf_img('skins/clock.png', __('Switch dead since').' '.$deathTime[$ip]).' ';
                        } else {
                            $deathClock='';
                        }
                        //add switch as dead
                        $ajaxResult.=$devicefind.'&nbsp;'.$deathClock.$ip.' - '.$switch.'<br>';

                    }


                        } else {
                        $ajaxResult=__('Switches are okay, everything is fine - I guarantee');
                        }
                

               
                 }
                  $ajaxResult.=wf_delimiter().__('Cache state at time').': '.date("H:i:s");
                  print($ajaxResult);
                  die();
                }
        }
        
        //load dead switches cache
        $dead_switches_raw=  zb_StorageGet('SWDEAD');
        if (!$dead_switches_raw) {
            $dead_switches=array();
        } else {
            $dead_switches=  unserialize($dead_switches_raw);
        }
        
			
        $tablecells=wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('IP'));
        $tablecells.=wf_TableCell(__('Location'));
        $tablecells.=wf_TableCell(__('Active'));
        $tablecells.=wf_TableCell(__('Model'));
        $tablecells.=wf_TableCell(__('SNMP community'));
        $tablecells.=wf_TableCell(__('Geo location'));
        $tablecells.=wf_TableCell(__('Description'));
        $tablecells.=wf_TableCell(__('Actions'));
        $tablerows=wf_TableRow($tablecells, 'row1');
        
	if (!empty($allswitches)) {
            foreach ($allswitches as $io=>$eachswitch) {
                if (isset($dead_switches[$eachswitch['ip']])) {
                  if (isset($deathTime[$eachswitch['ip']])) {
                      $obituary=__('Switch dead since').' '.$deathTime[$eachswitch['ip']];
                  } else {
                      $obituary='';
                  }
                  $aliveled=web_red_led($obituary);
                  $aliveflag='0';  
                } else {
                  $aliveled=  web_green_led();
                  $aliveflag='1';
                }
                
    
                $tablecells=wf_TableCell($eachswitch['id']);
                $tablecells.=wf_TableCell($eachswitch['ip'], '', '', 'sorttable_customkey="'.ip2int($eachswitch['ip']).'"');
                $tablecells.=wf_TableCell($eachswitch['location']);
                $tablecells.=wf_TableCell($aliveled, '', '', 'sorttable_customkey="'.$aliveflag.'"');
                $tablecells.=wf_TableCell(@$modelnames[$eachswitch['modelid']]);
                $tablecells.=wf_TableCell($eachswitch['snmp']);
                $tablecells.=wf_TableCell($eachswitch['geo']);
                $tablecells.=wf_TableCell($eachswitch['desc']);
                $switchcontrols='';
                if (cfr('SWITCHESEDIT')) {
                $switchcontrols.=wf_JSAlert('?module=switches&switchdelete='.$eachswitch['id'], web_delete_icon(), 'Removing this may lead to irreparable results');
                $switchcontrols.=wf_Link('?module=switches&edit='.$eachswitch['id'], web_edit_icon());
                } 
                
                
                if (cfr('SWITCHPOLL')) {
                    if ((!empty($eachswitch['snmp'])) AND (ispos($eachswitch['desc'], 'SWPOLL'))) {
                        $switchcontrols.='&nbsp;'.wf_Link('?module=switchpoller&switchid='.$eachswitch['id'], wf_img('skins/snmp.png', __('SNMP query')));
                    }
                }
                
                if ($alterconf['SWYMAP_ENABLED']) {
                 if (!empty($eachswitch['geo'])) {
                     $switchcontrols.=wf_Link('?module=switchmap&finddevice='.$eachswitch['geo'], wf_img('skins/icon_search_small.gif', __('Find on map')));
                 }
                }
                
                $tablecells.=wf_TableCell($switchcontrols);
                $tablerows.=wf_TableRow($tablecells, 'row3');
                
            }
	 
	}
	$result=wf_TableBody($tablerows, '100%', '0', 'sortable');
	return ($result);
}


function ub_SwitchAdd($modelid,$ip,$desc,$location,$snmp,$geo) {
    $modelid=vf($modelid);
    $ip=mysql_real_escape_string($ip);
    $desc=mysql_real_escape_string($desc);
    $location=mysql_real_escape_string($location);
    $snmp=mysql_real_escape_string($snmp);
	$query="
            INSERT INTO `switches` (
            `id` ,
            `modelid` ,
            `ip` ,
            `desc` ,
            `location` ,
             `snmp`,
             `geo`
            )
            VALUES (
                '', '".$modelid."', '".$ip."', '".$desc."', '".$location."', '".$snmp."','".$geo."'
                );
            ";
	nr_query($query);
        $lastid=  simple_get_lastid('switches');
	stg_putlogevent('SWITCH ADD ['.$lastid.'] IP '.$ip.' ON LOC '.$location);
	show_window(__('Add switch'),__('Was added new switch').' '.$ip.' '.$location);
}



function ub_SwitchDelete($switchid) {
    $switchid=vf($switchid);
    $switchdata=zb_SwitchGetData($switchid);
    $query="DELETE from `switches` WHERE `id`='".$switchid."'";
    nr_query($query);
    log_register('SWITCH DELETE ['.$switchid.'] IP '.$switchdata['ip'].' LOC '.$switchdata['location']);
}


function ub_JGetSwitchDeadLog() {
       $cyear=  curyear();
       
       $query="SELECT `id`,`date`,`timestamp`,`swdead` from `switchdeadlog` WHERE `date` LIKE '".$cyear."-%' ORDER BY `id` ASC";
       $alldead=  simple_queryall($query);
       
       $i=1;
       $logcount=sizeof($alldead);
       $result='';
       
       if (!empty($alldead)) {
           foreach ($alldead as $io=>$eachdead) {
               if ($i!=$logcount) {
                    $thelast=',';
                } else {
                    $thelast='';
                }
               
               $startdate=strtotime($eachdead['date']);
               $startdate=date("Y, n-1, j",$startdate);
               $deadData_raw=$eachdead['swdead'];
               $deadData=  unserialize($deadData_raw);
               $deadcount=sizeof($deadData);
               
               
               $result.="
                      {
                        title: '".date("H:i:s",$eachdead['timestamp'])." - (".$deadcount.")',
                        start: new Date(".$startdate."),
                        end: new Date(".$startdate."),
                        className : 'undone',
                        url: '?module=switches&timemachine=true&snapshot=".$eachdead['id']."'
		      }
                    ".$thelast;
               $i++;
           }
       }
       return ($result);
   } 
   
function ub_SwitchesTimeMachineShowSnapshot($snapshotid) {
    $snapshotid=vf($snapshotid,3);
    $query="SELECT * from `switchdeadlog` WHERE `id`='".$snapshotid."'";
    $deaddata=  simple_query($query);
    $deathTime=  zb_SwitchesGetAllDeathTime();
    
    if (!empty($deaddata)) {
        $deadarr=  unserialize($deaddata['swdead']);

        $cells=  wf_TableCell(__('Switch dead since'));
        $cells.= wf_TableCell(__('IP'));
        $cells.= wf_TableCell(__('Location'));
        $rows=wf_TableRow($cells, 'row1');
        
        
        if (!empty($deadarr)) {
            foreach ($deadarr as $ip=>$location) {
                $cells=  wf_TableCell(@$deathTime[$ip]);
                $cells.=  wf_TableCell($ip);
                $cells.= wf_TableCell($location);
                $rows.=wf_TableRow($cells, 'row3');
            }
        }
        
        $result=  wf_TableBody($rows, '100%', '0', 'sortable');
        show_window(__('Dead switches').' '.$deaddata['date'],$result);
        show_window('',  wf_Link("?module=switches&timemachine=true", 'Back', false, 'ubButton'));
    }
    
}   
   
?>
