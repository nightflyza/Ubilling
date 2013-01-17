<?php

///////////////////////////////////////
//                                   //
//    CONFIG SECTION                 //
//                                   //
///////////////////////////////////////          

// DN switcher files path
$dn_path="/etc/stargazer/dn/";

// shaping method
//available options is 'dummynet' or 'tc'
$shape_method='dummynet';

//shaping interface
$shape_interface="em0";

//speed size
$speed_size='Kbit/s';

//mysql settings
$db_host='localhost';
$db_database='stg';
$db_login='mylogin';
$db_password='newpassword';



////////////// Main code section ///////////

function rcms_scandir($directory, $exp = '', $type = 'all', $do_not_filter = false) {
	$dir = $ndir = array();
	if(!empty($exp)){
		$exp = '/^' . str_replace('*', '(.*)', str_replace('.', '\\.', $exp)) . '$/';
	}
	if(!empty($type) && $type !== 'all'){
		$func = 'is_' . $type;
	}
	if(is_dir($directory)){
		$fh = opendir($directory);
		while (false !== ($filename = readdir($fh))) {
			if(substr($filename, 0, 1) != '.' || $do_not_filter) {
				if((empty($type) || $type == 'all' || $func($directory . '/' . $filename)) && (empty($exp) || preg_match($exp, $filename))){
					$dir[] = $filename;
				}
			}
		}
		closedir($fh);
		natsort($dir);
	}
	return $dir;
}

//parse all online users speed data
$online_users=rcms_scandir($dn_path);
$connect_data=array();
if (!empty ($online_users)) {
    foreach ($online_users as $ia=>$eachdata) {
        $connect_data[$eachdata]=file_get_contents($dn_path.$eachdata);
    }

}

function simple_queryall($query) {
    global $db_host, $db_database, $db_login, $db_password;
    $result=array();
    // init mysql link
    $dblink=mysql_connect($db_host, $db_login, $db_password);
   //selecting stargazer database
    mysql_select_db($db_database, $dblink);
    //executing query
    $queried = mysql_query($query);
    //getting result as array
    while($row = mysql_fetch_assoc($queried)) {
    $result[]= $row;
    }
    //closing link
    mysql_close($dblink);
    //return result of query as array
    return($result);
}


function dshape_GetAllUserTariffs() {
    $query="SELECT `login`,`Tariff` from `users`";
    $alltariffs=simple_queryall($query);
    $result=array();
    if (!empty ($alltariffs)) {
        foreach ($alltariffs as $io=>$eachtariff) {
            $result[$eachtariff['login']]=$eachtariff['Tariff'];
        }
    }
    return ($result);
}

function dshape_GetTimeRules() {
    $now=date('H:i:s');
    $query="SELECT `tariff`,`speed` from `dshape_time` WHERE  '".$now."'  > `threshold1` AND '".$now."' < `threshold2`";
    $result=array();
    $allrules=simple_queryall($query);
    if (!empty ($allrules)) {
        foreach ($allrules as $io=>$eachrule) {
            $result[$eachrule['tariff']]=$eachrule['speed'];
        }
    }
    return ($result);
}

// switches speed directly
function dshape_SwitchSpeed($speed,$mark,$interface,$method,$speed_size='Kbit/s') {
     if ($method=='dummynet') {
         $shape_command='/sbin/ipfw -q pipe '.trim($mark).' config bw '.$speed.''.$speed_size.' queue 32Kbytes'."\n";
     }    
       
     if ($method=='tc') {
         $shape_command='/sbin/tc class change dev '.$interface.' parent 1:1 classid 1:'.$mark.' htb rate '.$speed.' '.$speed_size.' burst '.$speed.' '.$speed_size.' prio 2 2'."\n";
     }
     //print($shape_command);
     shell_exec($shape_command);
}


$AllUserTariffs=dshape_GetAllUserTariffs();
$AllTimeRules=dshape_GetTimeRules();


$debugdata='#### Shape start'.date("d-M-Y H:i:s")."####\n";

if (!empty ($online_users)) {
    if (!empty ($AllTimeRules)) {
    foreach ($online_users as $eachuser) {
            $normal_data=explode(':',$connect_data[$eachuser]);
             $normal_speed=$normal_data[0];
             $normal_mark=$normal_data[1];
             $new_speed=$normal_data[0];
             $user_tariff=$AllUserTariffs[$eachuser];
             $debugdata.='user login:'.$eachuser."\n";
             $debugdata.='normal mark:'.trim($normal_mark)."\n";
             $debugdata.='user tariff:'.$user_tariff."\n";
             
          // check is now time to change speed?
           if (isset($AllTimeRules[$user_tariff])) {
            $new_speed=$AllTimeRules[$user_tariff];
          }
           $debugdata.='normal speed:'.$normal_speed."\n";
           $debugdata.='new speed:'.$new_speed."\n";
           $debugdata.='==============='."\n";
           dshape_SwitchSpeed($new_speed,$normal_mark,$shape_interface,$shape_method,$speed_size);
           
        }
    }
}
$debugdata.='####Shape end '.date("d-M-Y H:i:s")."####\n";

//debug output 
print($debugdata);


?>

