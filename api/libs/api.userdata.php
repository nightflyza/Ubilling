<?php
// userdata workaround
 
/*
 * User Real name management
 * Create, Delete, Get, Change
 */

function zb_UserCreateRealName($login,$realname) {
$login=vf($login);
$realname=mysql_real_escape_string($realname);
$query="
    INSERT INTO `realname`
    (`id`,`login`,`realname`)
    VALUES
    (NULL, '".$login."','".$realname."');
    ";
nr_query($query);
log_register('CREATE UserRealName ('.$login.') '.$realname);
}


function zb_UserDeleteRealName($login) {
    $login=vf($login);
    $query="DELETE from `realname` WHERE `login` = '".$login."';";
    nr_query($query);
    log_register('DELETE UserRealName '.$login);
}

function zb_UserGetRealName($login) {
    $login=vf($login);
    $query="SELECT `realname` from `realname` WHERE `login`='".$login."'";
    $realname_arr=simple_query($query);
    return($realname_arr['realname']);
}

function zb_UserChangeRealName($login,$realname) {
    $login=vf($login);
    $realname= str_replace("'",'`',$realname);
    $realname=mysql_real_escape_string($realname);
    
    $query="UPDATE `realname` SET `realname` = '".$realname."' WHERE `login`= '".$login."' ;";
    nr_query($query);
    log_register('CHANGE UserRealName ('.$login.') '.$realname);
}


function zb_UserGetAllRealnames() {
    $query_fio="SELECT * from `realname`";
    $allfioz=simple_queryall($query_fio);
    $fioz=array();
    if (!empty ($allfioz)) {
        foreach ($allfioz as $ia=>$eachfio) {
            $fioz[$eachfio['login']]=$eachfio['realname'];
          }
    }
    return($fioz);
}
/**
 * Selects all users' IP addresses from database
 * 
 * @return  array   The array of users' IP addresses
 */
function zb_UserGetAllIPs() {
    $query  = "SELECT `login`,`IP` from `users`";
    $result = simple_queryall($query);
    $ips    = array();
    if ( !empty ($result) ) {
        foreach ( $result as $ip ) {
            $ips[$ip['login']] = $ip['IP'];
        }
    }
    return $ips;
}

function zb_UserGetAllIpMACs() {
    $query="SELECT `ip`,`mac` from `nethosts`";
    $all=simple_queryall($query);
    $result=array();
    if (!empty ($all)) {
        foreach ($all as $io=>$each) {
            $result[$each['ip']]=$each['mac'];
          }
    }
    return($result);
}

function zb_UserGetAllMACs() {
   $alluserips= zb_UserGetAllIPs();
   $alluserips=array_flip($alluserips);
   $allmac=zb_UserGetAllIpMACs();
   
   $result=array();
   
   //filling mac array
   if (!empty($allmac)) {
       foreach ($allmac as $eachip=>$eachmac) {
           
           if (isset($alluserips[$eachip])) {
               $result[$alluserips[$eachip]]=$eachmac;
           }
       }
   }
   
   return ($result);
}



/*
 * User Phone and Mobile management
 * Create, Delete, Get, Change
 */

function zb_UserCreatePhone($login,$phone,$mobile) {
$login=vf($login);
$phone=mysql_real_escape_string($phone);
$mobile=mysql_real_escape_string($mobile);
$query="
    INSERT INTO `phones`
    (`id`,`login`,`phone`,`mobile`)
    VALUES
    (NULL, '".$login."','".$phone."','".$mobile."');
    ";
nr_query($query);
log_register('CREATE UserPhone ('.$login.') '.$phone.' '.$mobile);
}

function zb_UserDeletePhone($login) {
    $login=vf($login);
    $query="DELETE from `phones` WHERE `login` = '".$login."';";
    nr_query($query);
    log_register('DELETE UserPhone '.$login);
}

function zb_UserGetPhone($login) {
    $query="SELECT `phone` from `phones` WHERE `login`='".$login."'";
    $phone_arr=simple_query($query);
    return($phone_arr['phone']);
}


function zb_UserGetMobile($login) {
    $query="SELECT `mobile` from `phones` WHERE `login`='".$login."'";
    $phone_arr=simple_query($query);
    return($phone_arr['mobile']);
}

function zb_UserChangePhone ($login,$phone) {
    $login=vf($login);
    $phone=mysql_real_escape_string($phone);
    $query="UPDATE `phones` SET `phone` = '".$phone."' WHERE `login`= '".$login."' ;";
    nr_query($query);
    log_register('CHANGE UserPhone ('.$login.') '.$phone);
}


function zb_UserChangeMobile ($login,$mobile) {
    $login=vf($login);
    $mobile=mysql_real_escape_string($mobile);
    $query="UPDATE `phones` SET `mobile` = '".$mobile."' WHERE `login`= '".$login."' ;";
    nr_query($query);
    log_register('CHANGE UserMobile ('.$login.') '.$mobile);
}

