<?php

function log_append($logfile,$data){
    if (!file_put_contents($logfile, $data, FILE_APPEND)) {
        die('Permission denied');
    }
}

function printlog($data) {
    global $ps_file101;
    log_append($ps_file101, $data."\n");
}

function printlog_sql($data) {
    global $ps_file102;
    log_append($ps_file102, $data."\n");
}

function deb($data) {
    print($data);
}

function debarr($data) {
    print_r('<pre>'.print_r($data,true).'</pre>');
}

/**
 * Debug on/off
 *
 */
define("DEBUG",0);
$query_counter=0;



function mysql_disconnect($conn)   {
	 mysql_close($conn);
}


// function that executing query and returns array
function sqa($conn,$query) {
$result='';
$queried = mysql_query($query,$conn) or die('wrong data input');
while($row = mysql_fetch_assoc($queried)) {
 $result[]=  $row;
}
return($result);
}

// function that executing query and returns array of first result
function sq($conn,$query) {
$queried = mysql_query($query,$conn) or die('wrong data input');
$result= mysql_fetch_assoc($queried);
return($result);
}



// function that just executing query 
function nq($conn,$query) {
$queried = mysql_query($query,$conn) or die('wrong data input');
}

/**
 * Returns cutted down entry data
 *
 * @param string $data
 * @param int $mode
 * @return string
 */
function vf($data,$mode=0)
{
	switch ($mode)
	{
		case 1:
			return preg_replace("#[^a-z0-9A-Z]#Uis",'',$data); // digits, letters
			break;
		case 2:
			return preg_replace("#[^a-zA-Z]#Uis",'',$data); // letters
			break;
		case 3:
			return preg_replace("#[^0-9]#Uis",'',$data); // digits
			break;
		case 4:
			return preg_replace("#[^a-z0-9A-Z\-_\.]#Uis",'',$data); // digits, letters, "-", "_", "."
			break;
		case 5:
			return preg_replace("#[^ [:punct:]".('a-zA-Z')."0-9]#Uis",'',$data); // current lang alphabet + digits + punctuation
			break;
		default:
			return preg_replace("#[~@\+\?\%\/\;=\*\>\<\"\'\-]#Uis",'',$data); // black list anyway
			break;
	}
}


function zb_TariffGetAllSpeeds($conn) {
    $allspeeds=sqa($conn, "SELECT * from `speeds`");
    $result=array();
    if (!empty ($allspeeds)) {
        foreach ($allspeeds as $io=>$eachspeed) {
            $result[$eachspeed['tariff']]['down']=$eachspeed['speeddown'];
            $result[$eachspeed['tariff']]['up']=$eachspeed['speedup'];
        }
    }
    return ($result);
}

function zb_UserGetAllContracts($conn) {
    $result=array();
    $query="SELECT * from `contracts`"; 
    $allcontracts=sqa($conn,$query);
    if (!empty ($allcontracts)) {
        foreach ($allcontracts as $io=>$eachcontract) {
            $result[$eachcontract['login']]=$eachcontract['contract'];
        }
    }
    return ($result);
}


function zb_UserGetAllUserregDates($conn) {
    $result=array();
    $query="SELECT * from `userreg`"; 
    $allregs=sqa($conn,$query);
    if (!empty ($allregs)) {
        foreach ($allregs as $io=>$eachcontract) {
            $result[$eachcontract['login']]=$eachcontract['date'];
        }
    }
    return ($result);
}

function zb_UserGetAllRealnames($conn) {
    $query_fio="SELECT * from `realname`";
    $allfioz=sqa($conn,$query_fio);
    $fioz=array();
    if (!empty ($allfioz)) {
        foreach ($allfioz as $ia=>$eachfio) {
            $fioz[$eachfio['login']]=$eachfio['realname'];
          }
    }
    return($fioz);
}

function zb_UserGetAllNotes($conn) {
    $result=array();
    $query="SELECT * from `notes`"; 
    $all=sqa($conn,$query);
    if (!empty ($all)) {
        foreach ($all as $io=>$each) {
            $result[$each['login']]=$each['note'];
        }
    }
    return ($result);
}

