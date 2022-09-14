<?php

if (cfr('ROOT')) {
    set_time_limit (0);

    function web_MigrationUploadFormExten() {
        $delimiters = array(
            ';'=>';',
            '|'=>'|',
            ','=>','
        );

        $encodings = array(
            'utf-8'=>'utf-8',
            'windows-1251'=>'windows-1251',
            'koi8-u'=>'koi8-u',
            'cp866'=>'cp866'
        );

        $uploadinputs = wf_HiddenInput('uploaduserbase','true');
        $uploadinputs.= __('Upload userbase').' <input id="fileselector" type="file" name="uluserbase" size="10" /><br>';
        $uploadinputs.= wf_Selector('delimiter', $delimiters, __('Delimiter'), '', true);
        $uploadinputs.= wf_Selector('encoding', $encodings, __('Encoding'), '', true);
        $uploadinputs.= wf_Submit('Upload');
        $uploadform = bs_UploadFormBody('', 'POST', $uploadinputs, 'glamour');

        return ($uploadform);
    }


     function migrate_UploadFileExten() {
        //путь сохранения
        $uploaddir = 'exports/';
        //белый лист расширений
        $allowedExtensions = array("txt", "csv");
        //по умолчанию надеемся на худшее
        $result = false;

        //проверяем точно ли текстовку нам подсовывают
        foreach ($_FILES as $file) {
            if ($file['tmp_name'] > '') {
                if (@!in_array(end(explode(".", strtolower($file['name']))), $allowedExtensions)) {
                    $errormessage = 'Wrong file type';
                    die($errormessage);
                }
            }
        }

        $filename = vf($_FILES['uluserbase']['name']);
        $uploadfile = $uploaddir . $filename;

        if (move_uploaded_file($_FILES['uluserbase']['tmp_name'], $uploadfile)) {
           $result = $filename;
        }

        return ($result);
    }


    function web_MigrationPreprocessingExten($filename, $delimiter, $encoding) {
        $path = 'exports/';
        $raw_data = file_get_contents($path . $filename);
        $parsed_data = array();

        if ($encoding != 'utf-8') {
            $raw_data = iconv($encoding, 'utf-8', $raw_data);
        }
        $raw_data = explodeRows($raw_data);

        if (!empty($raw_data)) {
            foreach ($raw_data as $eachrow) {
                if (!empty($eachrow)) {
                    $parsed_data[] = explode($delimiter, $eachrow);
                }
            }
        }

        if (sizeof($parsed_data) > 1) {
            $col_count = sizeof($parsed_data[0]);

            $cells = wf_TableCell(__('Column number'));
            $cells.= wf_TableCell(__('Column content'));
            $rows =  wf_TableRow($cells, 'row1');

            foreach ($parsed_data[0] as $col_num => $col_data) {
                $cells = wf_TableCell($col_num);
                $cells.= wf_TableCell($col_data);
                $rows.=  wf_TableRow($cells, 'row3');
            }

            $first_row = wf_TableBody($rows, '100%', '0', '');
            show_window(__('Found count of data columns'), $col_count);
            show_window(__('First of imported data rows'), $first_row);

            //construct of data processing form
            $rowNumArr = array();
            for ($i = 0; $i < $col_count; $i++) {
                $rowNumArr[$i] = $i;
            }

            $login_arr          = $rowNumArr + array('RANDOM'=>__('Generate Random'));
            $password_arr       = $rowNumArr + array('RANDOM'=>__('Generate Random'));
            $ip_arr             = $rowNumArr;
            $mac_arr            = $rowNumArr + array('RANDOM'=>__('Generate Random'));
            $tariff_arr         = $rowNumArr;
            $cash_arr           = $rowNumArr;
            $credit_arr         = $rowNumArr + array('ZERO'=>__('Set to zero'));
            $creditex_arr       = $rowNumArr + array('NONE'=>__('Set to none'));

            $city_arr           = $rowNumArr + array('NONE'=>__('Set to none'));
            $street_arr         = $rowNumArr + array('NONE'=>__('Set to none'));
            $build_arr          = $rowNumArr + array('NONE'=>__('Set to none'));
            $apt_entrance_arr   = $rowNumArr + array('NONE'=>__('Set to none'));
            $apt_floor_arr      = $rowNumArr + array('NONE'=>__('Set to none'));
            $apt_apt_arr        = $rowNumArr + array('NONE'=>__('Set to none'));

            $phone_arr          = $rowNumArr + array('NONE'=>__('Set to none'));
            $mobile_arr         = $rowNumArr + array('NONE'=>__('Set to none'));
            $email_arr          = $rowNumArr + array('NONE'=>__('Set to none'));
            $address_arr        = $rowNumArr + array('NONE'=>__('Set to none'));
            $realname_arr       = $rowNumArr + array('NONE'=>__('Set to none'));
            $contract_arr       = $rowNumArr + array('NONE'=>__('Set to none'));
            $contract_d_arr     = $rowNumArr + array('NONE'=>__('Set to none'));

            $pasp_num_arr       = $rowNumArr + array('NONE'=>__('Set to none'));
            $pasp_date_arr      = $rowNumArr + array('NONE'=>__('Set to none'));
            $pasp_granted_arr   = $rowNumArr + array('NONE'=>__('Set to none'));
            $usr_comments_arr   = $rowNumArr + array('NONE'=>__('Set to none'));

            $tags_ids_arr       = $rowNumArr + array('NONE'=>__('Set to none'));
            $tags_names_arr     = $rowNumArr + array('NONE'=>__('Set to none'));
            $nas_ip_arr         = $rowNumArr + array('NONE'=>__('Set to none'));

            $ao_arr             = $rowNumArr + array('AO_1'=>__('AlwaysOnline=1'));
            $down_arr           = $rowNumArr + array('DOWN_0'=>__('Down=0'));
            $passive_arr        = $rowNumArr + array('PASSIVE_0'=>__('Passive=0'));
            $regtype_arr        = array('UB'=>'Ubilling live register');

            //data column setting form
            $inputs = wf_Selector('login_col', $login_arr, __('User login'), '0', true);
            $inputs.= wf_Selector('password_col', $password_arr, __('User password'), '1', true);
            $inputs.= wf_Selector('ip_col', $ip_arr, __('User IP (will get first free IP from chosen subnet/NAS subnet if the field will be actually empty)'), '2', true);
            $inputs.= wf_Selector('mac_col', $mac_arr, __('User MAC'), '3', true);
            $inputs.= wf_Selector('tariff_col', $tariff_arr, __('User tariff'), '4', true);
            $inputs.= wf_Selector('cash_col', $cash_arr, __('User cash'), '5', true);
            $inputs.= wf_Selector('credit_col', $credit_arr, __('User credit limit'), '6', true);
            $inputs.= wf_Selector('creditex_col', $creditex_arr, __('User credit expire date'), '7', true);

            $inputs.= wf_Selector('city_col', $city_arr, __('User city'), '8', true);
            $inputs.= wf_Selector('street_col', $street_arr, __('User street'), '9', true);
            $inputs.= wf_Selector('build_col', $build_arr, __('User build'), '10', true);
            $inputs.= wf_Selector('apt_entrance_col', $apt_entrance_arr, __('User entrance'), '11', true);
            $inputs.= wf_Selector('apt_floor_col', $apt_floor_arr, __('User floor'), '12', true);
            $inputs.= wf_Selector('apt_apt_col', $apt_apt_arr, __('User apt'), '13', true);

            $inputs.= wf_Selector('phone_col', $phone_arr, __('User phone'), '14', true);
            $inputs.= wf_Selector('mobile_col', $mobile_arr, __('User mobile'), '15', true);
            $inputs.= wf_Selector('email_col', $email_arr, __('User email'), '16', true);
            $inputs.= wf_Selector('address_col', $address_arr, __('User address (will be added to user notes if no occupancy created for user)'), '17', true);
            $inputs.= wf_Selector('realname_col', $realname_arr, __('User realname'), '18', true);
            $inputs.= wf_Selector('contract_col', $contract_arr, __('User contract'), '19', true);
            $inputs.= wf_Selector('contract_d_col', $contract_d_arr, __('User contract date'), '20', true);

            $inputs.= wf_Selector('pasp_num_col', $pasp_num_arr, __('User passport number'), '21', true);
            $inputs.= wf_Selector('pasp_date_col', $pasp_date_arr, __('User passport date'), '22', true);
            $inputs.= wf_Selector('pasp_granted_col', $pasp_granted_arr, __('User passport granted by'), '23', true);

            $inputs.= wf_Selector('usr_comments_col', $usr_comments_arr, __('User comments'), '24', true);
            $inputs.= wf_Selector('tags_ids_col', $tags_ids_arr, __('Tags IDs to assign with user delimited with ","'), '25', true);
            $inputs.= wf_Selector('tags_names_col', $tags_names_arr, __('Tags names to assign with user delimited with ","'), '26', true);

            $inputs.= wf_Selector('ao_col', $ao_arr, __('User AlwaysOnline state'), '27', true);
            $inputs.= wf_Selector('down_col', $down_arr, __('User Down state'), '28', true);
            $inputs.= wf_Selector('passive_col', $passive_arr, __('User Passive state'), '29', true);
            $inputs.= wf_Selector('nas_ip_col', $nas_ip_arr, __('NAS IP address - for NASes with UNIQUE IPs ONLY. Use NAS IP to get user\'s subnet ID and fallback to chosen in a field below, if NAS IP is empty'), '31', true);
            $inputs.= multinet_network_selector() . __('Target network') . wf_delimiter();

            $inputs.= wf_Selector('regtype', $regtype_arr, __('User registration mode'), '30', true);
            $inputs.= wf_delimiter(0);
            $inputs.= wf_TextInput('skiprowscount', __('Skip specified numbers of rows from the beginning of .CSV/.TXT file (if those rows are empty, or contain fields captions, or whatever)'), 0, true, '4', 'digits');
            $inputs.= wf_delimiter(0);
            $inputs.= wf_Plate('Please, split your import CSV data to a smaller chunks (by settlement, street, NAS, targeted network or whatever) to prevent import issues and reduce import time',
                               '', '', 'glamour', 'color: red;');
            $inputs.= wf_delimiter(2);

            $inputs.= wf_HiddenInput('import_rawdata', base64_encode(serialize($parsed_data)));
            $inputs.= wf_Submit('Save this column pointers and continue import');

            $colform = wf_Form("?module=migration2_exten&setpointers=true", 'POST', $inputs, 'glamour');
            show_window(__('Select data columns and their values'), $colform);
        } else {
            show_error(__('Parsing error'));
        }
    }


    function web_MigrationPrepareExten($import_rawdata, $import_opts, $skiprowscnt) {
        $import_rawdata = unserialize(base64_decode($import_rawdata));
        $import_opts    = unserialize(base64_decode($import_opts));

        $cells = wf_TableCell('#');
        $cells.= wf_TableCell('[login]');
        $cells.= wf_TableCell('[password]');
        $cells.= wf_TableCell('[ip]');
        $cells.= wf_TableCell('[mac]');
        $cells.= wf_TableCell('[tariff]');
        $cells.= wf_TableCell('[cash]');
        $cells.= wf_TableCell('[phone]');
        $cells.= wf_TableCell('[mobile]');
        $cells.= wf_TableCell('[email]');
        $cells.= wf_TableCell('[credit]');
        $cells.= wf_TableCell('[creditex]');

        $cells.= wf_TableCell('[city]');
        $cells.= wf_TableCell('[street]');
        $cells.= wf_TableCell('[build]');
        $cells.= wf_TableCell('[apt_entrance]');
        $cells.= wf_TableCell('[apt_floor]');
        $cells.= wf_TableCell('[apt_apt]');

        $cells.= wf_TableCell('[address]');
        $cells.= wf_TableCell('[realname]');
        $cells.= wf_TableCell('[contract]');
        $cells.= wf_TableCell('[contract_d]');

        $cells.= wf_TableCell('[pasp_num]');
        $cells.= wf_TableCell('[pasp_date]');
        $cells.= wf_TableCell('[pasp_granted]');

        $cells.= wf_TableCell('[usr_comments]');
        $cells.= wf_TableCell('[tags_ids]');
        $cells.= wf_TableCell('[tags_names]');

        $cells.= wf_TableCell('[ao]');
        $cells.= wf_TableCell('[down]');
        $cells.=  wf_TableCell('[passive]');
        $cells.= wf_TableCell('[nas_ip]');

        $rows =  wf_TableRow($cells, 'row1');

        $regdata = array();
        $i = 0;

        foreach ($import_rawdata as $eachrow) {
            $i++;
            if (!empty($skiprowscnt) and $i <= $skiprowscnt) {
                continue;
            }

            // mandatory columns values
            $ip = $eachrow[$import_opts['ip_col']];
            $tariff = $eachrow[$import_opts['tariff_col']];
            $cash = $eachrow[$import_opts['cash_col']];

            // optional columns values
            if ($import_opts['login_col'] != 'RANDOM') {
                $login = $eachrow[$import_opts['login_col']];
            } else {
                $login = 'mi_'.zb_rand_string(8);
            }

            if ($import_opts['password_col'] != 'RANDOM') {
                $password=$eachrow[$import_opts['password_col']];
            } else {
                $password = zb_rand_string(10);
            }

            if ($import_opts['mac_col'] != 'RANDOM') {
                $mac=$eachrow[$import_opts['mac_col']];
            } else {
                $mac = '11:' . '77' . ':' . rand(10,99) . ':' . rand(10,99) . ':' . rand(10,99) . ':' . rand(10,99);
            }

            if ($import_opts['phone_col'] != 'NONE') {
                $phone=$eachrow[$import_opts['phone_col']];
            } else {
                $phone = '';
            }

            if ($import_opts['mobile_col'] != 'NONE') {
                $mobile=$eachrow[$import_opts['mobile_col']];
            } else {
                $mobile = '';
            }

            if ($import_opts['email_col'] != 'NONE') {
                $email = str_replace('"', '', $eachrow[$import_opts['email_col']]);
            } else {
                $email = '';
            }

            if ($import_opts['credit_col'] != 'ZERO') {
                $credit = $eachrow[$import_opts['credit_col']];
            } else {
                $credit = 0;
            }

            if ($import_opts['creditex_col'] != 'NONE') {
                $creditex = $eachrow[$import_opts['creditex_col']];
            } else {
                $creditex = '0';
            }

            if ($import_opts['city_col'] != 'NONE') {
                $city = str_replace('"', '', $eachrow[$import_opts['city_col']]);
            } else {
                $city = '';
            }

            if ($import_opts['street_col'] != 'NONE') {
                $street = str_replace('"', '', $eachrow[$import_opts['street_col']]);
            } else {
                $street = '';
            }

            if ($import_opts['build_col'] != 'NONE') {
                $build = str_replace('"', '', $eachrow[$import_opts['build_col']]);
            } else {
                $build = '';
            }

            if ($import_opts['apt_entrance_col'] != 'NONE') {
                $apt_entrance = str_replace('"', '', $eachrow[$import_opts['apt_entrance_col']]);
            } else {
                $apt_entrance = '';
            }

            if ($import_opts['apt_floor_col'] != 'NONE') {
                $apt_floor = str_replace('"', '', $eachrow[$import_opts['apt_floor_col']]);
            } else {
                $apt_floor = '';
            }

            if ($import_opts['apt_apt_col'] != 'NONE') {
                $apt_apt = str_replace('"', '', $eachrow[$import_opts['apt_apt_col']]);
            } else {
                $apt_apt = '';
            }

            if ($import_opts['address_col'] != 'NONE') {
                $address = str_replace('"', '', $eachrow[$import_opts['address_col']]);
            } else {
                $address = '';
            }

            if ($import_opts['realname_col'] != 'NONE') {
                $realname = str_replace('"', '', $eachrow[$import_opts['realname_col']]);
            } else {
                $realname = '';
            }

            if ($import_opts['contract_col'] != 'NONE') {
                $contract = str_replace('"', '', $eachrow[$import_opts['contract_col']]);
            } else {
                $contract = '';
            }

            if ($import_opts['contract_d_col'] != 'NONE') {
                $contract_d = $eachrow[$import_opts['contract_d_col']];
            } else {
                $contract_d = '';
            }

            if ($import_opts['pasp_num_col'] != 'NONE') {
                $pasp_num = str_replace('"', '', $eachrow[$import_opts['pasp_num_col']]);
            } else {
                $pasp_num = '';
            }

            if ($import_opts['pasp_date_col'] != 'NONE') {
                $pasp_date = str_replace('"', '', $eachrow[$import_opts['pasp_date_col']]);
            } else {
                $pasp_date = '';
            }

            if ($import_opts['pasp_granted_col'] != 'NONE') {
                $pasp_granted = str_replace('"', '', $eachrow[$import_opts['pasp_granted_col']]);
            } else {
                $pasp_granted = '';
            }

            if ($import_opts['usr_comments_col'] != 'NONE') {
                $usr_comments = str_replace('"', '', $eachrow[$import_opts['usr_comments_col']]);
            } else {
                $usr_comments = '';
            }

            if ($import_opts['tags_ids_col'] != 'NONE') {
                $tags_ids = $eachrow[$import_opts['tags_ids_col']];
            } else {
                $tags_ids = '';
            }

            if ($import_opts['tags_names_col'] != 'NONE') {
                $tags_names = str_replace('"', '', $eachrow[$import_opts['tags_names_col']]);
            } else {
                $tags_names = '';
            }

            if ($import_opts['ao_col'] != 'AO_1') {
                $ao=$eachrow[$import_opts['ao_col']];
            } else {
                $ao=1;
            }

            if ($import_opts['down_col'] != 'DOWN_0') {
             $down=$eachrow[$import_opts['down_col']];
            } else {
             $down=0;
            }

            if ($import_opts['passive_col'] != 'PASSIVE_0') {
                $passive=$eachrow[$import_opts['passive_col']];
            } else {
                $passive=0;
            }

            if ($import_opts['nas_ip_col'] != 'NONE') {
                $nas_ip = $eachrow[$import_opts['nas_ip_col']];
            } else {
                $nas_ip = '';
            }

            // getting table row
            $cells = wf_TableCell($i);
            $cells.= wf_TableCell($login);
            $cells.= wf_TableCell($password);
            $cells.= wf_TableCell($ip);
            $cells.= wf_TableCell($mac);
            $cells.= wf_TableCell($tariff);
            $cells.= wf_TableCell($cash);
            $cells.= wf_TableCell($phone);
            $cells.= wf_TableCell($mobile);
            $cells.= wf_TableCell($email);
            $cells.= wf_TableCell($credit);
            $cells.= wf_TableCell($creditex);

            $cells.= wf_TableCell($city);
            $cells.= wf_TableCell($street);
            $cells.= wf_TableCell($build);
            $cells.= wf_TableCell($apt_entrance);
            $cells.= wf_TableCell($apt_floor);
            $cells.= wf_TableCell($apt_apt);

            $cells.= wf_TableCell($address);
            $cells.= wf_TableCell($realname);
            $cells.= wf_TableCell($contract);
            $cells.= wf_TableCell($contract_d);

            $cells.= wf_TableCell($pasp_num);
            $cells.= wf_TableCell($pasp_date);
            $cells.= wf_TableCell($pasp_granted);

            $cells.= wf_TableCell($usr_comments);
            $cells.= wf_TableCell($tags_ids);
            $cells.= wf_TableCell($tags_names);

            $cells.= wf_TableCell($ao);
            $cells.= wf_TableCell($down);
            $cells.= wf_TableCell($passive);

            $cells.= wf_TableCell($nas_ip);

            $rows.= wf_TableRow($cells, 'row3');


            // filling userreg array
            $regdata[$login]['login']           = $login;
            $regdata[$login]['password']        = $password;
            $regdata[$login]['ip']              = $ip;
            $regdata[$login]['mac']             = $mac;
            $regdata[$login]['tariff']          = $tariff;
            $regdata[$login]['cash']            = $cash;
            $regdata[$login]['phone']           = $phone;
            $regdata[$login]['mobile']          = $mobile;
            $regdata[$login]['email']           = $email;
            $regdata[$login]['credit']          = $credit;
            $regdata[$login]['creditex']        = $creditex;

            $regdata[$login]['city']            = $city;
            $regdata[$login]['street']          = $street;
            $regdata[$login]['build']           = $build;
            $regdata[$login]['apt_entrance']    = $apt_entrance;
            $regdata[$login]['apt_floor']       = $apt_floor;
            $regdata[$login]['apt_apt']         = $apt_apt;

            $regdata[$login]['address']         = $address;
            $regdata[$login]['realname']        = $realname;
            $regdata[$login]['contract']        = $contract;
            $regdata[$login]['contract_d']      = $contract_d;

            $regdata[$login]['pasp_num']        = $pasp_num;
            $regdata[$login]['pasp_date']       = $pasp_date;
            $regdata[$login]['pasp_granted']    = $pasp_granted;

            $regdata[$login]['usr_comments']    = $usr_comments;
            $regdata[$login]['tags_ids']        = $tags_ids;
            $regdata[$login]['tags_names']      = $tags_names;

            $regdata[$login]['ao']              = $ao;
            $regdata[$login]['down']            = $down;
            $regdata[$login]['passive']         = $passive;
            $regdata[$login]['nas_ip']          = $nas_ip;
        }

        $regdata_save = serialize($regdata);
        $regdata_save = base64_encode($regdata_save);
        zb_StorageSet('IMPORT_REGDATA', $regdata_save);

        $preparse = wf_TableBody($rows, '100%', '0', '');
        show_window(__('All correct') . '?', $preparse);

        $inputs = wf_tag('h3', false, '', 'style="font: bold;"');
        $inputs.= $skiprowscnt . wf_nbsp(2) . 'rows skipped from the beginning of the import file';
        $inputs.= wf_tag('h3', true);
        $inputs.= wf_delimiter(0);
        $inputs.= wf_Link('?module=migration2_exten', 'No I want to try another import settings', false, 'ubButton');
        $inputs.= wf_delimiter(2);
        $inputs.= wf_Link('?module=migration2_exten&setpointers=true&goregister=ok', 'Yes, proceed registration of this users (no occupancy and tags will be created)', false, 'ubButton');
        $inputs.= wf_delimiter();
        $inputs.= wf_tag('h3', false, '', 'style="color: red; background-color: #F5F5DC"');
        $inputs.= 'Creating occupancy(cities, streets, buildings, addresses, tags, etc) for new users avialable ONLY for "Ubilling live register" user registration mode. ';
        $inputs.= wf_delimiter(0);
        $inputs.= 'Nevertheless this feature has to be used with GREAT CARE and for your OWN RISK';
        $inputs.= wf_tag('h3', true);
        $inputs.= wf_Link('?module=migration2_exten&setpointers=true&goregister=ok&create_accupancy=yes&create_tags=yes',
                         'Yes, proceed registration of this users and create occupancy and tags if not exists.', false, 'ubButton');

        show_window('', $inputs);
    }

    if (!ubRouting::checkGet('setpointers')) {
        if(!ubRouting::checkPost('uploaduserbase')) {
            //show upload form
            show_window(__('User database import from text file'), web_MigrationUploadFormExten());
        } else {
            //upload file and show preprocessing form
            $upload_done = migrate_UploadFileExten();
            if ($upload_done) {
                $delimiter = ubRouting::post('delimiter');
                $encoding =  ubRouting::post('encoding');

                web_MigrationPreprocessingExten($upload_done, $delimiter, $encoding);
            }
        }
    } else {
        //some pointers already set, load raw data into database for processing
        if (ubRouting::checkPost('import_rawdata')) {
            $import_rawdata = ubRouting::post('import_rawdata');
            zb_StorageSet('IMPORT_RAWDATA', $import_rawdata);

            $import_opts = array(
                'login_col'         => ubRouting::post('login_col'),
                'password_col'      => ubRouting::post('password_col'),
                'ip_col'            => ubRouting::post('ip_col'),
                'mac_col'           => ubRouting::post('mac_col'),
                'tariff_col'        => ubRouting::post('tariff_col'),
                'cash_col'          => ubRouting::post('cash_col'),
                'phone_col'         => ubRouting::post('phone_col'),
                'mobile_col'        => ubRouting::post('mobile_col'),
                'email_col'         => ubRouting::post('email_col'),
                'credit_col'        => ubRouting::post('credit_col'),
                'creditex_col'      => ubRouting::post('creditex_col'),

                'city_col'          => ubRouting::post('city_col'),
                'street_col'        => ubRouting::post('street_col'),
                'build_col'         => ubRouting::post('build_col'),
                'apt_entrance_col'  => ubRouting::post('apt_entrance_col'),
                'apt_floor_col'     => ubRouting::post('apt_floor_col'),
                'apt_apt_col'       => ubRouting::post('apt_apt_col'),

                'address_col'       => ubRouting::post('address_col'),
                'contract_col'      => ubRouting::post('contract_col'),
                'realname_col'      => ubRouting::post('realname_col'),
                'contract_d_col'    => ubRouting::post('contract_d_col'),

                'pasp_num_col'      => ubRouting::post('pasp_num_col'),
                'pasp_date_col'     => ubRouting::post('pasp_date_col'),
                'pasp_granted_col'  => ubRouting::post('pasp_granted_col'),

                'usr_comments_col'  => ubRouting::post('usr_comments_col'),
                'tags_ids_col'      => ubRouting::post('tags_ids_col'),
                'tags_names_col'    => ubRouting::post('tags_names_col'),
                'nas_ip_col'        => ubRouting::post('nas_ip_col'),

                'ao_col'            => ubRouting::post('ao_col'),
                'down_col'          => ubRouting::post('down_col'),
                'passive_col'       => ubRouting::post('passive_col'),
                'netid'             => ubRouting::post('networkselect'),
                'regtype'           => ubRouting::post('regtype')
            );

            $import_opts = serialize($import_opts);
            $import_opts = base64_encode($import_opts);
            zb_StorageSet('IMPORT_OPTS', $import_opts);
        } else {
            $import_rawdata = zb_StorageGet('IMPORT_RAWDATA');
            $import_opts =    zb_StorageGet('IMPORT_OPTS');
        }

        //last checks
        if (!ubRouting::checkGet('goregister')) {
            $skiprowscnt = ubRouting::post('skiprowscount');
            web_MigrationPrepareExten($import_rawdata, $import_opts, $skiprowscnt);
        } else {
            $createOccupancy = ( ubRouting::checkGet('create_accupancy') ? true : false );
            $createTags      = ( ubRouting::checkGet('create_tags') ? true : false );

            //register imported users
            $regdata_raw     = zb_StorageGet('IMPORT_REGDATA');
            $regdata         = unserialize(base64_decode($regdata_raw));
            $querybuff       = '';

            if (!empty($regdata)) {
                $RegAddrs = array();
                $iopts    = unserialize(base64_decode($import_opts));
                $allTags  = stg_get_alltagnames();
                $allTagsReversed = array_flip($allTags);
                $allNASes = zb_NasGetAllData();
                $randomBuilds = array();

                // creating tags in tags table
                if ($createTags && $iopts['regtype'] == 'UB') {
                    $allTagsNamesToImport = array();
                    // getting unique tag names
                    foreach ($regdata as $io => $user) {
                        if (!empty($user['tags_names'])) {
                            $tArr = explode(',', $user['tags_names']);

                            foreach ($tArr as $item) {
                                $tagName = trim($item);

                                if (array_key_exists($tagName, $allTagsReversed) or in_array($tagName, $allTagsNamesToImport)) {
                                    continue;
                                } else {
                                    $allTagsNamesToImport[] = $tagName;
                                }
                            }
                        }
                    }

                    if (!empty($allTagsNamesToImport)) {
                        foreach ($allTagsNamesToImport as $tag) {
                            $tagColor    = '#' . rand(11, 99) . rand(11, 99) . rand(11, 99);
                            $tagPriority = 4;
                            $text        = mysql_real_escape_string($tag);

                            $query = "INSERT INTO `tagtypes` (`id` ,`tagcolor` ,`tagsize` ,`tagname`) VALUES (NULL , '" . $tagColor . "', '" . $tagPriority . "', '" . $text . "');";
                            nr_query($query);
                            $newId = simple_get_lastid('tagtypes');
                            log_register('TAGTYPE ADD `' . $text . '` [' . $newId . ']');
                        }
                    }
                }

                // creating settlements, streets, addresses and so on
                if ($createOccupancy && $iopts['regtype'] == 'UB') {
                    // getting unique cities and streets names with buildings
                    foreach ($regdata as $io => $user) {
                        $tmpRegCity = (empty($user['city'])) ? 'Unknown_city' : $user['city'];
                        $tmpRegStreet = (empty($user['street'])) ? 'Unknown_street' : $user['street'];

                        if (empty($user['build'])) {
                            $tmpRegBuilding = 4000 + rand(1, 9999);
                            $randomBuilds[$user['login']] = $tmpRegBuilding;
                        } else {
                            $tmpRegBuilding = $user['build'];
                        }

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

                foreach ($regdata as $io => $user) {
                    debarr($user);

                    //typical register of each user
                    $login      = vf($user['login']);
                    $password   = vf($user['password']);
                    $ip         = $user['ip'];
                    $iopts      = unserialize(base64_decode($import_opts));
                    $netid      = $iopts['netid'];

                    if (empty($ip)) {
                        $netidNAS = 0;

                        if (!empty($user['nas_ip'])) {
                            foreach ($allNASes as $eachNAS) {
                                if ($eachNAS['nasip'] == $user['nas_ip']) {
                                    $netidNAS = $eachNAS['netid'];
                                    break;
                                }
                            }
                        }

                        // trying to get first free IP either from NAS's or chosen net ID
                        if (empty($netidNAS)) {
                            $ip = multinet_get_next_freeip('nethosts', 'ip', $netid);
                        } else {
                            $ip = multinet_get_next_freeip('nethosts', 'ip', $netidNAS);
                            $netid = $netidNAS;
                        }
                    }

                    //Ubilling normal registration mode
                    if ($iopts['regtype'] == 'UB') {
                        $billing->createuser($login);
                        log_register("StgUser REGISTER " . $login);
                        $billing->setpassword($login, $password);
                        log_register("StgUser PASSWORD " . $password);
                        $billing->setip($login, $ip);
                        log_register("StgUser IP " . $ip);
                        multinet_add_host($netid, $ip);
                        zb_UserCreateRealName($login, $user['realname']);
                        zb_UserCreatePhone($login, $user['phone'], $user['mobile']);
                        zb_UserCreateContract($login, $user['contract']);
                        zb_UserContractDateCreate($user['contract'], date('Y-m-d', strtotime($user['contract_d'])));
                        zb_UserCreateEmail($login, $user['email']);
                        zb_UserCreateSpeedOverride($login, 0);
                        multinet_change_mac($ip, $user['mac']);
                        multinet_rebuild_all_handlers();
                        $billing->setao($login, $user['ao']);
                        $dstat = 1;
                        $billing->setdstat($login, $dstat);
                        $billing->setdown($login, $user['down']);
                        $billing->setpassive($login, $user['passive']);
                        $billing->settariff($login, $user['tariff']);
                        $billing->setcredit($login, $user['credit']);
                        $billing->setcash($login, $user['cash']);

                        zb_UserPassportDataCreate($login, '', $user['pasp_num'], $user['pasp_date'], $user['pasp_granted'], '', '', '', '');

                        // assign tags to users
                        if ($createTags) {
                            if (empty($allTagsReversed)) {
                                $allTags         = (empty($allTags) ? stg_get_alltagnames() : $allTags);
                                $allTagsReversed = array_flip($allTags);
                            }

                            if (!empty($allTagsReversed)) {
                                $usrTagsIDs   = explode(',', $user['tags_ids']);
                                $usrTagsNames = explode(',', $user['tags_names']);

                                if (!empty($usrTagsIDs)) {
                                    foreach ($usrTagsIDs as $usrTagsID) {
                                        $tagID = trim($usrTagsID);

                                        // if such tagID even exists?
                                        if (!empty($allTags[$tagID])) {
                                            stg_add_user_tag($login, $tagID);
                                        }
                                    }
                                }

                                if (!empty($usrTagsNames)) {
                                    foreach ($usrTagsNames as $usrTagsName) {
                                        $tagName = trim($usrTagsName);

                                        // if such tagName even exists?
                                        if (!empty($allTagsReversed[$tagName])) {
                                            stg_add_user_tag($login, $allTagsReversed[$tagName]);
                                        }
                                    }
                                }
                            }
                        }

                        $NoOccupancyCreated = true;

                        if ($createOccupancy) {
                            $tmpRegCity         = (empty($user['city'])) ? 'Unknown_city' : $user['city'];
                            $tmpRegStreet       = (empty($user['street'])) ? 'Unknown_street' : $user['street'];
                            $tmpRegBuilding     = (empty($user['build']) and !empty($randomBuilds[$login])) ? $randomBuilds[$login] : $user['build'];
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

                        $userNotes = $user['usr_comments'];

                        if ($NoOccupancyCreated) {
                            zb_UserCreateNotes($login, $user['address'] . '  ' . $userNotes);
                        } elseif (!empty($userNotes)) {
                            zb_UserCreateNotes($login, $userNotes);
                        }
                    }
                }
            }
        }
    }
} else {
    show_error(__('Access denied'));   
}

?>