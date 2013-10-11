<?php

$altcfg=  rcms_parse_ini_file(CONFIG_PATH."alter.ini");
if ($altcfg['ASTERISK_ENABLED']) {


/*
 * Get numbers aliases from database, or set default empty array
 * 
 * @return array
 */
 function zb_AsteriskGetNumAliases() {
    $result=array();
    $rawAliases=  zb_StorageGet('ASTERISK_NUMALIAS');
    if (empty($rawAliases)) {
        $newAliasses=  serialize($result);
        $newAliasses= base64_encode($newAliasses);
        zb_StorageSet('ASTERISK_NUMALIAS', $newAliasses);
    } else {
        $readAlias=  base64_decode($rawAliases);
        $readAlias= unserialize($readAlias);
        $result=$readAlias;
    }
    return ($result);
}

/*
 * Gets Asterisk config from DB, or sets default values
 * 
 * @return array
 */
function zb_AsteriskGetConf() {
    $result=array();
    $emptyArray=array();
    //getting url
    $host=  zb_StorageGet('ASTERISK_HOST');
    if (empty($host)) {
        $host='localhost';
        zb_StorageSet('ASTERISK_HOST', $host);
    }
    //getting login
    $login=  zb_StorageGet('ASTERISK_LOGIN');
    if (empty($login)) {
        $login='asterisk';
        zb_StorageSet('ASTERISK_LOGIN', $login);
    }
    
    //getting DB name
    $db=  zb_StorageGet('ASTERISK_DB');
    if (empty($db)) {
        $db='asteriskdb';
        zb_StorageSet('ASTERISK_DB', $db);
    }
    //getting CDR table name
    $table=  zb_StorageGet('ASTERISK_TABLE');
    if (empty($table)) {
        $table='cdr';
        zb_StorageSet('ASTERISK_TABLE', $table);
    }
    
    //getting password
    $password=  zb_StorageGet('ASTERISK_PASSWORD');
    if (empty($password)) {
        $password='password';
        zb_StorageSet('ASTERISK_PASSWORD', $password);
    }
    //getting caching time
    $cache=  zb_StorageGet('ASTERISK_CACHETIME');
    if (empty($cache)) {
        $cache='1';
        zb_StorageSet('ASTERISK_CACHETIME', $cache);
    }
    
    $result['host']=$host;
    $result['db']=$db;
    $result['table']=$table;
    $result['login']=$login;
    $result['password']=$password;
    $result['cachetime']=$cache;
    return ($result);
}

/*
 * Converts per second time values to human-readable format
 * 
 * @param $seconds - time interval in seconds
 * 
 * @return string
 */
function zb_AsteriskFormatTime($seconds) {
$init=$seconds;
$hours = floor($seconds / 3600);
$minutes = floor(($seconds / 60) % 60);
$seconds = $seconds % 60;

if ($init<3600) { 
    //less than 1 hour
    if ($init<60) {
        //less than minute
        $result=$seconds.' '.__('sec.');
    } else {
        //more than one minute
        $result=$minutes.' '.__('minutes').' '.$seconds.' '.__('seconds');
    }
    
} else { 
    //more than hour
    $result=$hours.' '.__('hour').' '.$minutes.' '.__('minutes').' '.$seconds.' '.__('seconds');
}
return ($result);
}


/*
 * Returns human readable alias from phone book by phone number
 * 
 * @param $number - phone number
 * 
 * @return string
 */
function zb_AsteriskGetNumAlias($number) {
    global $numAliases;
    
    if (!empty($numAliases)) {
        if (isset($numAliases[$number])) {
            return($number.' - '.$numAliases[$number]);
        } else {
            return ($number);
        }
    } else {
        return ($number);
    }
}

/*
 * Checks first digit in some number by some prefix
 * 
 * @param $prefix - search prefix
 * @param $callerid - phone number
 * 
 * @return bool
 */
function zb_AsteriskCheckPrefix($prefix,$callerid) {
    if (substr($callerid, 0, 1)==$prefix) {
        return (true);
    } else {
        return (false);
    }
}

/*
 * Parse Asterisk RAW CDR data
 * 
 * @param data - raw CDR
 * 
 * @return void
 */

//need review with real CDR data
function zb_AsteriskParseCDR($data) {
   global $altcfg;
   $normalData=$data;
   
   
   if (!empty($normalData)) {
       $totalTime=0;
       $callsCounter=0;
       $cells=  wf_TableCell('#');
       $cells.=  wf_TableCell(__('Time'));
       $cells.=  wf_TableCell(__('From'));
       $cells.=  wf_TableCell(__('To'));
       $cells.=  wf_TableCell(__('Type'));
       $cells.=  wf_TableCell(__('Status'));
       $cells.=  wf_TableCell(__('Talk time'));
       
       $rows=  wf_TableRow($cells, 'row1');
       
       foreach ($normalData as $io=>$each) {
           $callsCounter++;
           $debugData= wf_tag('pre').print_r($each, true).  wf_tag('pre', true);
           
           $startTime=  explode(' ', $each['calldate']);
           @$startTime=$startTime[1];
           $tmpTime= strtotime($each['calldate']);
           $endTime=$tmpTime+$each['duration'];
           $endTime=date("H:i:s",$endTime);
           $answerTime=$tmpTime+($each['duration']-$each['billsec']);
           $answerTime=date("H:i:s",$answerTime);
           $tmpStats=__('Taken up the phone').': '.$answerTime."\n";
           $tmpStats.=__('End of call').': '.$endTime;
           $sessionTimeStats= wf_tag('abbr', false, '', 'title="'.$tmpStats.'"');
           $sessionTimeStats.=$startTime;
           $sessionTimeStats.=wf_tag('abbr',true);
           $callDirection='';
           
       
           
           $cells=   wf_TableCell(wf_modal($callsCounter, $callsCounter, $debugData, '', '500', '600'),'','','sorttable_customkey="'.$callsCounter.'"');
           $cells.=  wf_TableCell($sessionTimeStats,'','','sorttable_customkey="'.  $tmpTime.'"');
           $cells.=  wf_TableCell(zb_AsteriskGetNumAlias($each['src']));
           $cells.=  wf_TableCell(zb_AsteriskGetNumAlias($each['dst']));
           $CallType=__('Dial');
           if (ispos($each['lastapp'], 'internal-caller-transfer')) {
               $CallType=__('Call transfer');
           } 
           
          
         
           
           $cells.=  wf_TableCell($CallType);
           
           $callStatus=$each['disposition'];
           $statusIcon='';
           if (ispos($each['disposition'], 'ANSWERED')) {
               $callStatus=__('Answered');
               $statusIcon=  wf_img('skins/calls/phone_green.png');
           }
            if (ispos($each['disposition'], 'NO ANSWER')) {
               $callStatus=__('No answer');
               $statusIcon=  wf_img('skins/calls/phone_red.png');
           }
           
           if (ispos($each['disposition'], 'BUSY')) {
               $callStatus=__('Busy');
               $statusIcon=  wf_img('skins/calls/phone_yellow.png');
           }
           
           if (ispos($each['disposition'], 'FAILED')) {
               $callStatus=__('Failed');
               $statusIcon=  wf_img('skins/calls/phone_fail.png');
           }
           
           $cells.=  wf_TableCell($statusIcon.' '.$callStatus);
           $speekTime=$each['billsec'];
           $totalTime=$totalTime+$each['billsec'];
           $speekTime=  zb_AsteriskFormatTime($speekTime);
           
          
           
           $cells.= wf_TableCell($speekTime,'','','sorttable_customkey="'.$each['billsec'].'"');

           
           $rows.= wf_TableRow($cells, 'row3');
       }
       
       $result=  wf_TableBody($rows, '100%', '0', 'sortable');
       $result.=__('Time spent on calls').': '.  zb_AsteriskFormatTime($totalTime).  wf_tag('br');
       $result.=__('Total calls').': '.$callsCounter;
       show_window('',$result);
   }
}

/*
 * Another database query execution
 * 
 * @param $query - query to execute
 * 
 * @return array
 */

function zb_AsteriskQuery($query) {
    global $asteriskHost,$asteriskDb,$asteriskTable,$asteriskLogin,$asteriskPassword,$asteriskCacheTime;
    $asteriskDB=new DbConnect($asteriskHost, $asteriskLogin, $asteriskPassword, $asteriskDb, $error_reporting = true, $persistent = false);
    $asteriskDB->open() or die($asteriskDB->error());
    $result = array();
    $asteriskDB->query('SET NAMES utf8;');
    $asteriskDB->query($query);
    while ($row = $asteriskDB->fetchassoc()) {
        $result[] = $row;
    }
    $asteriskDB->close();
    return ($result);
}

/*
 * Gets Asterisk CDR data from database and manage cache
 * 
 * @pram $from - start date
 * @param $to  - end date
 * 
 * @return void
 */

function zb_AsteriskGetCDR($from,$to) {
global $asteriskHost,$asteriskDb,$asteriskTable,$asteriskLogin,$asteriskPassword,$asteriskCacheTime;
$from=  mysql_real_escape_string($from);
$to=  mysql_real_escape_string($to);
$asteriskTable=  mysql_real_escape_string($asteriskTable);
$cachePath='exports/';



//caching
$cacheUpdate=true;
$cacheName=  $from.$to;
$cacheName=  md5($cacheName);
$cacheName=$cachePath.$cacheName.'.asterisk';
$cachetime=time()-($asteriskCacheTime*60);

if (file_exists($cacheName)) {
     if ((filemtime($cacheName)>$cachetime)) {
         $rawResult=  file_get_contents($cacheName);
         $rawResult=  unserialize($rawResult);
         $cacheUpdate=false;
     } else {
         $cacheUpdate=true;
     }
} else {
    $cacheUpdate=true;
}


if ($cacheUpdate) {
//connect to Asterisk database and fetch some data
$query="select * from `".$asteriskTable."` where `calldate` BETWEEN '".$from." 00:00:00' AND '".$to." 23:59:59'";
$rawResult=  zb_AsteriskQuery($query);
$cacheContent=  serialize($rawResult);
file_put_contents($cacheName, $cacheContent);
}

if (!empty($rawResult)) {
    //here is data parsing
    zb_AsteriskParseCDR($rawResult);
    
} else {
    show_window(__('Error'), __('Empty reply received'));
}

}

/*
 * Returns CDR date selection form
 * 
 * @return string
 */
function web_AsteriskDateForm() {
    $inputs= wf_Link("?module=asterisk&config=true", wf_img('skins/settings.png',__('Settings'))).' ';
    $inputs.=  wf_DatePickerPreset('datefrom', curdate()).' '.__('From');
    $inputs.= wf_DatePickerPreset('dateto', curdate()).' '.__('To');
    $inputs.= wf_Submit(__('Show'));
    $result=  wf_Form("", "POST", $inputs, 'glamour');
    return ($result);
}

/*
 * Returns Asterisk module configuration form
 * 
 * @return string
 */
function web_AsteriskConfigForm() {
    global $asteriskHost,$asteriskDb,$asteriskTable,$asteriskLogin,$asteriskPassword,$asteriskCacheTime;
    
    $result= wf_Link('?module=asterisk', __('Back'), true, 'ubButton').  wf_delimiter();
    $inputs=  wf_TextInput('newhost', __('Asterisk host'), $asteriskHost, true);
    $inputs.= wf_TextInput('newdb', __('Database name'), $asteriskDb, true);
    $inputs.= wf_TextInput('newtable', __('CDR table name'), $asteriskTable, true);
    $inputs.= wf_TextInput('newlogin', __('Database login'), $asteriskLogin, true);
    $inputs.= wf_TextInput('newpassword', __('Database password'), $asteriskPassword, true);
    $inputs.= wf_TextInput('newcachetime', __('Cache time'), $asteriskCacheTime, true);
    $inputs.= wf_Submit(__('Save'));
    $result.=  wf_Form("", "POST", $inputs, 'glamour');
    return ($result);
    
}

/*
 * Returns number aliases aka phonebook form
 * 
 * @return string 
 */
function web_AsteriskAliasesForm() {
    global $numAliases;
    $createinputs=wf_TextInput('newaliasnum', __('Phone'), '', true);
    $createinputs.=wf_TextInput('newaliasname', __('Alias'), '', true);
    $createinputs.=wf_Submit(__('Create'));
    $createform=  wf_Form('', 'POST', $createinputs, 'glamour');
    $result=$createform;
    
    
    if (!empty($numAliases)) {
        $delArr=array();
        foreach ($numAliases as $num=>$eachname) {
            $delArr[$num]=$num.' - '.$eachname; 
        }
        $delinputs=  wf_Selector('deletealias', $delArr, __('Delete alias'), '', false);
        $delinputs.= wf_Submit(__('Delete'));
        $delform= wf_Form('', 'POST', $delinputs, 'glamour');
        $result.= $delform;
    }
    
    return ($result);
}





if (cfr('ASTERISK')) {

//loading asterisk config
$asteriskConf= zb_AsteriskGetConf();
$numAliases=  zb_AsteriskGetNumAliases();
$asteriskHost = $asteriskConf['host'];
$asteriskDb= $asteriskConf['db'];
$asteriskTable= $asteriskConf['table'];
$asteriskLogin=$asteriskConf['login'];
$asteriskPassword=$asteriskConf['password'];
$asteriskCacheTime=$asteriskConf['cachetime']; 




//showing configuration form
if (wf_CheckGet(array('config'))) {
    //changing settings
    if (wf_CheckPost(array('newhost','newdb','newtable','newlogin','newpassword'))) {
        zb_StorageSet('ASTERISK_HOST', $_POST['newhost']);
        zb_StorageSet('ASTERISK_DB', $_POST['newdb']);
        zb_StorageSet('ASTERISK_TABLE', $_POST['newtable']);
        zb_StorageSet('ASTERISK_LOGIN', $_POST['newlogin']);
        zb_StorageSet('ASTERISK_PASSWORD', $_POST['newpassword']);
        zb_StorageSet('ASTERISK_CACHETIME', $_POST['newcachetime']);
        log_register("ASTERISK settings changed");
        rcms_redirect("?module=asterisk&config=true");
        
    }
    
    //aliases creation
    if (wf_CheckPost(array('newaliasnum','newaliasname'))) {
        $newStoreAliases=$numAliases;
        $newAliasNum=  mysql_real_escape_string($_POST['newaliasnum']);
        $newAliasName=  mysql_real_escape_string($_POST['newaliasname']);
        $newStoreAliases[$newAliasNum]=$newAliasName;
        $newStoreAliases=  serialize($newStoreAliases);
        $newStoreAliases=  base64_encode($newStoreAliases);
        zb_StorageSet('ASTERISK_NUMALIAS', $newStoreAliases);
        log_register("ASTERISK ALIAS ADD `".$newAliasNum."` NAME `".$newAliasName."`");
        rcms_redirect("?module=asterisk&config=true");
    }
    
    //alias deletion
    if (wf_CheckPost(array('deletealias'))) {
        $newStoreAliases=$numAliases;
        $deleteAliasNum=mysql_real_escape_string($_POST['deletealias']);
        if (isset($newStoreAliases[$deleteAliasNum])) {
            unset($newStoreAliases[$deleteAliasNum]);
            $newStoreAliases=  serialize($newStoreAliases);
            $newStoreAliases=  base64_encode($newStoreAliases);
            zb_StorageSet('ASTERISK_NUMALIAS', $newStoreAliases);
            log_register("ASTERISK ALIAS DELETE `".$deleteAliasNum."`");
            rcms_redirect("?module=asterisk&config=true");
        }
        
    }
    
    show_window(__('Settings'), web_AsteriskConfigForm());
    show_window(__('Phone book'), web_AsteriskAliasesForm());
    
} else {
    //showing call history form
    show_window(__('Calls history'),  web_AsteriskDateForm());
    
    //and parse some calls history if this needed
    if (wf_CheckPost(array('datefrom','dateto'))) {
    zb_AsteriskGetCDR($_POST['datefrom'],$_POST['dateto']);    
    } 
}



} else {
    show_error(__('Error'),__('Permission denied'));
}



} else {
    show_window(__('Error'), __('Asterisk PBX integration now disabled'));
}



?>
