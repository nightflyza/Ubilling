<?php
if (cfr('ROOT')) {
set_time_limit (0);

function web_MigrationUploadForm() {
    $delimiters=array(
                      ';'=>';',
                      '|'=>'|',
                      ','=>','
                     );
    
    $encodings=array(
        'utf-8'=>'utf-8',
        'windows-1251'=>'windows-1251',
        'koi8-u'=>'koi8-u',
        'cp866'=>'cp866'
    );
    
    $uploadinputs=wf_HiddenInput('uploaduserbase','true');
    $uploadinputs.=__('Upload userbase').' <input id="fileselector" type="file" name="uluserbase" size="10" /><br>';
    $uploadinputs.=wf_Selector('delimiter', $delimiters, __('Delimiter'), '', true);
    $uploadinputs.=wf_Selector('encoding', $encodings, __('Encoding'), '', true);
    $uploadinputs.=wf_Submit('Upload');
    $uploadform=bs_UploadFormBody('', 'POST', $uploadinputs, 'glamour');
    return ($uploadform);;
    
}    


 function migrate_UploadFile() {
   //путь сохранения
   $uploaddir = 'exports/';
   //белый лист расширений
   $allowedExtensions = array("txt","csv"); 
   //по умолчанию надеемся на худшее
   $result=false;
   
   //проверяем точно ли текстовку нам подсовывают
   foreach ($_FILES as $file) {
    if ($file['tmp_name'] > '') {
      if (@!in_array(end(explode(".",strtolower($file['name']))),$allowedExtensions)) {
       $errormessage='Wrong file type';
       die($errormessage);
      }
     }
   } 
 
   $filename=vf($_FILES['uluserbase']['name']);
          $uploadfile = $uploaddir . $filename;   
           if (move_uploaded_file($_FILES['uluserbase']['tmp_name'], $uploadfile)) {
               $result=$filename;
           }
           
  return ($result);
}


function web_MigrationPreprocessing($filename,$delimiter,$encoding) {
    $path='exports/';
    $raw_data=  file_get_contents($path.$filename);
    $parsed_data=array();
    
    if ($encoding!='utf-8') {
        $raw_data= iconv($encoding, 'utf-8', $raw_data);
    }
    $raw_data=  explodeRows($raw_data);
    
    if (!empty($raw_data)) {
        foreach ($raw_data as $eachrow) {
            if (!empty($eachrow)) {
            $parsed_data[]=  explode($delimiter, $eachrow);
            }
        }
    }
    
    if (sizeof($parsed_data)>1) {
        $col_count=  sizeof($parsed_data[0]);
        
        $cells=  wf_TableCell(__('Conumn number'));
        $cells.= wf_TableCell(__('Column content'));
        $rows=   wf_TableRow($cells, 'row1');
        
        foreach ($parsed_data[0] as $col_num=>$col_data) {
        $cells=  wf_TableCell($col_num);
        $cells.= wf_TableCell($col_data);
        $rows.=  wf_TableRow($cells, 'row3');
        
        }
        
        $first_row=  wf_TableBody($rows, '100%', '0', '');
        show_window(__('Found count of data columns'),$col_count);
        show_window(__('First of imported data rows'), $first_row);
        
        //construct of data processing form
        $rowNumArr=array();
        for ($i=0;$i<$col_count;$i++) {
            $rowNumArr[$i]=$i;
        }
        
        $login_arr=$rowNumArr+array('RANDOM'=>__('Generate Random'));
        $password_arr=$rowNumArr+array('RANDOM'=>__('Generate Random'));
        $ip_arr=$rowNumArr;
        $mac_arr=$rowNumArr+array('RANDOM'=>__('Generate Random'));
        $tariff_arr=$rowNumArr;
        $cash_arr=$rowNumArr;
        $credit_arr=$rowNumArr+array('ZERO'=>__('Set to zero'));
        $creditex_arr=$rowNumArr+array('NONE'=>__('Set to none'));
        $phone_arr=$rowNumArr+array('NONE'=>__('Set to none'));
        $mobile_arr=$rowNumArr+array('NONE'=>__('Set to none'));
        $email_arr=$rowNumArr+array('NONE'=>__('Set to none'));
        $address_arr=$rowNumArr+array('NONE'=>__('Set to none'));
        $realname_arr=$rowNumArr+array('NONE'=>__('Set to none'));
        $contract_arr=$rowNumArr+array('NONE'=>__('Set to none'));
        $ao_arr=$rowNumArr+array('AO_1'=>__('AlwaysOnline=1'));
        $down_arr=$rowNumArr+array('DOWN_0'=>__('Down=0'));
        $passive_arr=$rowNumArr+array('PASSIVE_0'=>__('Passive=0'));
        $regtype_arr=array('SQL'=>'Show SQL dump','UB'=>'Ubilling live register');
        
        //data column setting form
        $inputs=  wf_Selector('login_col', $login_arr, __('User login'), '', true);
        $inputs.=  wf_Selector('password_col', $password_arr, __('User password'), '', true);
        $inputs.=  wf_Selector('ip_col', $ip_arr, __('User IP'), '', true);
        $inputs.=  wf_Selector('mac_col', $mac_arr, __('User MAC'), '', true);
        $inputs.=  wf_Selector('tariff_col', $tariff_arr, __('User tariff'), '', true);
        $inputs.=  wf_Selector('cash_col', $cash_arr, __('User cash'), '', true);
        $inputs.=  wf_Selector('credit_col', $credit_arr, __('User credit limit'), '', true);
        $inputs.=  wf_Selector('creditex_col', $creditex_arr, __('User credit expire date'), '', true);
        $inputs.=  wf_Selector('phone_col', $phone_arr, __('User phone'), '', true);
        $inputs.=  wf_Selector('mobile_col', $mobile_arr, __('User mobile'), '', true);
        $inputs.=  wf_Selector('email_col', $email_arr, __('User email'), '', true);
        $inputs.=  wf_Selector('address_col', $address_arr, __('User address'), '', true);
        $inputs.=  wf_Selector('realname_col', $realname_arr, __('User realname'), '', true);
        $inputs.=  wf_Selector('contract_col', $contract_arr, __('User contract'), '', true);
        $inputs.=  wf_Selector('ao_col', $ao_arr, __('User AlwaysOnline state'), '', true);
        $inputs.=  wf_Selector('down_col', $down_arr, __('User Down state'), '', true);
        $inputs.=  wf_Selector('passive_col', $passive_arr, __('User Passive state'), '', true);
        $inputs.=  wf_Selector('regtype', $regtype_arr, __('User registration mode'), '', true);
        $inputs.= multinet_network_selector().__('Target network').  wf_delimiter();
        $inputs.= wf_HiddenInput('import_rawdata', base64_encode(serialize($parsed_data)));
        $inputs.=wf_Submit('Save this column pointers and continue import');
        $colform=  wf_Form("?module=migration&setpointers=true", 'POST', $inputs, 'glamour');
        show_window(__('Select data columns and their values'),$colform);
        
    } else {
        show_error(__('Parsing error'));
    }
    
}

function web_MigrationPrepare($import_rawdata,$import_opts) {
    $import_rawdata=  unserialize(base64_decode($import_rawdata));
    $import_opts=  unserialize(base64_decode($import_opts));
    
    $cells =  wf_TableCell('#');
    $cells.=  wf_TableCell('[login]');
    $cells.=  wf_TableCell('[password]');
    $cells.=  wf_TableCell('[ip]');
    $cells.=  wf_TableCell('[mac]');
    $cells.=  wf_TableCell('[tariff]');
    $cells.=  wf_TableCell('[cash]');
    $cells.=  wf_TableCell('[phone]');
    $cells.=  wf_TableCell('[mobile]');
    $cells.=  wf_TableCell('[email]');
    $cells.=  wf_TableCell('[credit]');
    $cells.=  wf_TableCell('[creditex]');
    $cells.=  wf_TableCell('[address]');
    $cells.=  wf_TableCell('[realname]');
    $cells.=  wf_TableCell('[contract]');
    $cells.=  wf_TableCell('[ao]');
    $cells.=  wf_TableCell('[down]');
    $cells.=  wf_TableCell('[passive]');
    $rows=wf_TableRow($cells, 'row1');
    
    $regdata=array();
    $i=0;
    
    foreach ($import_rawdata as $eachrow) {
    $i++;    
    $cells =  wf_TableCell($i);    
    if ($import_opts['login_col']!='RANDOM') {
        $login=$eachrow[$import_opts['login_col']];
    } else {
        $login=  'mi_'.zb_rand_string(8);
    }
    
    $cells.=  wf_TableCell($login);
    
    if ($import_opts['password_col']!='RANDOM') {
        $password=$eachrow[$import_opts['password_col']];
    } else {
        $password=  zb_rand_string(10);
    }
    $cells.=  wf_TableCell($password);
    
    $ip=$eachrow[$import_opts['ip_col']];
    $cells.=  wf_TableCell($ip);
    
    if ($import_opts['mac_col']!='RANDOM') {
        $mac=$eachrow[$import_opts['mac_col']];
    } else {
        $mac='14:'.'88'.':'.rand(10,99).':'.rand(10,99).':'.rand(10,99).':'.rand(10,99);
    }
    $cells.=  wf_TableCell($mac);
    
    $tariff=$eachrow[$import_opts['tariff_col']];
    $cells.=  wf_TableCell($tariff);
    
    $cash=$eachrow[$import_opts['cash_col']];
    $cells.=  wf_TableCell($cash);
    
    if ($import_opts['phone_col']!='NONE') {
        $phone=$eachrow[$import_opts['phone_col']];
    } else {
        $phone='';
    }
    $cells.=  wf_TableCell($phone);
    
    if ($import_opts['mobile_col']!='NONE') {
        $mobile=$eachrow[$import_opts['mobile_col']];
    } else {
        $mobile='';
    }
    $cells.=  wf_TableCell($mobile);
  
    if ($import_opts['email_col']!='NONE') {
        $email=$eachrow[$import_opts['email_col']];
    } else {
        $email='';
    }
    $cells.=  wf_TableCell($email);
  
    if ($import_opts['credit_col']!='ZERO') {
        $credit=$eachrow[$import_opts['credit_col']];
    } else {
        $credit=0;
    }
    $cells.=  wf_TableCell($credit);
    
    if ($import_opts['creditex_col']!='NONE') {
        $creditex=$eachrow[$import_opts['creditex_col']];
    } else {
        $creditex='0';
    }
    $cells.=  wf_TableCell($creditex);
    
    if ($import_opts['address_col']!='NONE') {
        $address=$eachrow[$import_opts['address_col']];
    } else {
        $address='';
    }
    $cells.=  wf_TableCell($address);
    
    if ($import_opts['realname_col']!='NONE') {
        $realname=$eachrow[$import_opts['realname_col']];
    } else {
        $realname='';
    }
    $cells.=  wf_TableCell($realname);
    
    if ($import_opts['contract_col']!='NONE') {
        $contract=$eachrow[$import_opts['contract_col']];
    } else {
        $contract='';
    }
    $cells.=  wf_TableCell($contract);
    
    if ($import_opts['ao_col']!='AO_1') {
        $ao=$eachrow[$import_opts['ao_col']];
    } else {
        $ao=1;
    }
    $cells.=  wf_TableCell($ao);
    
    if ($import_opts['down_col']!='DOWN_0') {
     $down=$eachrow[$import_opts['down_col']];   
    } else {
     $down=0;    
    }   
    $cells.=  wf_TableCell($down);
    
    if ($import_opts['passive_col']!='PASSIVE_0') {
        $passive=$eachrow[$import_opts['passive_col']];
    } else {
        $passive=0;
    }
    $cells.=  wf_TableCell($passive);
    
    $rows.=wf_TableRow($cells, 'row3');
    // filling userreg array
    $regdata[$login]['login']=$login;
    $regdata[$login]['password']=$password;
    $regdata[$login]['ip']=$ip;
    $regdata[$login]['mac']=$mac;
    $regdata[$login]['tariff']=$tariff;
    $regdata[$login]['cash']=$cash;
    $regdata[$login]['phone']=$phone;
    $regdata[$login]['mobile']=$mobile;
    $regdata[$login]['email']=$email;
    $regdata[$login]['credit']=$credit;
    $regdata[$login]['creditex']=$creditex;
    $regdata[$login]['address']=$address;
    $regdata[$login]['realname']=$realname;
    $regdata[$login]['contract']=$contract;
    $regdata[$login]['ao']=$ao;
    $regdata[$login]['down']=$down;
    $regdata[$login]['passive']=$passive;
    }
    
    $regdata_save=  serialize($regdata);
    $regdata_save=  base64_encode($regdata_save);
    zb_StorageSet('IMPORT_REGDATA', $regdata_save);
    
    $preparse=  wf_TableBody($rows, '100%', '0', '');
    show_window(__('All correct').'?',$preparse);
    
    $inputs=  wf_Link('?module=migration', 'No I want to try another import settings', false, 'ubButton');
    $inputs.= wf_Link('?module=migration&setpointers=true&goregister=ok', 'Yes proceed registeration of this users', false, 'ubButton');
    show_window('', $inputs);
}

if (!wf_CheckGet(array('setpointers'))) {
if(!wf_CheckPost(array('uploaduserbase'))) {
//show upload form
    show_window(__('User database import from text file'), web_MigrationUploadForm());
    
} else {
  //upload file and show preprocessing form
     $upload_done=  migrate_UploadFile();
    if ($upload_done) {
     $delimiter= $_POST['delimiter'];
     $encoding=  $_POST['encoding'];
      
     web_MigrationPreprocessing($upload_done, $delimiter, $encoding);
    }
    
}
} else {
    //some pointers already set, load raw data into database for processing
    if (wf_CheckPost(array('import_rawdata'))) {
        $import_rawdata=$_POST['import_rawdata'];
        zb_StorageSet('IMPORT_RAWDATA', $import_rawdata);
        $import_opts=array(
          'login_col'=>  $_POST['login_col'], 
          'password_col'=>  $_POST['password_col'], 
          'ip_col'=>  $_POST['ip_col'], 
          'mac_col'=>  $_POST['mac_col'], 
          'tariff_col'=>  $_POST['tariff_col'], 
          'cash_col'=>  $_POST['cash_col'], 
          'phone_col'=>  $_POST['phone_col'], 
          'mobile_col'=>  $_POST['mobile_col'], 
          'email_col'=>  $_POST['email_col'], 
          'credit_col'=>  $_POST['credit_col'],   
          'creditex_col'=>  $_POST['creditex_col'], 
          'address_col'=>  $_POST['address_col'],
          'realname_col'=>  $_POST['realname_col'], 
          'contract_col'=>  $_POST['contract_col'], 
          'ao_col'=>  $_POST['ao_col'],
          'down_col'=>  $_POST['down_col'],
          'passive_col'=>  $_POST['passive_col'],
          'netid'=> $_POST['networkselect'],
          'regtype'=>$_POST['regtype']  
        );
        
        $import_opts=  serialize($import_opts);
        $import_opts=  base64_encode($import_opts);
        zb_StorageSet('IMPORT_OPTS', $import_opts);
        
    } else {
        $import_rawdata= zb_StorageGet('IMPORT_RAWDATA');
        $import_opts=  zb_StorageGet('IMPORT_OPTS');
    }
    
    //last checks
    if (!wf_CheckGet(array('goregister'))) {
        web_MigrationPrepare($import_rawdata, $import_opts);
        
    } else {
        //register imported users
        $regdata_raw=  zb_StorageGet('IMPORT_REGDATA');
        $regdata=  unserialize(base64_decode($regdata_raw));
        $querybuff='';
        if (!empty($regdata)) {
            
            
    foreach ($regdata as $io=>$user) {
    debarr($user);
    //typical register of each user
    $login=vf($user['login']);
    $password=vf($user['password']);
    $ip=$user['ip'];
    $iopts=  unserialize(base64_decode($import_opts));
    $netid=$iopts['netid'];
    
    //Ubilling normal registration mode
    if ($iopts['regtype']=='UB') {
    $billing->createuser($login);
    log_register("StgUser REGISTER ".$login);
    $billing->setpassword($login,$password);
    log_register("StgUser PASSWORD ".$password);
    $billing->setip($login,$ip);
    log_register("StgUser IP ".$ip);
    multinet_add_host($netid, $ip);
    zb_UserCreateRealName($login, $user['realname']);
    zb_UserCreatePhone($login, $user['phone'], $user['mobile']);
    zb_UserCreateContract($login, $user['contract']);
    zb_UserCreateEmail($login, $user['email']);
    zb_UserCreateSpeedOverride($login, 0);
    multinet_change_mac($ip, $user['mac']);
    multinet_rebuild_all_handlers();
    $billing->setao($login,$user['ao']);
    $dstat=1;
    $billing->setdstat($login,$dstat);
    $billing->setdown($login,$user['down']);
    $billing->setpassive($login,$user['passive']);
    $billing->settariff($login,$user['tariff']);
    $billing->setcredit($login,$user['credit']);
    $billing->setcash($login,$user['cash']);
    zb_UserCreateNotes($login, $user['address']);
    } 
    
    
    if ($iopts['regtype']=='SQL') {
        $querybuff.="
            INSERT INTO `users` (
            `login`,
            `Password`,
            `Passive`,
            `Down`,
            `DisabledDetailStat`,
            `AlwaysOnline`,
            `Tariff`,
            `Address`,
            `Phone`,
            `Email`,
            `Note`,
            `RealName`,
            `StgGroup`,
            `Credit`,
            `TariffChange`,
            `Userdata0`,
            `Userdata1`,
            `Userdata2`,
            `Userdata3`,
            `Userdata4`,
            `Userdata5`,
            `Userdata6`,
            `Userdata7`,
            `Userdata8`,
            `Userdata9`,
            `CreditExpire`,
            `IP`,
            `D0`,
            `U0`,
            `D1`,
            `U1`,
            `D2`,
            `U2`,
            `D3`,
            `U3`,
            `D4`,
            `U4`,
            `D5`,
            `U5`, 
            `D6`, 
            `U6`,
            `D7`, 
            `U7`, 
            `D8`,
            `U8`,
            `D9`,
            `U9`,
            `Cash`,
            `FreeMb`,
            `LastCashAdd`,
            `LastCashAddTime`,
            `PassiveTime`,
            `LastActivityTime`,
            `NAS`)
            VALUES (
            '".$login."',
            '".$password."',
            '".$user['passive']."',
            '".$user['down']."',
            '1',
            '".$user['ao']."',
            '".$user['tariff']."',
            '',
            '',
            '',
            '',
            '',
            '',
            '".$user['credit']."',
            '', 
            '',
            '',
            '',
            '', 
            '', 
            '', 
            '', 
            '',
            '', 
            '', 
            '".$user['creditex']."',
            '".$ip."',
            '0',
            '0',
            '0',
            '0',
            '0', 
            '0',
            '0',
            '0', 
            '0',
            '0',
            '0',
            '0',
            '0', 
            '0',
            '0',
            '0', 
            '0', 
            '0', 
            '0', 
            '0', 
            '".$user['cash']."',
            '0',
            '0', 
            '0',
            '0', 
            '0',
            '');
            "."\n";
     //multinet 
     $querybuff.="INSERT INTO `nethosts` (`id`,`netid`,`ip`,`mac`,`option`)  VALUES ('', '".$netid."' ,'".$ip."', '".$user['mac']."', '');"."\n";
     //realname
     $querybuff.="INSERT INTO `realname` (`id`,`login`,`realname`)  VALUES (NULL, '".$login."','".$user['realname']."');"."\n";
     //phone & mobile
     $querybuff.="INSERT INTO `phones` (`id`,`login`,`phone`,`mobile`)  VALUES (NULL, '".$login."','".$user['phone']."','".$user['mobile']."');"."\n";
     //contract
     $querybuff.="INSERT INTO `contracts` (`id`,`login`,`contract`)  VALUES (NULL, '".$login."','".$user['contract']."');"."\n";
     //email
     $querybuff.="INSERT INTO `emails` (`id`,`login`,`email`)  VALUES (NULL, '".$login."','".$user['email']."');"."\n";
     //speedoverride
     $querybuff.="INSERT INTO `userspeeds` (`id`,`login`,`speed`)  VALUES (NULL, '".$login."','0');"."\n";
     //notes
     $querybuff.="INSERT INTO `notes` (`id`,`login`,`note`)  VALUES ('', '".$login."','".$user['address']."');"."\n";
        
    }
    
    
      }
      
      show_window(__('Generated SQL dump'),  wf_TextArea('sqldump', '', $querybuff, true, '120x20'));
            
            
        }
    }
    
    
    
    
}

    
} else {
    show_error(__('Access denied'));   
}

?>
