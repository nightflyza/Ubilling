<?php

/*
 * 
 *   Bank statements api
 * 
 */



  function bs_UploadFormBody($action,$method,$inputs,$class='') {
     if ($class!='') {
        $form_class=' class="'.$class.'" ';
    } else {
        $form_class='';
    }
    $form='
        <form action="'.$action.'" method="'.$method.'" '.$form_class.' enctype="multipart/form-data">
        '.$inputs.'
        </form>
          <div style="clear:both;"></div>
        ';
    return ($form);
    }
    
    function bs_UploadFileForm() {
    $uploadinputs=wf_HiddenInput('upload','true');
    $uploadinputs.=__('File').' <input id="fileselector" type="file" name="filename" size="10" /><br>';
    $uploadinputs.=wf_Submit('Upload');
    $uploadform=bs_UploadFormBody('', 'POST', $uploadinputs, 'glamour');
    return ($uploadform);
    }
    
 function bs_UploadFile() {
   $timestamp=time();
   //путь сохранения
   $uploaddir = DATA_PATH.'banksta/';
   //белый лист расширений
   $allowedExtensions = array("txt"); 
   //по умолчанию надеемся на худшее
   $result=false;
   
   //проверяем точно ли выписку нам подсовывают
   foreach ($_FILES as $file) {
    if ($file['tmp_name'] > '') {
      if (!in_array(end(explode(".",strtolower($file['name']))),$allowedExtensions)) {
       $errormessage='Wrong file type';
       die($errormessage);
      }
     }
   } 
 
   $filename=vf($_FILES['filename']['name']);
          $uploadfile = $uploaddir . $filename;   
           if (move_uploaded_file($_FILES['filename']['tmp_name'], $uploadfile)) {
               $result=$filename;
           }
           
  return ($result);
}

function bs_FilePush($filename,$rawdata) {
    $filename=vf($filename);
    $rawdata=mysql_real_escape_string($rawdata);
    $query="INSERT INTO `bankstaraw` (
            `id` ,
            `filename` ,
            `rawdata`
            )
            VALUES (
            NULL , '".$filename."', '".$rawdata."'
            );
            ";
    nr_query($query);
    $lastid=  simple_get_lastid('bankstaraw');
    return ($lastid);
}

function bs_CheckHash($hash) {
    $hash=mysql_real_escape_string($hash);
    $query="SELECT COUNT(`id`) from `bankstaparsed` WHERE `hash`='".$hash."'";
    $rowcount=simple_query($query);
    $rowcount=$rowcount['COUNT(`id`)'];
    if ($rowcount>0) {
        return (false);
    } else {
        return(true);
    }
}


 function bs_cu_IsParent($login,$allparentusers) {
     $login=mysql_real_escape_string($login);
     if (isset($allparentusers[$login])) {
        return (true);
    } else {
        return (false);
    }
 }

function bs_ParseRaw($rawid) {
    $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    $bs_options=$alterconf['BS_OPTIONS'];
    //delimiter,data,name,addr,summ
    $options=explode(',',$bs_options);
    //magic numbers, khe khe
    $data_offset=$options[1];
    $realname_offset=$options[2];
    $address_offset=$options[3];
    $summ_offset=$options[4];
    $delimiter=$options[0];
    
    $date=  curdatetime();
    $rawdata_q="SELECT `rawdata` from `bankstaraw` WHERE `id`='".$rawid."'";
    $rawdata=simple_query($rawdata_q);
    $rawdata=$rawdata['rawdata'];
    $hash=md5($rawdata);
    
    $splitrows=  explodeRows($rawdata);
    if (sizeof($splitrows)>$data_offset) {
        $i=0;
        
        foreach ($splitrows as $eachrow) {
           if ($i>=$data_offset) { 
           $rowsplit=explode($delimiter,$eachrow);
           //filter ending
           if (isset($rowsplit[$summ_offset])) {
               $realname=trim(strtolower_utf8($rowsplit[$realname_offset]));
               $address=trim(strtolower_utf8($rowsplit[$address_offset]));
               $realname=str_replace('  ', '', $realname);
               $address=str_replace('  ', '', $address);
               $summ=trim($rowsplit[$summ_offset]);
               $query="INSERT INTO `bankstaparsed` (
                        `id` ,
                        `hash` ,
                        `date` ,
                        `row` ,
                        `realname` ,
                        `address` ,
                        `summ` ,
                        `state` ,
                        `login`
                        )
                        VALUES (
                        NULL ,
                        '".$hash."',
                        '".$date."',
                        '".$i."',
                        '".$realname."',
                        '".$address."',
                        '".$summ."',
                        '0',
                        ''
                        ); 
                        ";

               nr_query($query);
           }
           
             }
           $i++;
        }
    }
  
}