function zb_UserGetAllPhoneData() {
    $query  = "SELECT `login`, `phone`,`mobile` FROM `phones`";
    $result = simple_queryall($query);
    $phones = array();
    if ( !empty ($result) ) {
        foreach ( $result as $phone ) {
            $phones[$phone['login']]['phone'] =  $phone['phone'];
            $phones[$phone['login']]['mobile'] = $phone['mobile'];
        }
    }
    return ($phones);
}



/*
 * User email management
 *  Create, Delete, Get, Change
 */

function zb_UserCreateEmail($login,$email) {
$login=vf($login);
$email=mysql_real_escape_string($email);
$query="
    INSERT INTO `emails`
    (`id`,`login`,`email`)
    VALUES
    (NULL, '".$login."','".$email."');
    ";
nr_query($query);
log_register('CREATE UserEmail ('.$login.') '.$email);
}


function zb_UserDeleteEmail($login) {
    $login=vf($login);
    $query="DELETE from `emails` WHERE `login` = '".$login."';";
    nr_query($query);
    log_register('DELETE UserEmail ('.$login.')');
}

function zb_UserGetEmail($login) {
    $login=vf($login);
    $query="SELECT `email` from `emails` WHERE `login`='".$login."'";
    $email_arr=simple_query($query);
    return($email_arr['email']);
}

function zb_UserChangeEmail($login,$email) {
    $login=vf($login);
    $email=mysql_real_escape_string($email);
    $query="UPDATE `emails` SET `email` = '".$email."' WHERE `login`= '".$login."' ;";
    nr_query($query);
    log_register('CHANGE UserEmail ('.$login.') '.$email);
}

/*
 * User Contracts management
 * Create, Delete, Get, Change
 */

function zb_UserCreateContract($login,$contract) {
$login=vf($login);
$contract=mysql_real_escape_string($contract);
$query="
    INSERT INTO `contracts`
    (`id`,`login`,`contract`)
    VALUES
    (NULL, '".$login."','".$contract."');
    ";
nr_query($query);
log_register('CREATE UserContract ('.$login.') '.$contract);
}


function zb_UserDeleteContract($login) {
    $login=vf($login);
    $query="DELETE from `contracts` WHERE `login` = '".$login."';";
    nr_query($query);
    log_register('DELETE UserContract ('.$login.')');
}

function zb_UserGetContract($login) {
    $login=vf($login);
    $query="SELECT `contract` from `contracts` WHERE `login`='".$login."'";
    $contract_arr=simple_query($query);
    return($contract_arr['contract']);
}

function zb_UserChangeContract($login,$contract) {
    $login=vf($login);
    $contract=mysql_real_escape_string($contract);
    $query="UPDATE `contracts` SET `contract` = '".$contract."' WHERE `login`= '".$login."' ;";
    nr_query($query);
    log_register('CHANGE UserContract ('.$login.') '.$contract);
}

function zb_UserGetAllContracts() {
    $result=array();
    $query="SELECT * from `contracts`"; 
    $allcontracts=simple_queryall($query);
    if (!empty ($allcontracts)) {
        foreach ($allcontracts as $io=>$eachcontract) {
            $result[$eachcontract['contract']]=$eachcontract['login'];
        }
    }
    return ($result);
}

function zb_UserGetAllEmails() {
    $result=array();
    $query="SELECT * from `emails`"; 
    $all=simple_queryall($query);
    if (!empty ($all)) {
        foreach ($all as $io=>$each) {
            $result[$each['login']]=$each['email'];
        }
    }
    return ($result);
}

function zb_UserGetStargazerData($login) {
    $login=vf($login);
    $query="SELECT * from `users` where `login`='".$login."'";
    $userdata=simple_query($query);
    return($userdata);
}

function zb_UserGetAllStargazerData() {
    $query="SELECT * from `users`";
    $userdata=simple_queryall($query);
    return($userdata);
}

function zb_UserGetAllBalance() {
    $result=array();
    $query="SELECT `login`,`Cash` from `users`";
    $all=simple_queryall($query);
    if (!empty($all)) {
        foreach ($all as $io=>$each) {
            $result[$each['login']]=$each['Cash'];
        }
    }
    return($result);
}

function zb_UserGetSpeedOverride($login) {
    $login=vf($login);
    $query="SELECT `speed` from `userspeeds` where `login`='".$login."'";
    $speed=simple_query($query);
    if (!empty ($speed['speed'])) {
    $speed=$speed['speed'];
    } else {
    $speed=0;    
    }
    return($speed);
}

function zb_UserCreateSpeedOverride($login,$speed) {
    $login=vf($login);
    $speed=vf($speed,3);
    $query="INSERT INTO `userspeeds` (
            `id` ,
            `login` ,
            `speed`
            )
            VALUES (
            NULL , '".$login."', '".$speed."'
            );
        ";
    nr_query($query);
    log_register('CREATE UserSpeedOverride ('.$login.') `'.$speed.'`');
 }
 
function zb_UserDeleteSpeedOverride($login) {
    $login=vf($login);
    $query="DELETE from `userspeeds` WHERE `login`='".$login."'";
    nr_query($query);
    log_register('DELETE UserSpeedOverride ('.$login.')');
}


