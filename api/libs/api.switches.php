<?php

////////////////////// switch models managment
function web_SwitchModelsShow() {
	$query='SELECT * from `switchmodels`';
	$allmodels=simple_queryall($query);
                
        $tablecells=wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('Model'));
        $tablecells.=wf_TableCell(__('Ports'));
        $tablecells.=wf_TableCell(__('Actions'));
        $tablerows=wf_TableRow($tablecells, 'row1');
        
        
	if (!empty($allmodels)) {
	foreach ($allmodels as $io=>$eachmodel) {
            
        $tablecells=wf_TableCell($eachmodel['id']);
        $tablecells.=wf_TableCell($eachmodel['modelname']);
        $tablecells.=wf_TableCell($eachmodel['ports']);
        $switchmodelcontrols=wf_JSAlert('?module=switchmodels&deletesm='.$eachmodel['id'], web_delete_icon(), 'Are you serious');
        $switchmodelcontrols.=wf_Link('?module=switchmodels&edit='.$eachmodel['id'], web_edit_icon());
        $tablecells.=wf_TableCell($switchmodelcontrols);
        $tablerows.=wf_TableRow($tablecells, 'row3');
 	   }
	}
        
    $result=wf_TableBody($tablerows, '100%', '0', 'sortable');


$addinputs=wf_TextInput('newsm', 'Model', '', true);
$addinputs.=wf_TextInput('newsmp', 'Ports', '', true,'5');
$addinputs.=web_add_icon().' '.wf_Submit('Create');
$addform=wf_Form('', 'POST', $addinputs, 'glamour');


$result.=$addform;

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

function ub_SwitchModelAdd($name,$ports) {
    $ports=vf($ports);
    $name=mysql_real_escape_string($name);
	$query='
	INSERT INTO `switchmodels` (
                `id` ,
                `modelname` ,
                `ports`
                )
                VALUES (
                NULL , "'.$name.'", "'.$ports.'");';
	nr_query($query);
	stg_putlogevent('SWITCHMODEL ADD '.$name);
}

function ub_SwitchModelDelete($modelid) {
        $modelid=vf($modelid);
        $query='DELETE FROM `switchmodels` WHERE `id` = "'.$modelid.'"';
	nr_query($query);
	stg_putlogevent('SWITCHMODEL DELETE  '.$modelid);
	}

function web_SwitchFormAdd() {
    $addinputs=wf_TextInput('newip', 'IP', '', true);
    $addinputs.=wf_TextInput('newlocation', 'Location', '', true);
    $addinputs.=wf_TextInput('newdesc', 'Description', '', true);
    $addinputs.=wf_TextInput('newsnmp', 'SNMP community', '', true);
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
    
        
function web_SwitchesShow() {
        $allswitches=zb_SwitchesGetAll();
        $modelnames=zb_SwitchModelsGetAllTag();
			
        $tablecells=wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('IP'));
        $tablecells.=wf_TableCell(__('Location'));
        $tablecells.=wf_TableCell(__('Active'));
        $tablecells.=wf_TableCell(__('Model'));
        $tablecells.=wf_TableCell(__('SNMP community'));
        $tablecells.=wf_TableCell(__('Description'));
        $tablecells.=wf_TableCell(__('Actions'));
        $tablerows=wf_TableRow($tablecells, 'row1');
        
	if (!empty($allswitches)) {
            foreach ($allswitches as $io=>$eachswitch) {
                //check switch alive state
                if (!ispos($eachswitch['desc'], 'NP')) {
                if (zb_PingICMP($eachswitch['ip'])) {
                $aliveled=web_green_led();
                $aliveflag='1';
                } else {
                $aliveled=web_red_led();
                $aliveflag='0';
                }
                } else {
                // if switch have NP flag
                $aliveled=web_green_led();
                $aliveflag='1';
                }
                
    
                $tablecells=wf_TableCell($eachswitch['id']);
                $tablecells.=wf_TableCell($eachswitch['ip'], '', '', 'sorttable_customkey="'.ip2int($eachswitch['ip']).'"');
                $tablecells.=wf_TableCell($eachswitch['location']);
                $tablecells.=wf_TableCell($aliveled, '', '', 'sorttable_customkey="'.$aliveflag.'"');
                $tablecells.=wf_TableCell(@$modelnames[$eachswitch['modelid']]);
                $tablecells.=wf_TableCell($eachswitch['snmp']);
                $tablecells.=wf_TableCell($eachswitch['desc']);
                $switchcontrols=wf_JSAlert('?module=switches&switchdelete='.$eachswitch['id'], web_delete_icon(), 'Are you serious');
                $switchcontrols.=wf_Link('?module=switches&edit='.$eachswitch['id'], web_edit_icon());
                $tablecells.=wf_TableCell($switchcontrols);
                $tablerows.=wf_TableRow($tablecells, 'row3');
                
            }
	 
	}
	$result=wf_TableBody($tablerows, '100%', '0', 'sortable');
	return ($result);
}


function ub_SwitchAdd($modelid,$ip,$desc,$location,$snmp) {
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
             `snmp`
            )
            VALUES (
                '', '".$modelid."', '".$ip."', '".$desc."', '".$location."', '".$snmp."'
                );
            ";
	nr_query($query);
	stg_putlogevent('SWITCH ADD '.$ip.' ON ADDRESS '.$location);
	show_window(__('Add switch'),__('Was added new switch').' '.$ip.' '.$location);
}



function ub_SwitchDelete($switchid) {
    $switchid=vf($switchid);
    $query="DELETE from `switches` WHERE `id`='".$switchid."'";
    nr_query($query);
    log_register('SWITCH DELETE '.$switchid);
}

?>
