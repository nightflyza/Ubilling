<?php


$etalon_speed=1024;
$etalon_day_band=3800;
$home_band_p=35;

$genocide_q="SELECT `tariff`,`speed` from `genocide`";
$rawdata=simple_queryall($genocide_q);
$control_tariffs=array();
if (!empty ($rawdata)) {
    foreach ($rawdata as $io=>$eachrow) {
        $control_tariffs[$eachrow['tariff']]=$eachrow['speed'];
    }
}

$cur_day=date("d");

if (isset($_POST['change_settings'])) {
$home_band_p=vf($_POST['home_band_p'],3);
}


//create or delete limits
if (isset ($_GET['delete'])) {
    gen_delete_limit($_GET['delete']);
    rcms_redirect("?module=genocide");
}

if (isset($_POST['tariffsel'])) {
    if (isset($_POST['newgenocide'])) {
        gen_create_limit($_POST['tariffsel'], $_POST['newgenocide']);
        rcms_redirect("?module=genocide");
    }
    
}


//normal day band
$etalon_day_band=(($etalon_speed/100)*$home_band_p)/8*3600*24/1024;


function gen_check_users() {
    global $etalon_day_band,$control_tariffs,$etalon_speed,$cur_day,$home_band_p;
    $tariff_names=array_keys($control_tariffs);
    $genocide_qarr=array();
    $band_arr=array();
    
    $geninputs=wf_TextInput('home_band_p', 'Normal bandwidth load', $home_band_p, false, '2').' %';
    $geninputs.=wf_HiddenInput('change_settings', 'true').' ';
    $geninputs.=wf_Submit('Change');
    $genform=wf_Form('', 'POST', $geninputs, 'glamour');
    $result=$genform;
    
    $tablecells=wf_TableCell(__('Tariff'));
    $tablecells.=wf_TableCell(__('Normal day band'));
    $tablecells.=wf_TableCell(__('Current date normal band'));
    $tablecells.=wf_TableCell(__('Speed'));
    $tablecells.=wf_TableCell(__('Actions'));
    $tablerows=wf_TableRow($tablecells, 'row1');
    
    $i=0;
    
    foreach ($control_tariffs as $eachtariff) {
     @$cspeed_k=$etalon_speed/$eachtariff;
     @$cband_k=$etalon_day_band/$cspeed_k;
     $dband_l=$cband_k*$cur_day;
     $band_arr[$i][$tariff_names[$i]]=($dband_l*1024)*1024;
     
    $tablecells=wf_TableCell($tariff_names[$i]);
    $tablecells.=wf_TableCell(stg_convert_size(($cband_k*1024)*1024));
    $tablecells.=wf_TableCell(stg_convert_size(($band_arr[$i][$tariff_names[$i]])));
    $tablecells.=wf_TableCell($eachtariff);
    
    $gactions=wf_JSAlert('?module=genocide&delete='.$tariff_names[$i], web_delete_icon(), 'Are you serious');
    $tablecells.=wf_TableCell($gactions);
    $tablerows.=wf_TableRow($tablecells, 'row3');
    $i++;
    }
    
    //controlled tariffs
    $result.=wf_TableBody($tablerows, '100%', '0', '');
    
   $i=0;
   
    foreach ($band_arr as $eachtariff=>$eachband) {
        $query="SELECT * from `users` WHERE `D0`+`U0`>'".$eachband[$tariff_names[$i]]."' and `Tariff`='".$tariff_names[$i]."';";
        $genocide_qarr[]=$query;
        $i++;
    }
    
             $tablecells=wf_TableCell(__('Login'));
             $tablecells.=wf_TableCell(__('Full address'));
             $tablecells.=wf_TableCell(__('Real Name'));
             $tablecells.=wf_TableCell(__('Tariff'));
             $tablecells.=wf_TableCell(__('IP'));
             $tablecells.=wf_TableCell(__('Downloaded'));
             $tablecells.=wf_TableCell(__('Uploaded'));
             $tablecells.=wf_TableCell(__('Total'));
             $tablerows=wf_TableRow($tablecells, 'row1');
    
    foreach ($genocide_qarr as $each_q) {
     $genocide_users=simple_queryall($each_q);
     if (!empty ($genocide_users)) {
         $alluseraddress=zb_AddressGetFulladdresslist();
         $allusernames=zb_UserGetAllRealnames();
         
             
         
         foreach ($genocide_users as $io=>$eachuser) {
             $profilelink=wf_Link('?module=userprofile&username='.$eachuser['login'], web_profile_icon().' '.$eachuser['login']);
             $tablecells=wf_TableCell($profilelink);
             $tablecells.=wf_TableCell(@$alluseraddress[$eachuser['login']]);
             $tablecells.=wf_TableCell(@$allusernames[$eachuser['login']]);
             $tablecells.=wf_TableCell($eachuser['Tariff']);
             $tablecells.=wf_TableCell($eachuser['IP'],'','','sorttable_customkey="'.ip2long($eachuser['IP']).'"');
             $tablecells.=wf_TableCell(stg_convert_size($eachuser['D0']),'','','sorttable_customkey="'.$eachuser['D0'].'"');
             $tablecells.=wf_TableCell(stg_convert_size($eachuser['U0']),'','','sorttable_customkey="'.$eachuser['U0'].'"');
             $tablecells.=wf_TableCell(stg_convert_size(($eachuser['D0']+$eachuser['U0'])),'','','sorttable_customkey="'.($eachuser['D0']+$eachuser['U0']).'"');
             $tablerows.=wf_TableRow($tablecells, 'row3');
            }
            
            
         }
        
     }
 $result.=wf_TableBody($tablerows, '100%', '0', 'sortable');
	
    show_window(__('Genocide'),$result);
}


function gen_create_limit($tariff,$speed) {
    $tariff=mysql_real_escape_string($tariff);
    $speed=vf($speed,3);
    $query="INSERT INTO `genocide` (
            `id` ,
            `tariff` ,
            `speed`
            )
            VALUES (
            NULL , '".$tariff."', '".$speed."'
            );";
    nr_query($query);
    log_register("GENOCIDE ADD ".$tariff);
}

function gen_delete_limit($tariff) {
    $query="DELETE from `genocide` WHERE `tariff`='".$tariff."'";
    nr_query($query);
    log_register("GENOCIDE DELETE ".$tariff);
}

function web_gen_addform() {
    $addinputs=web_tariffselector();
    $addinputs.=wf_TextInput('newgenocide', 'Speed', '', false, '10');
    $addinputs.=wf_Submit('Create');
    $addform=wf_Form('', 'POST', $addinputs, 'glamour');
    show_window('', $addform);
}


gen_check_users();
web_gen_addform();

?>
