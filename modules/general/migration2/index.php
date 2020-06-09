<?php
/*
Modify by SoulRoot
09.12.2014
*/

if (cfr('ROOT')) {
set_time_limit (0);
/*START ADD*/

function translit($string) {
$converter = array(
        'а' => 'a',   'б' => 'b',   'в' => 'v',
        'г' => 'g',   'д' => 'd',   'е' => 'e',
        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
        'и' => 'i',   'й' => 'y',   'к' => 'k',
        'л' => 'l',   'м' => 'm',   'н' => 'n',
        'о' => 'o',   'п' => 'p',   'р' => 'r',
        'с' => 's',   'т' => 't',   'у' => 'u',
        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
        'ь' => '',  'ы' => 'y',   'ъ' => '',
        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
        
        'А' => 'A',   'Б' => 'B',   'В' => 'V',
        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
        'И' => 'I',   'Й' => 'Y',   'К' => 'K',
        'Л' => 'L',   'М' => 'M',   'Н' => 'N',
        'О' => 'O',   'П' => 'P',   'Р' => 'R',
        'С' => 'S',   'Т' => 'T',   'У' => 'U',
        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
        'Ь' => '',  'Ы' => 'Y',   'Ъ' => '',
        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
    );
    return strtr($string, $converter);
}
/*END ADD*/
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
        
        $cells=  wf_TableCell(__('Column number'));
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
        
        $login_arr          = $rowNumArr + array('RANDOM'=>__('Generate Random'));
        $password_arr       = $rowNumArr + array('RANDOM'=>__('Generate Random'));
        $ip_arr             = $rowNumArr;
        $mac_arr            = $rowNumArr + array('RANDOM'=>__('Generate Random'));
        $tariff_arr         = $rowNumArr;
        $cash_arr           = $rowNumArr;
        $credit_arr         = $rowNumArr + array('ZERO'=>__('Set to zero'));
        $creditex_arr       = $rowNumArr + array('NONE'=>__('Set to none'));
        /*Start ADD*/
        $city_arr           = $rowNumArr + array('NONE'=>__('Set to none'));
        $street_arr         = $rowNumArr + array('NONE'=>__('Set to none'));
        $build_arr          = $rowNumArr + array('NONE'=>__('Set to none'));
        $apt_entrance_arr   = $rowNumArr + array('NONE'=>__('Set to none'));
        $apt_floor_arr      = $rowNumArr + array('NONE'=>__('Set to none'));
        $apt_apt_arr        = $rowNumArr + array('NONE'=>__('Set to none'));
        /*End ADD*/
        $phone_arr          = $rowNumArr + array('NONE'=>__('Set to none'));
        $mobile_arr         = $rowNumArr + array('NONE'=>__('Set to none'));
        $email_arr          = $rowNumArr + array('NONE'=>__('Set to none'));
        $address_arr        = $rowNumArr + array('NONE'=>__('Set to none'));
        $realname_arr       = $rowNumArr + array('NONE'=>__('Set to none'));
        $contract_arr       = $rowNumArr + array('NONE'=>__('Set to none'));
        $contract_d_arr     = $rowNumArr + array('NONE'=>__('Set to none'));
        $ao_arr             = $rowNumArr + array('AO_1'=>__('AlwaysOnline=1'));
        $down_arr           = $rowNumArr + array('DOWN_0'=>__('Down=0'));
        $passive_arr        = $rowNumArr + array('PASSIVE_0'=>__('Passive=0'));
        $regtype_arr        = array('PHP'=>'Show PHP Script','SQL'=>'Show SQL dump','UB'=>'Ubilling live register');
        
        //data column setting form
        $inputs=   wf_Selector('login_col', $login_arr, __('User login'), '0', true);
        $inputs.=  wf_Selector('password_col', $password_arr, __('User password'), '1', true);
        $inputs.=  wf_Selector('ip_col', $ip_arr, __('User IP'), '2', true);
        $inputs.=  wf_Selector('mac_col', $mac_arr, __('User MAC'), '3', true);
        $inputs.=  wf_Selector('tariff_col', $tariff_arr, __('User tariff'), '4', true);
        $inputs.=  wf_Selector('cash_col', $cash_arr, __('User cash'), '5', true);
        $inputs.=  wf_Selector('credit_col', $credit_arr, __('User credit limit'), '6', true);
        $inputs.=  wf_Selector('creditex_col', $creditex_arr, __('User credit expire date'), '7', true);
	/*START ADD*/
        $inputs.=  wf_Selector('city_col', $city_arr, __('User city'), '8', true);
        $inputs.=  wf_Selector('street_col', $street_arr, __('User street'), '9', true);
        $inputs.=  wf_Selector('build_col', $build_arr, __('User build'), '10', true);
        $inputs.=  wf_Selector('apt_entrance_col', $apt_entrance_arr, __('User entrance'), '11', true);
        $inputs.=  wf_Selector('apt_floor_col', $apt_floor_arr, __('User floor'), '12', true);
        $inputs.=  wf_Selector('apt_apt_col', $apt_apt_arr, __('User apt'), '13', true);
        /*END ADD*/        
        $inputs.=  wf_Selector('phone_col', $phone_arr, __('User phone'), '14', true);
        $inputs.=  wf_Selector('mobile_col', $mobile_arr, __('User mobile'), '15', true);
        $inputs.=  wf_Selector('email_col', $email_arr, __('User email'), '16', true);
        $inputs.=  wf_Selector('address_col', $address_arr, __('User address'), '17', true);
        $inputs.=  wf_Selector('realname_col', $realname_arr, __('User realname'), '18', true);
        $inputs.=  wf_Selector('contract_col', $contract_arr, __('User contract'), '19', true);
        $inputs.=  wf_Selector('contract_d_col', $contract_d_arr, __('User contract date'), '20', true);
        $inputs.=  wf_Selector('ao_col', $ao_arr, __('User AlwaysOnline state'), '21', true);
        $inputs.=  wf_Selector('down_col', $down_arr, __('User Down state'), '22', true);
        $inputs.=  wf_Selector('passive_col', $passive_arr, __('User Passive state'), '23', true);
        $inputs.=  wf_Selector('regtype', $regtype_arr, __('User registration mode'), '24', true);
        $inputs.=  multinet_network_selector().__('Target network').  wf_delimiter();
        $inputs.=  wf_HiddenInput('import_rawdata', base64_encode(serialize($parsed_data)));
        $inputs.=  wf_Submit('Save this column pointers and continue import');

        $colform=  wf_Form("?module=migration2&setpointers=true", 'POST', $inputs, 'glamour');
        show_window(__('Select data columns and their values'),$colform);
        
    } else {
        show_error(__('Parsing error'));
    }
    
}