function zb_UserCreateNotes($login,$notes) {
    $login=vf($login);
    $notes=mysql_real_escape_string($notes);
    $query="INSERT INTO `notes` (
            `id` ,
            `login` ,
            `note`
            )
            VALUES (
            NULL , '".$login."', '".$notes."'
            );
            ";
    nr_query($query);
    log_register('CREATE UserNote ('.$login.') `'.$notes.'`');
}

function zb_UserDeleteNotes($login) {
    $login=vf($login);
    $query="DELETE FROM `notes` WHERE `login`='".$login."'";
    nr_query($query);
    log_register('DELETE UserNote ('.$login.')');
}

function zb_UserGetNotes($login) {
    $login=vf($login);
    $query="SELECT `note` from `notes` WHERE `login`='".$login."'";
    $result=simple_query($query);
    $result=$result['note'];
    return($result);
    }
    
    
    
// returns all of user tariffs and logins pairs
    function zb_TariffsGetAllUsers () {
        $query="SELECT `login`,`Tariff` from `users`";
        $result=array();
        $alltariffuserspairs=simple_queryall($query);
        if (!empty ($alltariffuserspairs)) {
            foreach ($alltariffuserspairs as $io=>$eachuser) {
                $result[$eachuser['login']]=$eachuser['Tariff'];
            }
        }
        return ($result);
    }

// returns all of user LAT and logins pairs
    function zb_LatGetAllUsers () {
        $query="SELECT `login`,`LastActivityTime` from `users`";
        $result=array();
        $allpairs=simple_queryall($query);
        if (!empty ($allpairs)) {
            foreach ($allpairs as $io=>$eachuser) {
                $result[$eachuser['login']]=$eachuser['LastActivityTime'];
            }
        }
        return ($result);
    }   
    
    
    
 // returns all of user cash and logins pairs
    function zb_CashGetAllUsers () {
        $query="SELECT `login`,`Cash` from `users`";
        $result=array();
        $allcashuserspairs=simple_queryall($query);
        if (!empty ($allcashuserspairs)) {
            foreach ($allcashuserspairs as $io=>$eachuser) {
                $result[$eachuser['login']]=$eachuser['Cash'];
            }
        }
        return ($result);
    }
    
 // returns all of user cash and logins pairs
    function zb_CreditGetAllUsers () {
        $query="SELECT `login`,`Credit` from `users`";
        $result=array();
        $alldata=simple_queryall($query);
        if (!empty ($alldata)) {
            foreach ($alldata as $io=>$eachuser) {
                $result[$eachuser['login']]=$eachuser['Credit'];
            }
        }
        return ($result);
    }    
    

  //function that shows signup tariffs popularity  
  function web_SignupGraph() {
        if (!wf_CheckGet(array('month'))) {
            $cmonth=curmonth();
        } else {
            $cmonth=  mysql_real_escape_string($_GET['month']);
        }
        $where="WHERE `date` LIKE '".$cmonth."%'";
        $alltariffnames=zb_TariffsGetAll();
        $tariffusers=zb_TariffsGetAllUsers();
        $allsignups=zb_SignupsGet($where);
        
        $tcount=array();
        if (!empty ($allsignups)) {
            foreach ($alltariffnames as $io=>$eachtariff) {
                foreach ($allsignups as $ii=>$eachsignup) {
              if (@$tariffusers[$eachsignup['login']]==$eachtariff['name']) {
                    @$tcount[$eachtariff['name']]=$tcount[$eachtariff['name']]+1;
              }
             }
            }
            
        }
        
        $tablecells=wf_TableCell(__('Tariff'));
        $tablecells.=wf_TableCell(__('Count'));
        $tablecells.=wf_TableCell(__('Visual'));
        $tablerows=wf_TableRow($tablecells, 'row1');
        
        if (!empty ($tcount)) {
            foreach ($tcount as $sigtariff=>$eachcount) {
                
                $tablecells=wf_TableCell($sigtariff);
                $tablecells.=wf_TableCell($eachcount);
                $tablecells.=wf_TableCell(web_bar($eachcount, sizeof($allsignups)) , '', '', 'sorttable_customkey="'.$eachcount.'"');
                $tablerows.=wf_TableRow($tablecells, 'row3');
            }
        }
        
       $result=wf_TableBody($tablerows, '100%', '0', 'sortable');
        show_window(__('Tariffs report'), $result);
    }
    
   
 function zb_TariffGetPrice($tariff) {
    $tariff=mysql_real_escape_string($tariff);
    $query="SELECT `Fee` from `tariffs` WHERE `name`='".$tariff."'";
    $res=simple_query($query);
    return($res['Fee']); 
 }  
 
  function zb_TariffGetPricesAll() {
    $query="SELECT `name`,`Fee` from `tariffs`";
    $allprices=simple_queryall($query);
    $result=array();
    
    if (!empty ($allprices)) {
        foreach ($allprices as $io=>$eachtariff) {
            $result[$eachtariff['name']]=$eachtariff['Fee'];
        }
    }
    
    return ($result);
 }  
 

  
?>