function bs_DeleteBanksta($hash) {
    $hash=vf($hash);
    $query="DELETE from `bankstaparsed` WHERE `hash`='".$hash."'";
    nr_query($query);
    log_register("BANKSTA DELETE ".$hash);
}


function bs_CheckProcessed($hash) {
    $hash=vf($hash);
    $query="SELECT COUNT(`id`) from `bankstaparsed` WHERE `hash`='".$hash."' and `state`='0'"; 
    $notprocessed=simple_query($query);
    if (($notprocessed['COUNT(`id`)'])!=0) {
        $result=web_bool_led(false).' <sup>('.$notprocessed['COUNT(`id`)'].')</sup>';
    } else {
        $result=web_bool_led(true).' <sup>('.$notprocessed['COUNT(`id`)'].')</sup>';
    }
    return ($result);
}

function bs_ShowAllStatements() {
    $query="SELECT DISTINCT `hash`,`date` from `bankstaparsed` ORDER BY `date` DESC";
    $allstatements=simple_queryall($query);
    if (!empty($allstatements)) {
       $tablecells=wf_TableCell(__('Date'));
       $tablecells.=wf_TableCell(__('Payments count'));
       $tablecells.=wf_TableCell(__('Processed'));
       $tablecells.=wf_TableCell(__('Actions'));
       $tablerows=  wf_TableRow($tablecells,'row1');
       foreach ($allstatements as $io=>$eachstatement) {
           $statementlink=wf_Link("?module=bankstatements&showhash=".$eachstatement['hash'], $eachstatement['date']);
           $rowcount_q="SELECT COUNT(`id`) from `bankstaparsed` WHERE `hash`='".$eachstatement['hash']."'";
           $rowcount=  simple_query($rowcount_q);
           $rowcount=$rowcount['COUNT(`id`)'];
           $tablecells=wf_TableCell($statementlink);
           $tablecells.=wf_TableCell($rowcount);
           $tablecells.=wf_TableCell(bs_CheckProcessed($eachstatement['hash']));
           $tablecells.=wf_TableCell(wf_JSAlert('?module=bankstatements&deletehash='.$eachstatement['hash'], web_delete_icon(), 'Removing this may lead to irreparable results'));
           $tablerows.=wf_TableRow($tablecells, 'row3');
       }
       $result=wf_TableBody($tablerows, '100%', '0', 'sortable');
    } else {
        $result=__('Any statements uploaded');
    }
    
    show_window(__('Previously uploaded statements'),$result);
}

function bs_LoginProposalForm($id,$login='') {
    $id=vf($id,3);
    if (!empty ($login)) {
        $loginform=web_bool_led(true).'<a href="?module=userprofile&username='.$login.'">'.web_profile_icon().' '.$login.'</a>';
    } else {
        $loginform=  web_bool_led(false);
    }
    return ($loginform);
}

function bs_NameEditForm($id,$name='') {
    $id=vf($id,3);
    $inputs=wf_HiddenInput('editrowid',$id);
    $inputs.=wf_TextInput('editrealname', '', $name, false, '10');
    $inputs.=wf_Submit(__('Save'));
    $form=  wf_Form("", 'POST', $inputs, '');
    return ($form);
}


function bs_AddressEditForm($id,$address='') {
    $id=vf($id,3);
    $inputs=wf_HiddenInput('editrowid',$id);
    $inputs.=wf_TextInput('editaddress', '', $address, false, '20');
    $inputs.=wf_Submit(__('Save'));
    $form=  wf_Form("", 'POST', $inputs, '');
    return ($form);
}


function bs_SearchCheckArr($alluseraddress,$allrealnames) {
    $checkarr=array();
        foreach ($alluseraddress as $addrlogin=>$eachaddr) {
            @$splitname=explode(' ',$allrealnames[$addrlogin]);
            $checkarr[$addrlogin]['address']=$eachaddr;
            $checkarr[$addrlogin]['realname']=$splitname[0];
        }
    return ($checkarr);
}

