<?php

if($system->checkForRight('STREETEPORT')) {

    
	
function zb_RSUserCountByStreet($streetid) {
$streetid=vf($streetid,3);    
$all_builds=simple_queryall('SELECT * FROM `build` where `streetid`="'.$streetid.'" ORDER BY `buildnum`');
$result=array();
$out=0;
if (!empty($all_builds)) {
foreach ($all_builds as $eachbuild=>$bl) {
    $result[]=$bl['id'];
 }
 foreach ($result as $eachhata) {
 	$ss=simple_queryall('SELECT COUNT(`id`) FROM `apt` where `buildid`="'.$eachhata.'"');
 	foreach ($ss as $ssd=>$eachapt) {
 	$out=$out+$eachapt['COUNT(`id`)'];
 	}
 }
}
if (empty($out)) { $out=0; }
return ($out);
}


function zb_RSGetBuildCount($streetid) {
    $streetid=vf($streetid,3);
    $query="SELECT COUNT(`id`) from `build` WHERE `streetid`='".$streetid."'";
    $result=simple_query($query);
    $result=$result['COUNT(`id`)'];
    return ($result);
}

function zb_RSGetUserCountByStreet($streetid) {
    $streetid=vf($streetid,3);
    $query="SELECT COUNT(`id`) from `apt` WHERE `streetid`='".$streetid."'";
    $result=simple_query($query);
    $result=$result['COUNT(`id`)'];
    return ($result);
}

	$streets_q='SELECT * from `street`';
	$streets=simple_queryall($streets_q);
        
        $headerscells=wf_TableCell(__('ID'));
        $headerscells.=wf_TableCell(__('Street'));
        $headerscells.=wf_TableCell(__('Builds'));
        $headerscells.=wf_TableCell(__('Registered'));
        $headerscells.=wf_TableCell(__('Visual'));
        $headerscells.=wf_TableCell(__('Level'));
        $tablerows=wf_TableRow($headerscells, 'row1');
        
   if (!empty($streets)) {
       $totalusercount=zb_UserGetAllStargazerData();
       $totalusercount=sizeof($totalusercount);
   	foreach ($streets as $val => $eachstreet) {
	  $build_count=zb_RSGetBuildCount($eachstreet['id']);
	  $usercount=zb_RSUserCountByStreet($eachstreet['id']);
	if (($usercount!=0) AND ($build_count!=0)) {
	  $kpd=$usercount/$build_count;	
	}
	else {
	  $kpd=0;
	}
	$col_kpd=round($kpd,2);
	$colour='black';
	if ($col_kpd<2) {
		$colour='red';
	}
	if ($col_kpd>=3) {
		$colour='green';
	}
        
        $tablecells=wf_TableCell($eachstreet['id']);
        $tablecells.=wf_TableCell($eachstreet['streetname']);
        $tablecells.=wf_TableCell($build_count);
        $tablecells.=wf_TableCell($usercount);
        $tablecells.=wf_TableCell(web_bar($usercount, $totalusercount),'50%','','sorttable_customkey="'.$usercount.'"');
        $tablecells.=wf_TableCell('<font color="'.$colour.'">'.$col_kpd.'</font>');
        $tablerows.=wf_TableRow($tablecells, 'row3');
        
	
	 }
        $result=wf_TableBody($tablerows, '100%', '0', 'sortable');
	}
	
    show_window(__('Streets report'),$result);
} else {
	show_error(__('Access denied'));
}

?>
