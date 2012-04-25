<?php

function zbs_UserDetectIp($debug=false)  {
    $ip=$_SERVER['REMOTE_ADDR'];
    if (!$ip) {
        die('Strange error');
    }
    if ($debug=='debug') {
     //$ip='172.30.0.2';   
    }
    return($ip);
}

function zbs_UserGetLoginByIp($ip) {
    $query="SELECT `login` from `users` where `IP`='".$ip."'";
    $result=simple_query($query);
    if (!empty ($result)) {
        return($result['login']);
    } else {
        die('Unknown user');
    }
}



function zbs_LangSelector() {
    $glob_conf=zbs_LoadConfig();
    if ($glob_conf['allowclang']) {
    $allangs=rcms_scandir("languages");
    if (!empty ($allangs)) {
    $form='<form action="" method="GET">';
    $form.='<select name="changelang" onChange="this.form.submit();">';
    $form.='<option value="-">'.__('Language').'</option>';
    foreach ($allangs as $eachlang) {
        $eachlangid=file_get_contents("languages/".$eachlang."/langid.txt");
        $form.='<option value="'.$eachlang.'">'.$eachlangid.'</option>';
    }
    $form.='</select>';
    $form.=' ';
    $form.='</form>';    
    }
    } else {
        $form='';
    }
    return($form);
}