function web_MigrationPrepare($import_rawdata,$import_opts) {
    $import_rawdata = unserialize(base64_decode($import_rawdata));
    $import_opts = unserialize(base64_decode($import_opts));
    
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
    /*Start ADD*/
    $cells.=  wf_TableCell('[city]');
    $cells.=  wf_TableCell('[street]');
    $cells.=  wf_TableCell('[build]');
    $cells.=  wf_TableCell('[apt_entrance]');
    $cells.=  wf_TableCell('[apt_floor]');
    $cells.=  wf_TableCell('[apt_apt]');
    /*End ADD*/
    $cells.=  wf_TableCell('[address]');
    $cells.=  wf_TableCell('[realname]');
    $cells.=  wf_TableCell('[contract]');
    $cells.=  wf_TableCell('[contract_d]');
    $cells.=  wf_TableCell('[ao]');
    $cells.=  wf_TableCell('[down]');
    $cells.=  wf_TableCell('[passive]');

    $rows = wf_TableRow($cells, 'row1');
    
    $regdata=array();
    $i=0;
    
    foreach ($import_rawdata as $eachrow) {
        $i++;
        $cells = wf_TableCell($i);
        if ($import_opts['login_col'] != 'RANDOM') {
            $login = trim($eachrow[$import_opts['login_col']]);
        } else {
            $login = 'mi_'.zb_rand_string(8);
        }
        $cells.= wf_TableCell($login);

        if ($import_opts['password_col'] != 'RANDOM') {
            $password = $eachrow[$import_opts['password_col']];
        } else {
            $password = zb_rand_string(10);
        }
        $cells.= wf_TableCell($password);

        $ip = trim($eachrow[$import_opts['ip_col']]);
        $cells.= wf_TableCell($ip);

        if ($import_opts['mac_col'] != 'RANDOM') {
            $mac = trim($eachrow[$import_opts['mac_col']]);
        } else {
            $mac = '14:'.'88'.':'.rand(10,99).':'.rand(10,99).':'.rand(10,99).':'.rand(10,99);
        }
        $cells.= wf_TableCell($mac);

        $tariff = trim($eachrow[$import_opts['tariff_col']]);
        $cells.= wf_TableCell($tariff);

        $cash = trim($eachrow[$import_opts['cash_col']]);
        $cells.= wf_TableCell($cash);

        if ($import_opts['phone_col'] != 'NONE') {
            $phone = trim($eachrow[$import_opts['phone_col']]);
        } else {
            $phone = '';
        }
        $cells.= wf_TableCell($phone);

        if ($import_opts['mobile_col'] != 'NONE') {
            $mobile = trim($eachrow[$import_opts['mobile_col']]);
        } else {
            $mobile = '';
        }
        $cells.= wf_TableCell($mobile);

        if ($import_opts['email_col'] != 'NONE') {
            $email = trim($eachrow[$import_opts['email_col']]);
        } else {
            $email = '';
        }
        $cells.= wf_TableCell($email);

        if ($import_opts['credit_col'] != 'ZERO') {
            $credit = trim($eachrow[$import_opts['credit_col']]);
        } else {
            $credit = 0;
        }
        $cells.= wf_TableCell($credit);

        if ($import_opts['creditex_col'] != 'NONE') {
            $creditex = trim($eachrow[$import_opts['creditex_col']]);
        } else {
            $creditex = '0';
        }
        $cells.= wf_TableCell($creditex);

        /*START ADD*/
        if ($import_opts['city_col'] != 'NONE') {
            $city = trim($eachrow[$import_opts['city_col']]);
        } else {
            $city = '';
        }
        $cells.= wf_TableCell($city);

        if ($import_opts['street_col'] != 'NONE') {
            $street = trim($eachrow[$import_opts['street_col']]);
        } else {
            $street = '';
        }
        $cells.= wf_TableCell($street);

        if ($import_opts['build_col'] != 'NONE') {
            $build = trim($eachrow[$import_opts['build_col']]);
        } else {
            $build = '';
        }
        $cells.= wf_TableCell($build);

        if ($import_opts['apt_entrance_col'] != 'NONE') {
            $apt_entrance = trim($eachrow[$import_opts['apt_entrance_col']]);
        } else {
            $apt_entrance = '';
        }
        $cells.= wf_TableCell($apt_entrance);

        if ($import_opts['apt_floor_col'] != 'NONE') {
            $apt_floor = trim($eachrow[$import_opts['apt_floor_col']]);
        } else {
            $apt_floor = '';
        }
        $cells.= wf_TableCell($apt_floor);

        if ($import_opts['apt_apt_col'] != 'NONE') {
            $apt_apt = trim($eachrow[$import_opts['apt_apt_col']]);
        } else {
            $apt_apt = '';
        }
        $cells.= wf_TableCell($apt_apt);
        /*END ADD*/

        if ($import_opts['address_col'] != 'NONE') {
            $address = trim($eachrow[$import_opts['address_col']]);
        } else {
            $address = '';
        }
        $cells.= wf_TableCell($address);

        if ($import_opts['realname_col'] != 'NONE') {
            $realname = trim($eachrow[$import_opts['realname_col']]);
        } else {
            $realname = '';
        }
        $cells.= wf_TableCell($realname);

        if ($import_opts['contract_col'] != 'NONE') {
            $contract = trim($eachrow[$import_opts['contract_col']]);
        } else {
            $contract = '';
        }
        $cells.= wf_TableCell($contract);

        if ($import_opts['contract_d_col'] != 'NONE') {
            $contract_d = trim($eachrow[$import_opts['contract_d_col']]);
        } else {
            $contract_d = '';
        }
        $cells.= wf_TableCell($contract_d);

        if ($import_opts['ao_col'] != 'AO_1') {
            $ao = trim($eachrow[$import_opts['ao_col']]);
        } else {
            $ao = 1;
        }
        $cells.= wf_TableCell($ao);

        if ($import_opts['down_col'] != 'DOWN_0') {
            $down = trim($eachrow[$import_opts['down_col']]);
        } else {
            $down = 0;
        }
        $cells.= wf_TableCell($down);

        if ($import_opts['passive_col'] != 'PASSIVE_0') {
            $passive = trim($eachrow[$import_opts['passive_col']]);
        } else {
            $passive = 0;
        }

        $cells.= wf_TableCell($passive);
        $rows.= wf_TableRow($cells, 'row3');

        // filling userreg array
        $regdata[$login]['login'] = $login;
        $regdata[$login]['password'] = $password;
        $regdata[$login]['ip'] = $ip;
        $regdata[$login]['mac'] = $mac;
        $regdata[$login]['tariff'] = $tariff;
        $regdata[$login]['cash'] = $cash;
        $regdata[$login]['phone'] = $phone;
        $regdata[$login]['mobile'] = $mobile;
        $regdata[$login]['email'] = $email;
        $regdata[$login]['credit'] = $credit;
        $regdata[$login]['creditex'] = $creditex;
        /*Start ADD*/
        $regdata[$login]['city'] = $city;
        $regdata[$login]['street'] = $street;
        $regdata[$login]['build'] = $build;
        $regdata[$login]['apt_entrance'] = $apt_entrance;
        $regdata[$login]['apt_floor'] = $apt_floor;
        $regdata[$login]['apt_apt'] = $apt_apt;
        /*End ADD*/
        $regdata[$login]['address'] = $address;
        $regdata[$login]['realname'] = $realname;
        $regdata[$login]['contract'] = $contract;
        $regdata[$login]['contract_d'] = $contract_d;
        $regdata[$login]['ao'] = $ao;
        $regdata[$login]['down'] = $down;
        $regdata[$login]['passive'] = $passive;
    }
    
    $regdata_save = serialize($regdata);
    $regdata_save = base64_encode($regdata_save);
    zb_StorageSet('IMPORT_REGDATA', $regdata_save);
    
    $preparse = wf_TableBody($rows, '100%', '0', '');
    show_window(__('All correct').'?',$preparse);
    
    $inputs = wf_Link('?module=migration2', 'No I want to try another import settings', false, 'ubButton');
    $inputs.= wf_Link('?module=migration2&setpointers=true&goregister=ok', 'Yes, proceed registeration of this users', false, 'ubButton');
    $inputs.= wf_delimiter();
    $inputs.= wf_tag('h3', false, '', 'style="color: red; background-color: #F5F5DC"');
    $inputs.= 'Creating occupancy(cities, streets, buildings, addresses, etc) for new users avialable ONLY for "Ubilling live register" user registration mode. ';
    $inputs.= 'Nevertheless this feature has to be used with GREAT CARE and for your OWN RISK';
    $inputs.= wf_tag('h3', true);
    $inputs.= wf_Link('?module=migration2&setpointers=true&goregister=ok&create_accupancy=yes',
                     'Yes, proceed registeration of this users and create occupancy if not exists.', false, 'ubButton');

    show_window('', $inputs);
}

