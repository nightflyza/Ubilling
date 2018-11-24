<?php

if (cfr('ROOT')) {

    error_reporting(E_ALL);

    class ConverTador {

        protected $ignoreLines = 1;
        protected $allUserData = array();

        /**
         * cityname=>id
         *
         * @var array
         */
        protected $allCities = array();

        /**
         * cityid=>streetname=>id
         *
         * @var array
         */
        protected $allStreets = array();

        /**
         * streetid=>buildnum=>id
         *
         * @var array
         */
        protected $allBuilds = array();

        /**
         * buildid=>apt=>id
         *
         * @var array
         */
        protected $allApt = array();

        /**
         * List of allowed extensions
         *
         * @var array
         */
        protected $allowedExtensions = array('csv');

        /**
         * login=>aptid=>id
         *
         * @var array
         */
        protected $allAddress = array();
        protected $allTariffs = array();
        protected $delimiter = ";";
        protected $normalLine = 29;
        protected $rawCsv = '';
        protected $tariffPrefix = 'Speed-';
        protected $offsets = array();
        protected $grayIpNet = '10.11';
        protected $whiteIpNet = '195.162.80';
        protected $nasIpAddr = '195.162.83.29';
        protected $nethosts = array();
        protected $currentIp = 2;
        protected $currentNet = 1;
        protected $mobileLen = 6;
        protected $networks = array();
        protected $usedMacs = array();
        protected $currentCityId = 1;
        protected $currentStreetId = 1;
        protected $currentBuildId = 1;
        protected $currentAptId = 1;
        protected $currentAddressId = 1;
        protected $orphans = 0;

        /**
         * Some upload options
         */
        const UPLOAD_PATH = 'exports/';
        const SQL_PREPEND = 'docs/conv/cleanbase_092/ubilling_clean_base.sql';
        const MLG_PREPEND = 'docs/multigen/dump.sql';

        public function __construct() {
            $this->setOffsets();
        }

        public function setCsv($data) {
            $data = iconv('windows-1251', 'utf-8', $data);
            $this->rawCsv = explodeRows($data);
        }

        protected function setOffsets() {
            $this->offsets['login'] = 1;
            $this->offsets['password'] = 2;
            $this->offsets['down'] = 6;
            $this->offsets['speed'] = 17;
            $this->offsets['realip'] = 16;
            $this->offsets['cash'] = 28;
            $this->offsets['realname'] = 7;
            $this->offsets['tarifffee'] = 22;
            $this->offsets['notes'] = 14;
            $this->offsets['regdate'] = 4;
            $this->offsets['phone'] = 8;
            $this->offsets['city'] = 9;
            $this->offsets['street'] = 11;
            $this->offsets['build'] = 12;
            $this->offsets['apt'] = 13;


            //first net will be realip
            $this->networks[1] = array('id' => 1, 'start' => $this->whiteIpNet . '.0', 'end' => $this->whiteIpNet . '.255', 'cidr' => $this->whiteIpNet . '.0/24');
        }

        protected function generateNethost($realip = '', $mac = '') {

            if (empty($mac)) {
                $newMac = '14:' . '88' . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99);
                if (isset($this->usedMacs[$newMac])) {
                    $newMac = '14:' . '88' . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99);
                }
            } else {
                $newMac = $mac;
            }

            if (isset($this->usedMacs[$newMac])) {
                die('MAC DUPLICATE FAIL');
            }

            if (strlen($realip) < 5) {
                $newIp = $this->currentIp + 1;
                if ($newIp == 255) {
                    $this->currentNet++;
                    $this->currentIp = 2; //reserving .1 for NAS etc
                    $newIp = $this->currentIp;
                } else {
                    $this->currentIp = $newIp;
                }


                $fullIp = $this->grayIpNet . '.' . $this->currentNet . '.' . $newIp;
                $this->nethosts[$fullIp] = array('netid' => $this->currentNet + 1, 'mac' => $newMac);
                $this->networks[$this->currentNet + 1] = array('id' => $this->currentNet + 1, 'start' => $this->grayIpNet . '.' . $this->currentNet . '.0', 'end' => $this->grayIpNet . '.' . $this->currentNet . '.255', 'cidr' => $this->grayIpNet . '.' . $this->currentNet . '.0/24');
            } else {
                $fullIp = $realip;
                $this->nethosts[$fullIp] = array('netid' => 1, 'mac' => $newMac);
            }


            $this->usedMacs[$newMac] = $fullIp;
            return ($fullIp);
        }

        protected function generateCash($cash) {
            $result = '';
            $result = trim($cash);
            return ($result);
        }

        protected function fixCase($data) {
            $result = mb_convert_case($data, MB_CASE_TITLE, "UTF-8");
            return ($result);
        }

        protected function generateAddress($userLogin, $city, $street, $build, $apt) {
            $city = mysql_real_escape_string($city);
            $street = mysql_real_escape_string($street);
            $build = mysql_real_escape_string($build);
            $apt = mysql_real_escape_string($apt);
            $result = '';

            $city = trim($city);
            $street = trim($street);
            $build = trim($build);
            $apt = trim($apt);

            $city = $this->fixCase($city);
            $street = $this->fixCase($street);
            $build = vf($build);
            if (mb_strlen($build, 'utf-8') > 10) {
                $build = mb_substr($build, 0, 10, 'utf-8');
            }

            if (!empty($city)) {
                if (!isset($this->allCities[$city])) {
                    $this->allCities[$city] = $this->currentCityId;
                    $this->currentCityId++;
                }


                $cityId = $this->allCities[$city];

                if (!empty($street)) {
                    if (!isset($this->allStreets[$cityId][$street])) {
                        $this->allStreets[$cityId][$street] = $this->currentStreetId;
                        $this->currentStreetId++;
                    }

                    $streetId = $this->allStreets[$cityId][$street];

                    if (!empty($build)) {
                        if (!isset($this->allBuilds[$streetId][$build])) {
                            $this->allBuilds[$streetId][$build] = $this->currentBuildId;
                            $this->currentBuildId++;
                        }

                        $buildId = $this->allBuilds[$streetId][$build];

                        if (!empty($apt)) {
                            if (!isset($this->allApt[$buildId][$apt])) {
                                $this->allApt[$buildId][$apt] = $this->currentAptId;
                                $this->currentAptId++;
                            }

                            $aptId = $this->allApt[$buildId][$apt];
                        } else {
                            //seems private house - zero apt
                            $apt = 0;
                            if (!isset($this->allApt[$buildId][$apt])) {
                                $this->allApt[$buildId][$apt] = $this->currentAptId;
                                $this->currentAptId++;
                            }

                            $aptId = $this->allApt[$buildId][$apt];
                        }

                        //we need a build and apt at least
                        if (!empty($aptId)) {
                            $this->allAddress[$userLogin] = $aptId;
                            $this->currentAddressId++;

                            if ($apt != 0) {
                                $result = mysql_real_escape_string($street . ' ' . $build . '/' . $apt);
                            } else {
                                $result = mysql_real_escape_string($street . ' ' . $build);
                            }
                        }
                    }
                }
            }

            if (empty($result)) {
                $this->orphans++;
                $result = 'Пропили';
            }


            return ($result);
        }

        protected function saveAddressData() {
            $result = '';
            if (!empty($this->allCities)) {
                foreach ($this->allCities as $io => $each) {
                    $result.= "INSERT INTO `city` (`id`,`cityname`,`cityalias`) "
                            . "VALUES ('" . $each . "', '" . $io . "',''); " . "\n";
                }
            }

            if (!empty($this->allStreets)) {
                foreach ($this->allStreets as $io => $each) {
                    if (!empty($each)) {
                        foreach ($each as $streetname => $id) {
                            $aliasProposal = zb_TranslitString($streetname);
                            $aliasProposal = str_replace(' ', '', $aliasProposal);
                            $aliasProposal = str_replace('-', '', $aliasProposal);
                            if (strlen($aliasProposal) > 5) {
                                $newstreetalias = substr($aliasProposal, 0, 5);
                            } else {
                                $newstreetalias = $aliasProposal;
                            }

                            $newstreetalias = vf($newstreetalias, 2);
                            $result.= "INSERT INTO `street` (`id`,`cityid`,`streetname`,`streetalias`) "
                                    . "VALUES  ('" . $id . "', '" . $io . "','" . $streetname . "','" . $newstreetalias . "');" . "\n";
                        }
                    }
                }
            }

            if (!empty($this->allBuilds)) {
                foreach ($this->allBuilds as $io => $each) {
                    if (!empty($each)) {
                        foreach ($each as $ia => $id) {
                            $result.= "INSERT INTO `build` (`id`,`streetid`,`buildnum`) VALUES ('" . $id . "', '" . $io . "','" . $ia . "');" . "\n";
                        }
                    }
                }
            }

            if (!empty($this->allApt)) {
                foreach ($this->allApt as $io => $each) {
                    if (!empty($each)) {
                        foreach ($each as $ia => $id) {
                            $result.= "INSERT INTO `apt` (`id`,`buildid`,`entrance`,`floor`,`apt`) "
                                    . "VALUES ('" . $id . "','" . $io . "','','','" . $ia . "');" . "\n";
                        }
                    }
                }
            }

            if (!empty($this->allAddress)) {
                foreach ($this->allAddress as $io => $each) {
                    $result.= "INSERT INTO `address` (`id`,`login`,`aptid`) "
                            . "VALUES (NULL, '" . $io . "','" . $each . "');" . "\n";
                }
            }


            return ($result);
        }

        public function processing() {
            $count = 0;
            $failCount = 0;
            $okCount = 0;
            $failCashCount = 0;
            $failedUsers = array();

            $result = '';

            if (file_exists(self::SQL_PREPEND)) {
                $result.=file_get_contents(self::SQL_PREPEND); //some clean base here
            }


            if (file_exists(self::MLG_PREPEND)) {
                $result.=file_get_contents(self::MLG_PREPEND); // and multigen presets
            }

            if (!empty($this->rawCsv)) {
                foreach ($this->rawCsv as $io => $eachLine) {
                    $count++;
                    if ($count > $this->ignoreLines) {
                        $eachLine = explode($this->delimiter, $eachLine);
                        if (sizeof($eachLine) == $this->normalLine) {
                            if (!isset($this->allUserData[$this->offsets['login']])) {
                                $userTariff = $this->tariffPrefix . $eachLine[$this->offsets['speed']];

                                if (!isset($this->allTariffs[$userTariff])) {
                                    $this->allTariffs[$userTariff]['speed'] = $eachLine[$this->offsets['speed']] * 1024;
                                    $this->allTariffs[$userTariff]['fee'] = $eachLine[$this->offsets['tarifffee']];
                                }

                                $userLogin = $eachLine[$this->offsets['login']];
                                $userLogin = vf($userLogin);
                                $userIp = $this->generateNethost($eachLine[$this->offsets['realip']]);
                                $userCash = $this->generateCash($eachLine[$this->offsets['cash']]);

                                $userRealName = $eachLine[$this->offsets['realname']];
                                $userRealName = mysql_real_escape_string($userRealName);
                                $userRealName = trim($userRealName);
                                $userRealName = str_replace('"', '``', $userRealName);
                                $userRealName = str_replace("'", '`', $userRealName);
                                $userRealName = $this->fixCase($userRealName);

                                $userPassword = $eachLine[$this->offsets['password']];
                                $userPassword = vf($userPassword);
                                if (empty($userPassword)) {
                                    $userPassword = 'ge_' . zb_rand_string(6);
                                }

                                if ($userCash == 502) {
                                    $failCashCount++;
                                }
                                //user primary table
                                $result.="INSERT INTO `users` (`login`, `Password`, `Passive`, `Down`, `DisabledDetailStat`, `AlwaysOnline`, `Tariff`, `Address`, `Phone`, `Email`, `Note`, `RealName`, `StgGroup`, `Credit`, `TariffChange`, `Userdata0`, `Userdata1`, `Userdata2`, `Userdata3`, `Userdata4`, `Userdata5`, `Userdata6`, `Userdata7`, `Userdata8`, `Userdata9`, `CreditExpire`, `IP`, `D0`, `U0`, `D1`, `U1`, `D2`, `U2`, `D3`, `U3`, `D4`, `U4`, `D5`, `U5`, `D6`, `U6`, `D7`, `U7`, `D8`, `U8`, `D9`, `U9`, `Cash`, `FreeMb`, `LastCashAdd`, `LastCashAddTime`, `PassiveTime`, `LastActivityTime`, `NAS`) "
                                        . "VALUES ('" . $userLogin . "',"
                                        . " '" . $userPassword . "', '0', '" . $eachLine[$this->offsets['down']] . "', '1', '1', "
                                        . "'" . $userTariff . "', '', '', '', '', '', '', '0', '', '', '', '', '', '', '', '', '', '', '', '0', '" . $userIp . "', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0',"
                                        . " '" . $userCash . "', '0', '0', '0', '0', '0', '');" . "\n";

                                //speed overrides
                                $result.="INSERT INTO `userspeeds` (`id` ,`login` ,`speed`) VALUES (NULL , '" . $userLogin . "', '0');" . "\n";
                                //realnames 
                                $result.= "INSERT INTO `realname`  (`id`,`login`,`realname`) VALUES   (NULL, '" . $userLogin . "','" . $userRealName . "'); " . "\n";
                                //emails 
                                $result.= "INSERT INTO `emails`  (`id`,`login`,`email`) VALUES  (NULL, '" . $userLogin . "','');" . "\n";
                                //contracts
                                $result.= "INSERT INTO `contracts` (`id`,`login`,`contract`)  VALUES  (NULL, '" . $userLogin . "','');" . "\n";
                                //address creation
                                $userAddress = $this->generateAddress($userLogin, $eachLine[$this->offsets['city']], $eachLine[$this->offsets['street']], $eachLine[$this->offsets['build']], $eachLine[$this->offsets['apt']]);
                                //reglog
                                $regTimeStamp = strtotime($eachLine[$this->offsets['regdate']]);
                                $regDateTime = date("Y-m-d", $regTimeStamp) . ' 10:42:42';
                                $result.= "INSERT INTO `userreg` (`id` ,`date` ,`admin` ,`login` ,`address`) "
                                        . "VALUES (NULL , '" . $regDateTime . "', 'converter', '" . $userLogin . "', '" . $userAddress . "');" . "\n";
                                //some phones
                                $mobile = '';
                                $phone = '';
                                $userPhone = $eachLine[$this->offsets['phone']];
                                if (strlen($userPhone) > $this->mobileLen) {
                                    $mobile = '0' . $userPhone;
                                } else {
                                    $phone = $userPhone;
                                }
                                $result.= "INSERT INTO `phones`  (`id`,`login`,`phone`,`mobile`)  "
                                        . "VALUES  (NULL, '" . $userLogin . "','" . $phone . "','" . $mobile . "');" . "\n";
                                //user notes
                                $userNotes = $eachLine[$this->offsets['notes']];
                                $userNotes = mysql_real_escape_string($userNotes);
                                $userNotes = trim($userNotes);
                                if (strlen($userNotes) < 250) {
                                    $result.= "INSERT INTO `notes` (`id` , `login` ,`note`) "
                                            . "VALUES (NULL , '" . $userLogin . "', '" . $userNotes . "');" . "\n";
                                }

                                //seems ok
                                $okCount++;
                            }
                        } else {
                            $failedUsers[] = $eachLine;
                            $failCount++;
                        }
                    }
                }

                //creating tariffs
                if (!empty($this->allTariffs)) {
                    foreach ($this->allTariffs as $io => $each) {
                        $result.="INSERT INTO `tariffs` VALUES ('" . $io . "', 0, 0, 0, 0, 0, '0:0-0:0', 1, 1, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, 0, 0, 0, 0, '0:0-0:0', 0, 0, 0, " . $each['fee'] . ", 0, 'up+down', 'month');" . "\n";
                    }

                    foreach ($this->allTariffs as $io => $each) {
                        $result.= "INSERT INTO `speeds` (`id` , `tariff` , `speeddown` , `speedup` , `burstdownload` , `burstupload` , `bursttimedownload` , `burstimetupload`) "
                                . "VALUES (NULL , '" . $io . "', '" . $each['speed'] . "', '" . $each['speed'] . "', '', '', '', '');" . "\n";
                    }
                }

                //generating nethosts data
                if (!empty($this->networks)) {
                    foreach ($this->networks as $io => $each) {
                        $result.="INSERT INTO `networks` (`id`, `desc`, `startip`, `endip`, `nettype`, `use_radius` ) "
                                . "VALUES (NULL, '" . $each['cidr'] . "', '" . $each['start'] . "', '" . $each['end'] . "', 'dhcpstatic', '0');" . "\n";
                    }

                    foreach ($this->nethosts as $io => $each) {
                        $result.= "INSERT INTO `nethosts` (`id` ,`ip` ,`mac` ,`netid` ,`option`) "
                                . "VALUES (NULL , '" . $io . "', '" . $each['mac'] . "', '" . $each['netid'] . "', '');" . "\n";
                    }

                    foreach ($this->networks as $io => $each) {
                        $result.= "INSERT INTO `nas` (`id` ,`netid` , `nasip` , `nasname` , `nastype` , `bandw`) VALUES
                                   (NULL , '" . $each['id'] . "', '" . $this->nasIpAddr . "', 'PPPoE',  'radius', '');";
                    }
                }

                //generating address data
                $result.=$this->saveAddressData();
            }


            show_info('Всього рядків на вході: ' . ($count - $this->ignoreLines));
            show_success('Нормально зібрано юзерів: ' . $okCount);
            show_success('Нафігачено тарифів зі швидкостями: ' . sizeof($this->allTariffs));
            show_success('Зібрано нетхостів: ' . sizeof($this->nethosts) . ' у ось скількох підмережах: ' . sizeof($this->networks));
            show_success('Населених пунктів створено: ' . sizeof($this->allCities));
            show_success('В них ось скільки вулиць: ' . $this->currentStreetId);
            show_success('В них ось скільки будинків: ' . $this->currentBuildId);
            show_warning('При цьому кількість безхатченків: ' . $this->orphans);
            show_error('Пройобано юзерів через криві вхідні дані: ' . $failCount);

            //debarr($this->allAddress);
            //debarr($result);
            //debarr($failedUsers);
            file_put_contents('content/backups/sql/convertador.sql', $result);
        }

        /**
         * Returns upload form
         * 
         * @return string
         */
        public function renderUploadForm() {
            $uploadinputs = wf_HiddenInput('uploadconvertadorks', 'true');
            $uploadinputs .= __('CSV file') . wf_tag('br');
            $uploadinputs .= wf_tag('input', false, '', 'id="fileselector" type="file" name="convertadorcsv"') . wf_tag('br');
            $uploadinputs .= wf_Submit('Upload');
            $uploadform = bs_UploadFormBody('', 'POST', $uploadinputs, 'glamour');
            return ($uploadform);
        }

        /**
         * Process of uploading of raw csv
         * 
         * @return array
         */
        public function csvDoUpload() {
            $result = array();
            $extCheck = true;
            //check file type
            foreach ($_FILES as $file) {
                if ($file['tmp_name'] > '') {
                    if (@!in_array(end(explode(".", strtolower($file['name']))), $this->allowedExtensions)) {
                        $extCheck = false;
                    }
                }
            }

            if ($extCheck) {
                $filename = $_FILES['convertadorcsv']['name'];
                $uploadfile = self::UPLOAD_PATH . $filename;

                if (move_uploaded_file($_FILES['convertadorcsv']['tmp_name'], $uploadfile)) {
                    $fileContent = file_get_contents(self::UPLOAD_PATH . $filename);
                    $fileHash = md5($fileContent);
                    $fileContent = ''; //free some memory

                    $result = array(
                        'filename' => $_FILES['convertadorcsv']['name'],
                        'savedname' => $filename,
                        'hash' => $fileHash
                    );
                } else {
                    show_error(__('Cant upload file to') . ' ' . self::UPLOAD_PATH);
                }
            } else {
                show_error(__('Wrong file type'));
            }
            return ($result);
        }

    }

    $conv = new ConverTador();
    if (!wf_CheckPost(array('uploadconvertadorks'))) {
        show_window(__('Upload'), $conv->renderUploadForm());
    } else {
        $uploadResult = $conv->csvDoUpload();
        if (!empty($uploadResult)) {
            $filename = $conv::UPLOAD_PATH . $uploadResult['savedname'];
            if (file_exists($filename)) {
                $raw = file_get_contents($filename);
                $conv->setCsv($raw);
                $conv->processing();
            }
        }
    }
} else {
    show_error(__('Access denied'));
}
?>