function zbs_AddressGetFullCityNames() {
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

function zbs_AddressGetFulladdresslist() {
$alterconf=zbs_LoadConfig();
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
    $cities=zbs_AddressGetFullCityNames();
    
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

function zbs_UserGetStargazerData($login) {
    $login=mysql_real_escape_string($login);
    $query="SELECT * from `users` WHERE `login`='".$login."'";
    $result=simple_query($query);
    return($result);
}

function zbs_UserGetAllRealnames() {
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

function zbs_UserGetContract($login) {
    $login=vf($login);
    $query="SELECT `contract` from `contracts` WHERE `login`='".$login."'";
    $contract_arr=simple_query($query);
    return($contract_arr['contract']);
}

function zbs_UserGetEmail($login) {
    $login=vf($login);
    $query="SELECT `email` from `emails` WHERE `login`='".$login."'";
    $email_arr=simple_query($query);
    return($email_arr['email']);
}

function zbs_UserGetMobile($login) {
    $query="SELECT `mobile` from `phones` WHERE `login`='".$login."'";
    $phone_arr=simple_query($query);
    return($phone_arr['mobile']);
}

function zbs_UserGetPhone($login) {
    $query="SELECT `phone` from `phones` WHERE `login`='".$login."'";
    $phone_arr=simple_query($query);
    return($phone_arr['phone']);
}

function zbs_UserShowProfile($login) {
    $us_config=zbs_LoadConfig();
    $us_currency=$us_config['currency'];
    $userdata=zbs_UserGetStargazerData($login);
    $alladdress=zbs_AddressGetFulladdresslist();
    $allrealnames=zbs_UserGetAllRealnames();
    $contract=zbs_UserGetContract($login);
    $email=zbs_UserGetEmail($login);
    $mobile=zbs_UserGetMobile($login);
    $phone=zbs_UserGetPhone($login);
    
    if ($userdata['CreditExpire']!=0) {
    $credexpire=date("d-m-Y",$userdata['CreditExpire']);
    } else {
    $credexpire='';    
    }
    $profile='
        <table width="100%" border="0">
            <tr>
            <td class="row1">'.__('Address').'</td>
            <td>'.@$alladdress[$login].'</td>
            </tr>
            
            <tr>
            <td class="row1">'.__('Real name').'</td>
            <td>'.@$allrealnames[$login].'</td>
            </tr>
            
            <tr>
            <td class="row1">'.__('Login').'</td>
            <td>'.$login.'</td>
            </tr>
            
            <tr>
            <td class="row1">'.__('Password').'</td>
            <td>'.$userdata['Password'].'</td>
            </tr>
            
            <tr>
            <td class="row1">'.__('IP').'</td>
            <td>'.$userdata['IP'].'</td>
            </tr>
                       
            <tr>
            <td class="row1">'.__('Phone').'</td>
            <td>'.$phone.'</td>
            </tr>
            
            <tr>
            <td class="row1">'.__('Mobile').'</td>
            <td>'.$mobile.'</td>
            </tr>
            
            <tr>
            <td class="row1">'.__('Email').'</td>
            <td>'.$email.'</td>
            </tr>
            
            <tr>
            <td class="row1">'.__('Payment ID').'</td>
            <td>'.ip2int($userdata['IP']).'</td>
            </tr>
            
            <tr>
            <td class="row1">'.__('Contract').'</td>
            <td>'.$contract.'</td>
            </tr>
            
           
            
            <tr>
            <td class="row1">'.__('Balance').'</td>
            <td>'.$userdata['Cash'].' '.$us_currency.'</td>
            </tr>
            
            <tr>
            <td class="row1">'.__('Credit').'</td>
            <td>'.$userdata['Credit'].' '.$us_currency.'</td>
            </tr>
            
            <tr>
            <td class="row1">'.__('Credit Expire').'</td>
            <td>'.$credexpire.'</td>
            </tr>
            
            <tr>
            <td class="row1">'.__('Tariff').'</td>
            <td>'.$userdata['Tariff'].'</td>
            </tr>
            
            <tr>
            <td class="row1">'.__('Tariff change').'</td>
            <td>'.$userdata['TariffChange'].'</td>
            </tr>
            </table>
        ';
    return($profile);
}

function zbs_CashGetUserPayments($login) {
    $login=vf($login);
    $query="SELECT * from `payments` WHERE `login`='".$login."' ORDER BY `id` DESC";
    $allpayments=simple_queryall($query);
    return($allpayments);
   }
 
    function zbs_UserTraffStats($login) {
       $login=vf($login);
       $alldirs=zbs_DirectionsGetAll();
       /*
        * Current month traffic stats
        */
       $result='<h3>'.__('Current month traffic stats').'</h3>
           <table width="100%" border="0" class="sortable">';
       $result.='<tr class="row1">
                        <td>'.__('Traffic classes').'</td>
                        <td>'.__('Downloaded').'</td>
                        <td>'.__('Uploaded').'</td>
                        <td>'.__('Total').'</td>
                        </tr>';
            if (!empty ($alldirs)) {
                foreach ($alldirs as $io=>$eachdir) {
                    $query_downup="SELECT `D".$eachdir['rulenumber']."`,`U".$eachdir['rulenumber']."` from `users` WHERE `login`='".$login."'";
                    $downup=simple_query($query_downup);
                    $result.='
                        <tr class="row3">
                        <td>'.$eachdir['rulename'].'</td>
                        <td>'.zbs_convert_size($downup['D'.$eachdir['rulenumber']]).'</td>
                        <td>'.zbs_convert_size($downup['U'.$eachdir['rulenumber']]).'</td>
                        <td>'.zbs_convert_size(($downup['U'.$eachdir['rulenumber']]+$downup['D'.$eachdir['rulenumber']])).'</td>
                        </tr>';
                }
            }
       $result.='</table> <br><br>';
             
       /*
        * traffic stats by previous months
        */
     $result.='<h3>'.__('Previous month traffic stats').'</h3>
           <table width="100%" border="0" class="sortable">';
     $result.='
                        <tr class="row1">
                        <td>'.__('Year').'</td>
                        <td>'.__('Month').'</td>
                        <td>'.__('Traffic classes').'</td>
                        <td>'.__('Downloaded').'</td>
                        <td>'.__('Uploaded').'</td>
                        <td>'.__('Total').'</td>
                        <td>'.__('Cash').'</td>
                        </tr>';

       if (!empty ($alldirs)) {
           foreach ($alldirs as $io=>$eachdir) {
               $query_prev="SELECT `D".$eachdir['rulenumber']."`,`U".$eachdir['rulenumber']."`,`month`,`year`,`cash` from `stat` WHERE `login`='".$login."' ORDER BY YEAR";
               $allprevmonth=simple_queryall($query_prev);
                if (!empty ($allprevmonth)) {
                   foreach ($allprevmonth as $io2=>$eachprevmonth) {
                    $result.='
                        <tr class="row3">
                        <td>'.$eachprevmonth['year'].'</td>
                        <td>'.$eachprevmonth['month'].'</td>
                        <td>'.$eachdir['rulename'].'</td>
                        <td>'.zbs_convert_size($eachprevmonth['D'.$eachdir['rulenumber']]).'</td>
                        <td>'.zbs_convert_size($eachprevmonth['U'.$eachdir['rulenumber']]).'</td>
                        <td>'.zbs_convert_size(($eachprevmonth['U'.$eachdir['rulenumber']]+$eachprevmonth['D'.$eachdir['rulenumber']])).'</td>
                        <td>'.round($eachprevmonth['cash'],2).'</td>
                        </tr>';

                   }
               }
           }
       }
       $result.='</table>';
       
       return($result);
   }
   
    function zbs_DirectionsGetAll() {
        $query="SELECT * from `directions`";
        $allrules=simple_queryall($query);
        return ($allrules);
    }
   
    function zbs_convert_size($fs)
{
     if ($fs >= 1073741824) 
      $fs = round($fs / 1073741824 * 100) / 100 . " Gb";
     elseif ($fs >= 1048576)
      $fs = round($fs / 1048576 * 100) / 100 . " Mb";
     elseif ($fs >= 1024)
      $fs = round($fs / 1024 * 100) / 100 . " Kb";
     else
      $fs = $fs . " b";
     return ($fs);
}

function zbs_ModulesMenuShow ($icons=false) {
    $mod_path="config/modules.d/";
    $all_modules=rcms_scandir($mod_path);
    $result='';
    if (!empty ($all_modules))  {
        foreach ($all_modules as $eachmodule) {
            if ($icons==true) {
                if (file_exists("iconz/".$eachmodule.".gif")) {
                    $iconlink=' <img src="iconz/'.$eachmodule.'.gif"> ';
                } else {
                    $iconlink='';
                }
            } else {
                $iconlink='';
            }
            $mod_name=trim(file_get_contents($mod_path.$eachmodule));
            $result.='<li><a href="?module='.$eachmodule.'">'.$iconlink.''.__($mod_name).'</a></li>';
        }
    }
    return($result);   
}

function zbs_PaymentLog($login,$summ,$cashtypeid,$note) {
    $cashtypeid=vf($cashtypeid);
    $ctime=curdatetime();
    $userdata=zbs_UserGetStargazerData($login);
    $balance=$userdata['Cash'];
    $note=mysql_real_escape_string($note); 
    $query="
        INSERT INTO `payments` (
        `id` ,
        `login` ,
        `date` ,
        `admin` ,
        `balance` ,
        `summ` ,
        `cashtypeid` ,
        `note`
        )
        VALUES (
        NULL , '".$login."', '".$ctime."', 'external', '".$balance."', '".$summ."', '".$cashtypeid."', '".$note."'
        );
        ";
    nr_query($query);
}

function executor($command,$debug=false) {
$globconf=zbs_LoadConfig();
$SGCONF=$globconf['SGCONF'];
$STG_HOST=$globconf['STG_HOST'];
$STG_PORT=$globconf['STG_PORT'];
$STG_LOGIN=$globconf['STG_LOGIN'];
$STG_PASSWD=$globconf['STG_PASSWD'];
$configurator=$SGCONF.' set -s '.$STG_HOST.' -p '.$STG_PORT.' -a'.$STG_LOGIN.' -w'.$STG_PASSWD.' '.$command;
if ($debug) {
     print($configurator."\n");
     print(shell_exec($configurator));
    } else {
     shell_exec($configurator);
    }
}

function billing_addcash($login,$cash) {
        executor('-u'.$login.' -c '.$cash);
}

function billing_setcredit($login,$credit) {
    executor('-u'.$login.' -r '.$credit);
}

function billing_setcreditexpire($login,$creditexpire) {
    executor('-u'.$login.' -E '.$creditexpire);
}

function billing_setcash($login,$cash) {
    executor('-u'.$login.' -v '.$cash);
}

function billing_settariff($login,$tariff) {
    executor('-u'.$login.' -t '.$tariff);
}

function billing_settariffnm($login,$tariff) {
   executor('-u'.$login.' -t '.$tariff.':delayed');
}

function whoami() {
    $mylogin='external';
    return($mylogin);
}

function log_register($event) {
    $admin_login=whoami();
    $ip=$_SERVER['REMOTE_ADDR'];
    $current_time=curdatetime();
    $event=mysql_real_escape_string($event);
    $query="INSERT INTO `weblogs` (`id`,`date`,`admin`,`ip`,`event`) VALUES(NULL,'".$current_time."','".$admin_login."','".$ip."','".$event."')";
    nr_query($query);
}

function zbs_CashGetUserBalance($login) {
    $login=vf($login);
    $query="SELECT `Cash` from `users` WHERE `login`='".$login."'";
    $cash=simple_query($query);
    return($cash['Cash']); 
    
}

function zbs_CashGetUserCredit($login) {
    $login=vf($login);
    $query="SELECT `Credit` from `users` WHERE `login`='".$login."'";
    $cash=simple_query($query);
    return($cash['Credit']); 
 }
 
 function zbs_CashGetUserCreditExpire($login) {
    $login=vf($login);
    $query="SELECT `CreditExpire` from `users` WHERE `login`='".$login."'";
    $cash=simple_query($query);
    return($cash['CreditExpire']); 
 }

 function zbs_UserGetTariff($login) {
    $login=mysql_real_escape_string($login);
    $query="SELECT `Tariff` from `users` WHERE `login`='".$login."'";
    $res=simple_query($query);
    return($res['Tariff']); 
 }
 
 function zbs_UserGetTariffPrice($tariff) {
    $login=mysql_real_escape_string($tariff);
    $query="SELECT `Fee` from `tariffs` WHERE `name`='".$tariff."'";
    $res=simple_query($query);
    return($res['Fee']); 
 }

function zbs_CashAdd($login,$cash,$note) {
    $login=vf($login);
    $cash=mysql_real_escape_string($cash);
    $cashtype=0;
    $note=mysql_real_escape_string($note);
    $date=curdatetime();
    $balance=zb_CashGetUserBalance($login);
    billing_addcash($login,$cash); 
    $query="INSERT INTO `payments` (
                `id` ,
                `login` ,
                `date` ,
                `balance` ,
                `summ` ,
                `cashtypeid` ,
                `note`
                )
                VALUES (
                NULL , '".$login."', '".$date."', '".$balance."', '".$cash."', '".$cashtype."', '".$note."'
                );";
   
   nr_query($query);
   log_register("BALANCECHANGE ".$login.' ON '.$cash);
}

?>