if (!wf_CheckGet(array('setpointers'))) {
    if(!wf_CheckPost(array('uploaduserbase'))) {
    //show upload form
        show_window(__('User database import from text file'), web_MigrationUploadForm());

    } else {
    //upload file and show preprocessing form
            $upload_done = migrate_UploadFile();
        if ($upload_done) {
            $delimiter = $_POST['delimiter'];
            $encoding = $_POST['encoding'];

         web_MigrationPreprocessing($upload_done, $delimiter, $encoding);
        }
    }
} else {
    //some pointers already set, load raw data into database for processing
    if (wf_CheckPost(array('import_rawdata'))) {
        $import_rawdata = $_POST['import_rawdata'];
        zb_StorageSet('IMPORT_RAWDATA', $import_rawdata);
        $import_opts = array(
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
          /*START ADD*/
          'city_col'=>  $_POST['city_col'], 
          'street_col'=>  $_POST['street_col'], 
          'build_col'=>  $_POST['build_col'], 
          'apt_entrance_col'=>  $_POST['apt_entrance_col'], 
          'apt_floor_col'=>  $_POST['apt_floor_col'], 
          'apt_apt_col'=>  $_POST['apt_apt_col'], 
          /*End ADD*/
          'address_col'=>  $_POST['address_col'],
          'realname_col'=>  $_POST['realname_col'], 
          'contract_col'=>  $_POST['contract_col'],
          'contract_d_col'=>  $_POST['contract_d_col'],
          'ao_col'=>  $_POST['ao_col'],
          'down_col'=>  $_POST['down_col'],
          'passive_col'=>  $_POST['passive_col'],
          'netid'=> $_POST['networkselect'],
          'regtype'=>$_POST['regtype']  
        );
        
        $import_opts = serialize($import_opts);
        $import_opts = base64_encode($import_opts);
        zb_StorageSet('IMPORT_OPTS', $import_opts);
    } else {
        $import_rawdata = zb_StorageGet('IMPORT_RAWDATA');
        $import_opts = zb_StorageGet('IMPORT_OPTS');
    }
    
    //last checks
    if (!wf_CheckGet(array('goregister'))) {
        web_MigrationPrepare($import_rawdata, $import_opts);
        
    } else {
        $CreateOccupancy = ( wf_CheckGet(array('create_accupancy')) ) ? true : false;

        //register imported users
        $regdata_raw = zb_StorageGet('IMPORT_REGDATA');
        $regdata = unserialize(base64_decode($regdata_raw));
        $querybuff = '';

        if (!empty($regdata)) {
            $iopts = unserialize(base64_decode($import_opts));
            $RegAddrs = array();

            if ($CreateOccupancy && $iopts['regtype'] == 'UB') {
                // getting unique cities and streets names with buildings
                foreach ($regdata as $io => $user) {
                    $tmpRegCity = $user['city'];
                    $tmpRegStreet = $user['street'];
                    $tmpRegBuilding = $user['build'];

                    if (!array_key_exists($tmpRegCity, $RegAddrs)) {
                        $RegAddrs[$tmpRegCity] = array();
                    }

                    if (!array_key_exists($tmpRegStreet, $RegAddrs[$tmpRegCity])) {
                        $RegAddrs[$tmpRegCity][$tmpRegStreet] = array();
                    }

                    if (!in_array($tmpRegBuilding, $RegAddrs[$tmpRegCity][$tmpRegStreet])) {
                        $RegAddrs[$tmpRegCity][$tmpRegStreet][$tmpRegBuilding] = array('id' => '');
                    }
                }

                if ( !empty($RegAddrs) ) {
                    foreach ($RegAddrs as $tCity => $CityData) {
                        $CityWasJustCreated = false;

                        $tmpQuery = "SELECT  * FROM `city` WHERE LOWER(`cityname`) = '" . mb_strtolower($tCity) . "';";
                        $tmpResult = simple_queryall($tmpQuery);

                        if (!empty($tmpResult)) {
                            $tCityID = $tmpResult[0]['id'];
                        } else {
                            zb_AddressCreateCity($tCity, '');
                            $tCityID = simple_get_lastid('city');
                            $CityWasJustCreated = true;
                        }

                        foreach ($CityData as $tStreet => $tBuilds) {
                            $StreetWasJustCreated = false;
                            $NeedCreateStreet = true;

                            // if city was not just created - let's check, maybe there is such street in DB already
                            if (!$CityWasJustCreated) {
                                $tmpQuery = "SELECT * FROM `street` WHERE `cityid` = '" . $tCityID . "' AND LOWER(`streetname`) = '" . mb_strtolower($tStreet) . "';";
                                $tmpResult = simple_queryall($tmpQuery);

                                if (!empty($tmpResult)) {
                                    $tStreetID = $tmpResult[0]['id'];
                                    $NeedCreateStreet = false;
                                }
                            }

                            if ($NeedCreateStreet) {
                                zb_AddressCreateStreet($tCityID, $tStreet, '');
                                $tStreetID = simple_get_lastid('street');
                                $StreetWasJustCreated = true;
                            }

                            foreach ($tBuilds as $tBuild => $tID) {
                                $NeedCreateBuilding = true;

                                // if street was not just created - let's check, maybe there is such building in DB already
                                if (!$StreetWasJustCreated) {
                                    $tmpQuery = "SELECT * FROM `build` WHERE `streetid` = '" . $tStreetID . "' AND LOWER(`buildnum`) = '" . mb_strtolower($tBuild) . "';";
                                    $tmpResult = simple_queryall($tmpQuery);

                                    if (!empty($tmpResult)) {
                                        $tBuildingID = $tmpResult[0]['id'];
                                        $NeedCreateBuilding = false;
                                    }
                                }

                                if ($NeedCreateBuilding) {
                                    zb_AddressCreateBuild($tStreetID, $tBuild);
                                    $tBuildingID = simple_get_lastid('build');
                                }

                                $RegAddrs[$tCity][$tStreet][$tBuild]['id'] = $tBuildingID;
                            }
                        }
                    }
                }
            }



    /*START ADD*/
    $buffer="";
    $buffer.='function InArray(&$Array,$Value)
{
    $ret=false;
    foreach ($Array as $key => $value)
    {
        if ($Value == $value)
        {
            $ret=true;
            break;
        }
    }
    return $ret;
}
'."\n";
    /*END ADD*/

    foreach ($regdata as $io => $user) {
        debarr($user);
        //typical register of each user
        $login=vf($user['login']);
        $password=vf($user['password']);
        $ip=$user['ip'];
        $iopts=  unserialize(base64_decode($import_opts));
        $netid=$iopts['netid'];

    /*START ADD*/
    if ($iopts['regtype']=='PHP') {
	$SQL['city_id']="SELECT `id` FROM `city` WHERE `cityname`= '".$user['city']."'";
	$SQL['city_add']="INSERT INTO  `city` (`id` ,`cityname` ,`cityalias`) VALUES (NULL ,  '".$user['city']."',  '".translit($user['city'])."');";
	$buffer.='$city_id=0;'."\n";
	$buffer.='$city_id_count=mysql_num_rows( mysql_query( "'.$SQL['city_id'].'" ) );'."\n";
	$buffer.='if ($city_id_count==0)
{
    $result = mysql_query("'.$SQL['city_add'].'");
    if (!$result) { echo \'Error add city: \' . mysql_error();}
}
$row = mysql_fetch_row( mysql_query( "'.$SQL['city_id'].'" ) );
$city_id=$row[0];
'."\n";

	
	$SQL['street_id']="SELECT `id` FROM `street` WHERE `cityid` = '\".\$city_id.\"' AND `streetname` = '".$user['street']."'";
	$SQL['street_add']="INSERT INTO `street` (`id`, `cityid`, `streetname`, `streetalias`) VALUES (NULL, '\".\$city_id.\"', '".$user['street']."', '".translit($user['street'])."');";
	$buffer.='$street_id=0;'."\n";
	$buffer.='$street_id_count=mysql_num_rows( mysql_query( "'.$SQL['street_id'].'" ) );'."\n";
	$buffer.='if ($street_id_count==0)
{
    $result = mysql_query("'.$SQL['street_add'].'");
    if (!$result) { echo \'Error add street: \' . mysql_error();}
}
$row = mysql_fetch_row( mysql_query( "'.$SQL['street_id'].'" ) );
$street_id=$row[0];
';

	$SQL['build_id']="SELECT `id` FROM `build` WHERE `streetid`= '\".\$street_id.\"' and `buildnum` = '".$user['build']."'";
	$SQL['build_add']="INSERT INTO `build` (`id`, `streetid`, `buildnum`, `geo`) VALUES (NULL, '\".\$street_id.\"', '".$user['build']."', NULL);";
	$buffer.='$build_id=0;'."\n";
	$buffer.='$build_id_count=mysql_num_rows( mysql_query( "'.$SQL['build_id'].'" ) );'."\n";
	$buffer.='if ($build_id_count==0)
{
    $result = mysql_query("'.$SQL['build_add'].'");
    if (!$result) { echo \'Error add build: \' . mysql_error();}
}
$row = mysql_fetch_row( mysql_query( "'.$SQL['build_id'].'" ) );
$build_id=$row[0];'."\n\n\n";

	$SQL['apt_id']="SELECT `id` FROM `apt` WHERE `buildid` = '\".\$build_id.\"' AND `entrance` = '".$user['apt_entrance']."' AND `floor` = '".$user['apt_floor']."' AND `apt` = '".$user['apt_apt']."'";
	$SQL['apt_add']="INSERT INTO `apt` (`id`, `buildid`, `entrance`, `floor`, `apt`) VALUES (NULL, '\".\$build_id.\"', '".$user['apt_entrance']."', '".$user['apt_floor']."', '".$user['apt_apt']."');";
	$buffer.='$apt_id=0;'."\n";
	$buffer.='$apt_id_count=mysql_num_rows( mysql_query( "'.$SQL['apt_id'].'" ) );'."\n";
	$buffer.='if ($apt_id_count==0)
{
    $result = mysql_query("'.$SQL['apt_add'].'");
    if (!$result) { echo \'Error add apt: \' . mysql_error();}
}
$row = mysql_fetch_row( mysql_query( "'.$SQL['apt_id'].'" ) );
$apt_id=$row[0];'."\n";


	$SQL['address_id']="SELECT `id` FROM `address` WHERE `login`= '".$login."'";
	$SQL['address_add']="INSERT INTO `address` (`id`, `login`, `aptid`) VALUES (NULL, '".$login."', '\".\$apt_id.\"');";
	$buffer.='$address_id=0;'."\n";
	$buffer.='$address_id_count=mysql_num_rows( mysql_query( "'.$SQL['address_id'].'" ) );'."\n";
	$buffer.='if ($address_id_count==0)
{
    $result = mysql_query("'.$SQL['address_add'].'");
    if (!$result) { echo \'Error add address: \' . mysql_error();}
}
$row = mysql_fetch_row( mysql_query( "'.$SQL['address_id'].'" ) );
$address_id=$row[0];'."\n";

	$SQL['users']="INSERT INTO `users` (`login`,`Password`,`Passive`,`Down`,`DisabledDetailStat`,`AlwaysOnline`,`Tariff`,`Address`,`Phone`,`Email`,`Note`,`RealName`,`StgGroup`,`Credit`,`TariffChange`,`Userdata0`,`Userdata1`,`Userdata2`,`Userdata3`,`Userdata4`,`Userdata5`,`Userdata6`,`Userdata7`,`Userdata8`,`Userdata9`,`CreditExpire`,`IP`,`D0`,`U0`,`D1`,`U1`,`D2`,`U2`,`D3`,`U3`,`D4`,`U4`,`D5`,`U5`, `D6`, `U6`,`D7`, `U7`, `D8`,`U8`,`D9`,`U9`,`Cash`,`FreeMb`,`LastCashAdd`,`LastCashAddTime`,`PassiveTime`,`LastActivityTime`,`NAS`)
VALUES ('".$login."','".$password."','".$user['passive']."','".$user['down']."','1','".$user['ao']."','".$user['tariff']."','\".\$address_id.\"','','','','','','".$user['credit']."','', '','','','', '', '', '', '','', '', '".$user['creditex']."','".$ip."','0','0','0','0','0', '0','0','0', '0','0','0','0','0', '0','0','0', '0', '0', '0', '0', '".$user['cash']."','0','0', '0','0', '0','');";
	$SQL['nethosts']="INSERT INTO `nethosts` (`id`,`netid`,`ip`,`mac`,`option`)  VALUES ('', '".$netid."' ,'".$ip."', '".$user['mac']."', '');";
	$SQL['realname']="INSERT INTO `realname` (`id`,`login`,`realname`)  VALUES (NULL, '".$login."','".$user['realname']."');";
	$SQL['phones']="INSERT INTO `phones` (`id`,`login`,`phone`,`mobile`)  VALUES (NULL, '".$login."','".$user['phone']."','".$user['mobile']."');";
	$SQL['contracts']="INSERT INTO `contracts` (`id`,`login`,`contract`)  VALUES (NULL, '".$login."','".$user['contract']."');";
    $SQL['contracts_d']="INSERT INTO `contractdates` (`id`,`contract`,`date`) VALUES (NULL , '" . $user['contract'] . "', '" . $user['contract_d'] . "');";
	$SQL['emails']="INSERT INTO `emails` (`id`,`login`,`email`)  VALUES (NULL, '".$login."','".$user['email']."');";
	$SQL['userspeeds']="INSERT INTO `userspeeds` (`id`,`login`,`speed`)  VALUES (NULL, '".$login."','0');";
	$SQL['notes']="INSERT INTO `notes` (`id`,`login`,`note`)  VALUES ('', '".$login."','".$user['address']."');";
	
	$buffer.='$result = mysql_query("'.$SQL['users'].'");'."\n";
	$buffer.='if (!$result) {
    echo \'Query[users] error with '.$login.': \' . mysql_error();
    exit;
}';
	$buffer.="\n";

	
	$buffer.='$result = mysql_query("'.$SQL['nethosts'].'");'."\n";
	$buffer.='if (!$result) {
    echo \'Query[nethosts] error with '.$login.': \' . mysql_error();
    exit;
}';
	$buffer.="\n";
	
	$buffer.='$result = mysql_query("'.$SQL['realname'].'");'."\n";
	$buffer.='if (!$result) {
    echo \'Query[realname] error with '.$login.': \' . mysql_error();
    exit;
}';
	$buffer.="\n";
	
	$buffer.='$result = mysql_query("'.$SQL['phones'].'");'."\n";
	$buffer.='if (!$result) {
    echo \'Query[phones] error with '.$login.': \' . mysql_error();
    exit;
}';
	$buffer.="\n";
	
	$buffer.='$result = mysql_query("'.$SQL['contracts'].'");'."\n";
	$buffer.='if (!$result) {
    echo \'Query[contracts] error with '.$login.': \' . mysql_error();
    exit;
}';
	$buffer.="\n";

    $buffer.='$result = mysql_query("'.$SQL['contracts_d'].'");'."\n";
    $buffer.='if (!$result) {
    echo \'Query[contracts_d] error with '.$login.': \' . mysql_error();
    exit;
}';
    $buffer.="\n";

	$buffer.='$result = mysql_query("'.$SQL['emails'].'");'."\n";
	$buffer.='if (!$result) {
    echo \'Query[emails] error with '.$login.': \' . mysql_error();
    exit;
}';
	$buffer.="\n";
	
	$buffer.='$result = mysql_query("'.$SQL['userspeeds'].'");'."\n";
	$buffer.='if (!$result) {
    echo \'Query[userspeeds] error with '.$login.': \' . mysql_error();
    exit;
}';
	$buffer.="\n";
	
	$buffer.='$result = mysql_query("'.$SQL['notes'].'");'."\n";
	$buffer.='if (!$result) {
    echo \'Query[notes] error with '.$login.': \' . mysql_error();
    exit;
}';
	$buffer.="\n";
    }
    /*END ADD*/

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
            zb_UserContractDateCreate($user['contract'], date('Y-m-d', strtotime($user['contract_d'])));
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

            $NoOccupancyCreated = true;

            if ($CreateOccupancy) {
                $tmpRegCity         = $user['city'];
                $tmpRegStreet       = $user['street'];
                $tmpRegBuilding     = $user['build'];
                $tmpRegAptEntrance  = $user['apt_entrance'];
                $tmpRegAptFloor     = $user['apt_floor'];
                $tmpRegApt          = $user['apt_apt'];
                $tBuildingID        = '';

                // try to get build ID from $RegAddrs array which was processed earlier
                // and create araptment and address for the user
                if ( !empty($RegAddrs) ) {
                    if (isset($RegAddrs[$tmpRegCity][$tmpRegStreet][$tmpRegBuilding]['id'])) {
                        $tBuildingID = $RegAddrs[$tmpRegCity][$tmpRegStreet][$tmpRegBuilding]['id'];

                        zb_AddressCreateApartment($tBuildingID, $tmpRegAptEntrance, $tmpRegAptFloor, $tmpRegApt);
                        $tAptID = simple_get_lastid('apt');
                        zb_AddressCreateAddress($login, $tAptID);
                        $NoOccupancyCreated = false;
                    }
                }
            }

            if ($NoOccupancyCreated) { zb_UserCreateNotes($login, $user['address']); }
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
      /*START ADD*/
      if ($iopts['regtype']=='PHP')
      {
    	show_window(__('Generated PHP script'),  wf_TextArea('sqldump', '', $buffer, true, '170x60'));
      }
      else show_window(__('Generated SQL dump'),  wf_TextArea('sqldump', '', $querybuff, true, '120x20'));
      /*END ADD*/
      //show_window(__('Generated SQL dump'),  wf_TextArea('sqldump', '', $querybuff, true, '120x20'));
            
            
        }
    }
    
    
    
    
}

    
} else {
    show_error(__('Access denied'));   
}

?>