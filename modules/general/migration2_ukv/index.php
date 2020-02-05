<?php

if (cfr('ROOT')) {

    function renderFileUploadFrom() {
        $delimiters = array(';' => ';',
            '|' => '|',
            ',' => ','
        );

        $encodings = array('utf-8' => 'utf-8',
            'windows-1251' => 'windows-1251',
            'koi8-u' => 'koi8-u',
            'cp866' => 'cp866'
        );

        $uploadinputs = wf_HiddenInput('uploaduserbase', 'true');
        $uploadinputs .= __('Upload userbase') . ' <input id="fileselector" type="file" name="uluserbaseukv" size="10" /><br>';
        $uploadinputs .= wf_Selector('delimiter', $delimiters, __('Delimiter'), '', true);
        $uploadinputs .= wf_Selector('encoding', $encodings, __('Encoding'), '', true);
        $uploadinputs .= wf_Submit('Upload');
        $uploadform = bs_UploadFormBody('', 'POST', $uploadinputs, 'glamour');

        return ($uploadform);
    }

    function uploadFile() {
        $result = false;
        $uploadDir = 'exports/';
        $allowedExtensions = array("txt", "csv");

//check file extension against $allowedExtensions
        foreach ($_FILES as $file) {
            if ($file['tmp_name'] > '') {
                if (@!in_array(end(explode(".", strtolower($file['name']))), $allowedExtensions)) {
                    $errormessage = __('Wrong file type');
                    die($errormessage);
                }
            }
        }

        $fileName = vf($_FILES['uluserbaseukv']['name']);
        $uploadFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['uluserbaseukv']['tmp_name'], $uploadFile)) {
            $result = $fileName;
        }

        return ($result);
    }

    function web_MigrationPreprocessing($filename, $delimiter, $encoding) {
        $path = 'exports/';
        $dataRaw = file_get_contents($path . $filename);
        $dataParsed = array();

        if ($encoding != 'utf-8') {
            $dataRaw = iconv($encoding, 'utf-8', $dataRaw);
        }

        $dataRaw = explodeRows($dataRaw);

        if (!empty($dataRaw)) {
            foreach ($dataRaw as $eachrow) {
                if (!empty($eachrow)) {
                    $dataParsed[] = explode($delimiter, $eachrow);
                }
            }
        }

        if (sizeof($dataParsed) > 1) {
            $colCount = sizeof($dataParsed[0]);

            $cells = wf_TableCell(__('Column number'));
            $cells .= wf_TableCell(__('Column content'));
            $rows = wf_TableRow($cells, 'row1');

            foreach ($dataParsed[0] as $colNum => $colData) {
                $cells = wf_TableCell($colNum);
                $cells .= wf_TableCell($colData);
                $rows .= wf_TableRow($cells, 'row3');
            }

            $firstRow = wf_TableBody($rows, '100%', '0', '');
            show_window(__('Found count of data columns'), $colCount);
            show_window(__('First of imported data rows'), $firstRow);

//construct of data processing form
            $rowNumArr = array();
            for ($i = 0; $i < $colCount; $i++) {
                $rowNumArr[$i] = $i;
            }

            $contract_arr = $rowNumArr + array('RANDOM' => __('Generate Random'));
            $contract_d_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $tariff_arr = $rowNumArr;
            $tariff_price_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $cash_arr = $rowNumArr;
            $active_arr = $rowNumArr;
            $realname_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $phone_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $mobile_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $city_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $street_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $build_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $apt_apt_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $passp_num_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $passp_who_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $passp_date_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $passp_addr_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $inn_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $notes_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $inetlogin_arr = $rowNumArr + array('NONE' => __('Set to none'));
            $regtype_arr = array('UB' => 'Ubilling live register', 'UB_OC' => 'Ubilling live register with occupancy and tariffs creation');

//data column setting form
            $inputs = wf_Selector('contract_col', $contract_arr, __('User contract'), '0', true);
            $inputs .= wf_Selector('contract_d_col', $contract_d_arr, __('User contract date'), '1', true);
            $inputs .= wf_Selector('tariff_col', $tariff_arr, __('User tariff'), '2', true);
            $inputs .= wf_Selector('tariff_price_col', $tariff_price_arr, __('User tariff price'), '3', true);
            $inputs .= wf_Selector('cash_col', $cash_arr, __('User cash'), '4', true);
            $inputs .= wf_Selector('isactive_col', $active_arr, __('User active'), '5', true);
            $inputs .= wf_Selector('realname_col', $realname_arr, __('User realname'), '6', true);
            $inputs .= wf_Selector('phone_col', $phone_arr, __('User phone'), '7', true);
            $inputs .= wf_Selector('mobile_col', $mobile_arr, __('User mobile'), '8', true);
            $inputs .= wf_Selector('city_col', $city_arr, __('User city'), '9', true);
            $inputs .= wf_Selector('street_col', $street_arr, __('User street'), '10', true);
            $inputs .= wf_Selector('build_col', $build_arr, __('User build'), '11', true);
            $inputs .= wf_Selector('apt_apt_col', $apt_apt_arr, __('User apt'), '12', true);
            $inputs .= wf_Selector('passp_num_col', $passp_num_arr, __('User passp num'), '13', true);
            $inputs .= wf_Selector('passp_who_col', $passp_who_arr, __('User passp who'), '14', true);
            $inputs .= wf_Selector('passp_date_col', $passp_date_arr, __('User passp date'), '15', true);
            $inputs .= wf_Selector('passp_addr_col', $passp_addr_arr, __('User passp address'), '16', true);
            $inputs .= wf_Selector('inn_col', $inn_arr, __('User INN'), '18', true);
            $inputs .= wf_Selector('notes_col', $notes_arr, __('User notes'), '19', true);
            $inputs .= wf_Selector('inetlogin_col', $inetlogin_arr, __('User inet login'), '20', true);
            $inputs .= wf_Selector('regtype', $regtype_arr, __('User registration mode'), '21', true);
            $inputs .= wf_HiddenInput('import_rawdata', base64_encode(serialize($dataParsed)));
            $inputs .= wf_Submit('Save this column pointers and continue import');

            $colForm = wf_Form("?module=migration2_ukv&setpointers=true", 'POST', $inputs, 'glamour');
            show_window(__('Select data columns and their values'), $colForm);
        } else {
            show_error(__('Parsing error'));
        }
    }

    function web_MigrationPrepare($importRawData, $importOptions) {
        $importRawData = unserialize(base64_decode($importRawData));
        $importOptions = unserialize(base64_decode($importOptions));

        $cells = wf_TableCell('#');
        $cells .= wf_TableCell('[contract]');
        $cells .= wf_TableCell('[contract_d]');
        $cells .= wf_TableCell('[tariff]');
        $cells .= wf_TableCell('[tariff_price]');
        $cells .= wf_TableCell('[cash]');
        $cells .= wf_TableCell('[isactive]');
        $cells .= wf_TableCell('[realname]');
        $cells .= wf_TableCell('[phone]');
        $cells .= wf_TableCell('[mobile]');
        $cells .= wf_TableCell('[city]');
        $cells .= wf_TableCell('[street]');
        $cells .= wf_TableCell('[build]');
        $cells .= wf_TableCell('[apt_apt]');
        $cells .= wf_TableCell('[passpnum]');
        $cells .= wf_TableCell('[passpwho]');
        $cells .= wf_TableCell('[passpdate]');
        $cells .= wf_TableCell('[passpaddr]');
        $cells .= wf_TableCell('[inn]');
        $cells .= wf_TableCell('[notes]');
        $cells .= wf_TableCell('[inetlogin]');

        $rows = wf_TableRow($cells, 'row1');

        $regdata = array();
        $i = 0;

        foreach ($importRawData as $eachRow) {
            $i++;
            $cells = wf_TableCell($i);

            if ($importOptions['contract_col'] != 'RANDOM') {
                $contract = $eachRow[$importOptions['contract_col']];
            } else {
                $contract = zb_rand_digits(7);
            }
            $cells .= wf_TableCell($contract);

            if ($importOptions['contract_d_col'] != 'NONE') {
                $contract_d = $eachRow[$importOptions['contract_d_col']];
            } else {
                $contract_d = '';
            }
            $cells .= wf_TableCell($contract_d);

            $tariff = $eachRow[$importOptions['tariff_col']];
            $cells .= wf_TableCell($tariff);

            if ($importOptions['tariff_price_col'] != 'NONE') {
                $tariff_price = $eachRow[$importOptions['tariff_price_col']];
            } else {
                $tariff_price = '0';
            }
            $cells .= wf_TableCell($tariff_price);

            $cash = $eachRow[$importOptions['cash_col']];
            $cells .= wf_TableCell($cash);

            if ($importOptions['isactive_col'] != 'NONE') {
                $isactive = $eachRow[$importOptions['isactive_col']];
            } else {
                $isactive = '';
            }
            $cells .= wf_TableCell($isactive);

            if ($importOptions['realname_col'] != 'NONE') {
                $realname = $eachRow[$importOptions['realname_col']];
            } else {
                $realname = '';
            }
            $cells .= wf_TableCell($realname);

            if ($importOptions['phone_col'] != 'NONE') {
                $phone = $eachRow[$importOptions['phone_col']];
            } else {
                $phone = '';
            }
            $cells .= wf_TableCell($phone);

            if ($importOptions['mobile_col'] != 'NONE') {
                $mobile = $eachRow[$importOptions['mobile_col']];
            } else {
                $mobile = '';
            }
            $cells .= wf_TableCell($mobile);

            if ($importOptions['city_col'] != 'NONE') {
                $city = $eachRow[$importOptions['city_col']];
            } else {
                $city = '';
            }
            $cells .= wf_TableCell($city);

            if ($importOptions['street_col'] != 'NONE') {
                $street = $eachRow[$importOptions['street_col']];
            } else {
                $street = '';
            }
            $cells .= wf_TableCell($street);

            if ($importOptions['build_col'] != 'NONE') {
                $build = $eachRow[$importOptions['build_col']];
            } else {
                $build = '';
            }
            $cells .= wf_TableCell($build);

            if ($importOptions['apt_apt_col'] != 'NONE') {
                $apt_apt = $eachRow[$importOptions['apt_apt_col']];
            } else {
                $apt_apt = '';
            }
            $cells .= wf_TableCell($apt_apt);

            if ($importOptions['passp_num_col'] != 'NONE') {
                $passpnum = $eachRow[$importOptions['passp_num_col']];
            } else {
                $passpnum = '';
            }
            $cells .= wf_TableCell($passpnum);

            if ($importOptions['passp_who_col'] != 'NONE') {
                $passpwho = $eachRow[$importOptions['passp_who_col']];
            } else {
                $passpwho = '';
            }
            $cells .= wf_TableCell($passpwho);

            if ($importOptions['passp_date_col'] != 'NONE') {
                $passpdate = $eachRow[$importOptions['passp_date_col']];
            } else {
                $passpdate = '';
            }
            $cells .= wf_TableCell($passpdate);

            if ($importOptions['passp_addr_col'] != 'NONE') {
                $passpaddr = $eachRow[$importOptions['passp_addr_col']];
            } else {
                $passpaddr = '';
            }
            $cells .= wf_TableCell($passpaddr);

            if ($importOptions['inn_col'] != 'NONE') {
                $inn = $eachRow[$importOptions['inn_col']];
            } else {
                $inn = '';
            }
            $cells .= wf_TableCell($inn);

            if ($importOptions['notes_col'] != 'NONE') {
                $notes = $eachRow[$importOptions['notes_col']];
            } else {
                $notes = '';
            }
            $cells .= wf_TableCell($notes);

            if ($importOptions['inetlogin_col'] != 'NONE') {
                $inetlogin = $eachRow[$importOptions['inetlogin_col']];
            } else {
                $inetlogin = '';
            }
            $cells .= wf_TableCell($inetlogin);

            $rows .= wf_TableRow($cells, 'row3');

// filling userreg array
            $regdata[$contract]['contract'] = $contract;
            $regdata[$contract]['contract_d'] = $contract_d;
            $regdata[$contract]['tariff'] = $tariff;
            $regdata[$contract]['tariff_price'] = $tariff_price;
            $regdata[$contract]['cash'] = $cash;
            $regdata[$contract]['isactive'] = $isactive;
            $regdata[$contract]['realname'] = $realname;
            $regdata[$contract]['phone'] = $phone;
            $regdata[$contract]['mobile'] = $mobile;
            $regdata[$contract]['city'] = $city;
            $regdata[$contract]['street'] = $street;
            $regdata[$contract]['build'] = $build;
            $regdata[$contract]['apt_apt'] = $apt_apt;
            $regdata[$contract]['passpnum'] = $passpnum;
            $regdata[$contract]['passpwho'] = $passpwho;
            $regdata[$contract]['passpdate'] = $passpdate;
            $regdata[$contract]['passpaddr'] = $passpaddr;
            $regdata[$contract]['inn'] = $inn;
            $regdata[$contract]['notes'] = $notes;
            $regdata[$contract]['inetlogin'] = $inetlogin;
        }

        $regdataSave = serialize($regdata);
        $regdataSave = base64_encode($regdataSave);
        zb_StorageSet('IMPORT_REGDATA_UKV', $regdataSave);

        $preParsed = wf_TableBody($rows, '100%', '0', '');
        show_window(__('All correct') . '?', $preParsed);

        $inputs = wf_Link('?module=migration2_ukv', 'No I want to try another import settings', false, 'ubButton');
        $inputs .= wf_Link('?module=migration2_ukv&setpointers=true&goregister=ok', 'Yes, proceed registration of this users', false, 'ubButton');
        /* $inputs.= wf_delimiter();
          $inputs.= wf_tag('h3', false, '', 'style="color: red; background-color: #F5F5DC"');
          $inputs.= 'Creating occupancy(cities, streets, buildings, addresses, etc) for new users avialable ONLY for "Ubilling live register" user registration mode. ';
          $inputs.= 'Nevertheless this feature has to be used with GREAT CARE and for your OWN RISK';
          $inputs.= wf_tag('h3', true);
          $inputs.= wf_Link('?module=migration2&setpointers=true&goregister=ok&create_accupancy=yes',
          'Yes, proceed registeration of this users and create occupancy if not exists.', false, 'ubButton'); */

        show_window('', $inputs);
    }

    if (!wf_CheckGet(array('setpointers'))) {
        if (!wf_CheckPost(array('uploaduserbase'))) {
//show upload form
            show_window(__('User database import from text file'), renderFileUploadFrom());
        } else {
//upload file and show preprocessing form
            $upload_done = uploadFile();
            if ($upload_done) {
                $delimiter = $_POST['delimiter'];
                $encoding = $_POST['encoding'];

                web_MigrationPreprocessing($upload_done, $delimiter, $encoding);
            }
        }
    } else {
//some pointers already set, load raw data into database for processing
        if (wf_CheckPost(array('import_rawdata'))) {
            $importRawData = $_POST['import_rawdata'];
            zb_StorageSet('IMPORT_RAWDATA_UKV', $importRawData);

            $importOptions = array(
                'contract_col' => $_POST['contract_col'],
                'contract_d_col' => $_POST['contract_d_col'],
                'tariff_col' => $_POST['tariff_col'],
                'tariff_price_col' => $_POST['tariff_price_col'],
                'cash_col' => $_POST['cash_col'],
                'isactive_col' => $_POST['isactive_col'],
                'realname_col' => $_POST['realname_col'],
                'phone_col' => $_POST['phone_col'],
                'mobile_col' => $_POST['mobile_col'],
                'city_col' => $_POST['city_col'],
                'street_col' => $_POST['street_col'],
                'build_col' => $_POST['build_col'],
                'apt_apt_col' => $_POST['apt_apt_col'],
                'passp_num_col' => $_POST['passp_num_col'],
                'passp_who_col' => $_POST['passp_who_col'],
                'passp_date_col' => $_POST['passp_date_col'],
                'passp_addr_col' => $_POST['passp_addr_col'],
                'inn_col' => $_POST['inn_col'],
                'notes_col' => $_POST['notes_col'],
                'inetlogin_col' => $_POST['inetlogin_col'],
                'regtype' => $_POST['regtype']
            );

            $importOptions = base64_encode(serialize($importOptions));
            zb_StorageSet('IMPORT_OPTS_UKV', $importOptions);
        } else {
            $importRawData = zb_StorageGet('IMPORT_RAWDATA_UKV');
            $importOptions = zb_StorageGet('IMPORT_OPTS_UKV');
        }

//last checks
        if (!wf_CheckGet(array('goregister'))) {
            web_MigrationPrepare($importRawData, $importOptions);
        } else {
//$CreateOccupancy = ( wf_CheckGet(array('create_accupancy')) ) ? true : false;
//register imported users
            $regdata_raw = zb_StorageGet('IMPORT_REGDATA_UKV');
            $regdata = unserialize(base64_decode($regdata_raw));
            $querybuff = '';

            if (!empty($regdata)) {
                $ukv = new UkvSystem();
                $RegAddrs = array();
                $RegTariffs = array();
                $iopts = unserialize(base64_decode($importOptions));

//if ($CreateOccupancy && $iopts['regtype'] == 'UB') {
                if ($iopts['regtype'] == 'UB_OC') {
// getting unique cities and streets names with buildings
                    foreach ($regdata as $io => $user) {
                        $tmpRegCity = (empty($user['city'])) ? 'Unknown' : $user['city'];
                        $tmpRegStreet = (empty($user['street'])) ? 'Unknown' : $user['street'];
                        $tmpRegBuilding = $user['build'];
                        $tmpRegTariff = $user['tariff'];
                        $tmpRegTariffPrice = $user['tariff_price'];

                        if (!array_key_exists($tmpRegCity, $RegAddrs)) {
                            $RegAddrs[$tmpRegCity] = array();
                        }

                        if (!array_key_exists($tmpRegStreet, $RegAddrs[$tmpRegCity])) {
                            $RegAddrs[$tmpRegCity][$tmpRegStreet] = array();
                        }

                        if (!in_array($tmpRegBuilding, $RegAddrs[$tmpRegCity][$tmpRegStreet])) {
                            $RegAddrs[$tmpRegCity][$tmpRegStreet][$tmpRegBuilding] = array('id' => '');
                        }

                        if (!array_key_exists($tmpRegTariff, $RegTariffs)) {
                            $RegTariffs[$tmpRegTariff] = $tmpRegTariffPrice;
                        }
                    }

                    if (!empty($RegAddrs)) {
                        foreach ($RegAddrs as $tCity => $CityData) {
                            $CityWasJustCreated = false;

                            $tmpQuery = "SELECT  * FROM `city` WHERE LOWER(`cityname`) = '" . mb_strtolower($tCity, "UTF-8") . "';";
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
                                    $tmpQuery = "SELECT * FROM `street` WHERE `cityid` = '" . $tCityID . "' AND LOWER(`streetname`) = '" . mb_strtolower($tStreet, "UTF-8") . "';";
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
                                        $tmpQuery = "SELECT * FROM `build` WHERE `streetid` = '" . $tStreetID . "' AND LOWER(`buildnum`) = '" . mb_strtolower($tBuild, "UTF-8") . "';";
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

                    if (!empty($RegTariffs)) {
                        foreach ($RegTariffs as $eachTariff => $eachPrice) {
                            $tmpQuery = "SELECT * FROM `ukv_tariffs` WHERE LOWER(`tariffname`) = '" . mb_strtolower($eachTariff, "UTF-8") . "';";
                            $tmpResult = simple_queryall($tmpQuery);

                            if (empty($tmpResult)) {
                                $ukv->tariffCreate($eachTariff, $eachPrice);
                            }
                        }
                    }
                }

// getting tarrifs in a tariffname => tariffid representation
                $tmpQuery = "SELECT * FROM `ukv_tariffs` ORDER BY `tariffname`";
                $tmpResult = simple_queryall($tmpQuery);
                $ukv_tariffs = array();

                foreach ($tmpResult as $io => $eachTariff) {
                    $ukv_tariffs[$eachTariff['tariffname']] = $eachTariff['id'];
                }

                $i = 0;
                $writeDebugLog = false;
                $debugLog = '';

                foreach ($regdata as $io => $user) {
                    $i++;

                    $userTariffId = (isset($ukv_tariffs[$user['tariff']])) ? $ukv_tariffs[$user['tariff']] : 0;

                    $tmpQuery = "INSERT INTO `ukv_users` (`contract`, `tariffid`, `cash`, `active`, `realname`, `passnum`, `passwho`, `passdate`, `paddr`, 
                                                      `ssn`, `phone`, `mobile`, `regdate`, `city`, `street`, `build`, `apt`, `inetlogin`, `notes`)
                                              VALUES ('" . $user['contract'] . "', " . $userTariffId . ", " . str_replace(',', '.', $user['cash']) . ", " . $user['isactive'] . ", '" . $user['realname'] . "', '" .
                            $user['passpnum'] . "', '" . $user['passpwho'] . "', '" . $user['passpdate'] . "', '" . $user['passpaddr'] . "', '" . $user['inn'] . "', '" .
                            $user['phone'] . "', '" . $user['mobile'] . "', '" . $user['contract_d'] . "', '" . $user['city'] . "', '" . $user['street'] . "', '" .
                            $user['build'] . "', '" . $user['apt_apt'] . "', '" . $user['notes'] . "', '" . $user['inetlogin'] . "')";

                    $debugLog .= '<b>' . $i . '.</b>   <pre>' . print_r($user, true) . '</pre>' . $tmpQuery . '<hr /><br /><br />';
                    nr_query($tmpQuery);
                    log_register('UKV user added with contract ' . $user['contract']);
                }

                $windowContent = (($writeDebugLog) ? $debugLog : ' ') . wf_nbsp() . wf_tag('h2') . __('Processed records') . ': ' . $i . wf_tag('h2', true) .
                        wf_delimiter() . wf_BackLink('?module=migration2_ukv');
                show_window(__('Process finished'), $windowContent);
            }
        }
    }
} else {
    show_error(__('Access denied'));
}
?>