function zb_UserGetAllPhones($conn) {
    $result=array();
    $query="SELECT * from `phones`"; 
    $all=sqa($conn,$query);
    if (!empty ($all)) {
        foreach ($all as $io=>$each) {
            $result[$each['login']]['phone']=$each['phone'];
            $result[$each['login']]['mobile']=$each['mobile'];
        }
    }
    return ($result);
}

function us_NethostsGetAll($conn) {
    $result=array();
    $query="SELECT * from `nethosts`"; 
    $all=sqa($conn,$query);
    if (!empty ($all)) {
        foreach ($all as $io=>$each) {
            $result[$each['ip']]=$each['mac'];
        }
    }
    return ($result);
}



    // returns all addres array in view like login=>address
function us_AddressGetFulladdresslist($conn) {

$result=array();
$apts=array();
$builds=array();
$adrz_q="SELECT * from `address`";
$apt_q="SELECT * from `apt`";
$build_q="SELECT * from build";
$streets_q="SELECT * from `street`";
$alladdrz=sqa($conn,$adrz_q);
$allapt=sqa($conn,$apt_q);
$allbuilds=sqa($conn,$build_q);
$allstreets=sqa($conn,$streets_q);
if (!empty ($alladdrz)) {

    
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

   
        $result[$eachaddress['login']]['string']=$streetname.' '.$building.'/'.$apartment;
        $result[$eachaddress['login']]['streetname']=vf($streetname);
        $result[$eachaddress['login']]['streetid']=$builds[$apts[$eachaddress['aptid']]['buildid']]['streetid'];
        $result[$eachaddress['login']]['buildnum']=vf($building);
        $result[$eachaddress['login']]['buildid']=$apts[$eachaddress['aptid']]['buildid'];
        $result[$eachaddress['login']]['aptid']=$eachaddress['aptid'];
        $result[$eachaddress['login']]['apt']=vf($apartment);
    
    }
}

return($result);
}

//returns all streets list
function us_AddressGetStreetsAll ($conn) {
     $result=array();
    $query="SELECT * from `street`"; 
    $all=sqa($conn,$query);
    if (!empty ($all)) {
        foreach ($all as $io=>$each) {
            $result[$each['id']]=$each['streetname'];
        }
    }
    return ($result);
}



//returns all streets list
function us_AddressGetBuildAll ($conn) {
     $result=array();
    $query="SELECT * from `build`"; 
    $all=sqa($conn,$query);
    if (!empty ($all)) {
        foreach ($all as $io=>$each) {
            $result[$each['id']]['id']=$each['id'];
            $result[$each['id']]['buildnum']=$each['buildnum'];
            $result[$each['id']]['streetid']=$each['streetid'];
        }
    }
    return ($result);
}

function crc16($string) {
  $crc = 0xFFFF;
  for ($x = 0; $x < strlen ($string); $x++) {
    $crc = $crc ^ ord($string[$x]);
    for ($y = 0; $y < 8; $y++) {
      if (($crc & 0x0001) == 0x0001) {
        $crc = (($crc >> 1) ^ 0xA001);
      } else { $crc = $crc >> 1; }
    }
  }
  return $crc;
} 

function ip2int($src){
  $t = explode('.', $src);
  return count($t) != 4 ? 0 : 256 * (256 * ((float)$t[0] * 256 + (float)$t[1]) + (float)$t[2]) + (float)$t[3];
}

function int2ip($src){
  $s1 = (int)($src / 256);
  $i1 = $src - 256 * $s1;
  $src = (int)($s1 / 256);
  $i2 = $s1 - 256 * $src;
  $s1 = (int)($src / 256);
  return sprintf('%d.%d.%d.%d', $s1, $src - 256 * $s1, $i2, $i1);
}

function prepare_mac($mac) {
    $fmac=str_replace(':', '', $mac);
    $fmac=strtoupper($fmac);
    return ($fmac);
}

function enccorr($string) {
    $result=iconv('utf-8','windows-1251',$string);
    $result=mysql_real_escape_string($result);
    return ($result);
    
}



?>