function bs_SearchLoginByAddresspart($queryaddress,$queryname,$checkarr) {
        $queryaddress=mysql_real_escape_string($queryaddress);
        $queryaddress=strtolower_utf8($queryaddress);
        $queryname=mysql_real_escape_string($queryname);
        $queryname=strtolower_utf8($queryname);
        $result=array();


        if (!empty ($checkarr)) {
        foreach ($checkarr as $io=>$check) {
            // искаем логин по паре фамилия+пароль
            if (ispos($queryaddress,strtolower_utf8($check['address']))) {
                if (!empty ($check['realname'])) {
                if (ispos($queryname,strtolower_utf8($check['realname']))) {
                    $result[]=$io;
                 }
                }
            }
         
         
        }
        }
        return ($result);

}


function bs_NameEdit($id,$name) {
    $id=vf($id,3);
    $name=mysql_real_escape_string($name);
    simple_update_field('bankstaparsed', 'realname', $name, "WHERE `id`='".$id."'");
}

function bs_AddressEdit($id,$address) {
    $id=vf($id,3);
    $address=mysql_real_escape_string($address);
    simple_update_field('bankstaparsed', 'address', $address, "WHERE `id`='".$id."'");
}

function bs_ShowHash($hash) {
    $hash=vf($hash);
    $allrealnames=  zb_UserGetAllRealnames();
    $alladdress=  zb_AddressGetFulladdresslist();
    $checkarr=bs_SearchCheckArr($alladdress, $allrealnames);
    $alter_conf=rcms_parse_ini_file(CONFIG_PATH.'alter.ini');
    $query="SELECT * from `bankstaparsed` WHERE `hash`='".$hash."' ORDER BY `id` DESC";
    $alldata=  simple_queryall($query);
      // проверяем врублены ли корпоративные пользователи
      if ($alter_conf['USER_LINKING_ENABLED']) {
                    $alllinkedusers=cu_GetAllLinkedUsers();
                    $allparentusers=cu_GetAllParentUsers();
      }

    if (!empty($alldata)) {
        $tablecells=wf_TableCell(__('ID'));
        $tablecells.=wf_TableCell(__('Real Name'));
        $tablecells.=wf_TableCell(__('Address'));
        $tablecells.=wf_TableCell(__('Cash'));
        $tablecells.=wf_TableCell(__('Login poroposal'));
        $tablecells.=wf_TableCell(__('Processed'));
        $tablecells.=wf_TableCell(__('Actions'));
        $tablerows=wf_TableRow($tablecells, 'row1');
        
        foreach ($alldata as $io=>$eachrow) {
            
        $tablecells=wf_TableCell($eachrow['id']);
        $tablecells.=wf_TableCell(bs_NameEditForm($eachrow['id'], $eachrow['realname']));
        $tablecells.=wf_TableCell(bs_AddressEditForm($eachrow['id'], $eachrow['address']));
        $tablecells.=wf_TableCell($eachrow['summ']);
        //proposal subroutine
        if  (empty($eachrow['login'])) {
          $proposed_login=bs_SearchLoginByAddresspart($eachrow['address'], $eachrow['realname'], $checkarr);
          //if no one found
          if (sizeof($proposed_login)==0) {
              $proposal_form=bs_LoginProposalForm($eachrow['id'], '');
          }
          //if only one user found
          if (sizeof($proposed_login)==1) {
              $proposal_form=bs_LoginProposalForm($eachrow['id'], $proposed_login[0]);
              //заполним со старта что-ли
              simple_update_field('bankstaparsed', 'login', $proposed_login[0], "WHERE `id`='".$eachrow['id']."'");
          }
          
          //if many users found
          if (sizeof($proposed_login)>1) {
              //считаем что это корпоративный пользователь и достаем для него родительского
               if ($alter_conf['USER_LINKING_ENABLED']) {
                      
                   foreach ($proposed_login as $eachcorp) {
                       if (bs_cu_IsParent($eachcorp,$allparentusers)) {
                           $proposal_form=web_corporate_icon().' '.$eachcorp;
                           //заполним родительского пользователя
                           simple_update_field('bankstaparsed', 'login', $eachcorp, "WHERE `id`='".$eachrow['id']."'");
                       }
                   }
                    
                      
                    } else {
                        // если корпоративщина не включена - вываливаем екзепшн
                        $proposal_form=__('Multiple users found');
                    }
               
          }
          
        } else {
          $proposal_form=bs_LoginProposalForm($eachrow['id'], $eachrow['login']);    
        }
        $tablecells.=wf_TableCell($proposal_form);
        $procflag=  web_bool_led($eachrow['state']);
        if (!$eachrow['state']) {
            $actlink=wf_JSAlert("?module=bankstatements&lockrow=".$eachrow['id']."&showhash=".$eachrow['hash'], web_key_icon('Lock'), __('Are you serious'));
        } else {
            $actlink='';
        }
        
        
        $tablecells.=wf_TableCell($procflag);
        $tablecells.=wf_TableCell($actlink);
        $tablerows.=wf_TableRow($tablecells, 'row3');
            
        }
        
        $result=  wf_TableBody($tablerows, '100%', '0', 'sortable');
        
    } else {
        $result=__('Strange exeption catched');
    }
    
    show_window('', wf_BackLink("?module=bankstatements", 'Back', true));
    show_window(__('Bank statement processing'),$result);
}

function bs_ProcessHash($hash) {
    global $billing;

    $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    //corporate users handling
    if ($alterconf['USER_LINKING_ENABLED']) {
        $allparentusers=cu_GetAllParentUsers();
    }
    
    $query="SELECT `id`,`summ`,`login` from `bankstaparsed` WHERE `hash`='".$hash."' AND `state`='0' AND `login` !=''";
    $allinprocessed=simple_queryall($query);
    if (!empty ($allinprocessed)) {
        log_register("BANKSTA PROCESSING ".$hash." START");
        
        foreach ($allinprocessed as $io=>$eachrow) {
            //setting payment variables
             $operation='add';
             $cashtype=$alterconf['BS_CASHTYPE'];
             $cash=$eachrow['summ'];
             $note=mysql_real_escape_string("BANKSTA:".$eachrow['id']);
             
            
            // CU filter subroutine
            if ($alterconf['USER_LINKING_ENABLED']) {
            if (!bs_cu_IsParent($eachrow['login'],$allparentusers)) {
                //normal user cash
                zb_CashAdd($eachrow['login'], $cash, $operation, $cashtype, $note);
                simple_update_field('bankstaparsed', 'state', '1', "WHERE `id`='".$eachrow['id']."'");
                
            } else {
                //corporate user
                $userlink=$allparentusers[$eachrow['login']];
                $allchildusers=cu_GetAllChildUsers($userlink);
                // adding natural payment to parent user
                zb_CashAdd($eachrow['login'], $cash, $operation, $cashtype, $note);
                simple_update_field('bankstaparsed', 'state', '1', "WHERE `id`='".$eachrow['id']."'");
                
                if (!empty ($allchildusers)) {
                    foreach ($allchildusers as $eachchild) {
                       //adding quiet payments for child users
                       $billing->addcash($eachchild,$cash); 
                       log_register("BANKSTA GROUPBALANCE ".$eachchild." ".$operation." ON ".$cash);
                    }
                }
                
                // end of processing with linking
             }
            } else {
                // standalone user cash push
                 zb_CashAdd($eachrow['login'], $cash, $operation, $cashtype, $note);
                 simple_update_field('bankstaparsed', 'state', '1', "WHERE `id`='".$eachrow['id']."'");
                // end of processing without linking
            }
                
        }
        
        log_register("BANKSTA PROCESSING ".$hash." END");
    } else {
        log_register("BANKSTA PROCESSING ".$hash." EMPTY");
    }
}


function bs_ProcessingForm($hash) {
    $hash=vf($hash);
    
    $inputs=wf_HiddenInput('processingrequest', $hash);
    $inputs.=wf_Submit('Process all payments for which the user defined');
    $result=wf_Form("", 'POST', $inputs, 'glamour');
    show_window('',$result);
}

function bs_LockRow($rowid) {
    $rowid=vf($rowid,3);
    simple_update_field('bankstaparsed', 'state', '1', "WHERE `id`='".$rowid."'");
    log_register("BANKSTA LOCK ROW ".$rowid);
}

?>
