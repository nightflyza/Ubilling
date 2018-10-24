<?php

//just dummy module for testing purposes
error_reporting(E_ALL);

class ConverTador {

    protected $ignoreLines = 1;
    protected $allUserData = array();
    protected $allCities = array();
    protected $allBuilds = array();
    protected $allApt = array();
    protected $allAddress = array();
    protected $allTariffs = array();
    protected $delimiter = ";";
    protected $normalLine = 29;
    protected $rawCsv = '';
    protected $tariffPrefix = 'Speed-';
    protected $offsets = array();
    protected $grayIpNet = '172.16';
    protected $whiteIpNet = '195.162.80';
    protected $nethosts = array();
    protected $currentIp = 1;
    protected $currentNet = 1;
    protected $mobileLen = 6;
    protected $networks = array();
    protected $usedMacs = array();

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
                $this->currentIp = 1;
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
//        if (zb_checkMoney($cash)) {
//            $result = $cash;
//        } else {
//            $result = 502;
//        }
//        
        $result = trim($cash);

        return ($result);
    }

    public function processing() {
        $count = 0;
        $failCount = 0;
        $okCount = 0;
        $failCashCount = 0;

        $result = '';
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
                            //reglog
                            $regTimeStamp = strtotime($eachLine[$this->offsets['regdate']]);
                            $regDateTime = date("Y-m-d", $regTimeStamp) . ' 10:42:42';
                            $result.= "INSERT INTO `userreg` (`id` ,`date` ,`admin` ,`login` ,`address`) "
                                    . "VALUES (NULL , '" . $regDateTime . "', 'converter', '" . $userLogin . "', 'Пропили');" . "\n";
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
            }
        }


        show_info('Всього рядків на вході: ' . ($count - $this->ignoreLines));
        show_success('Нормально зібрано юзерів: ' . $okCount);
        show_success('Нафігачено тарифів зі швидкостями: ' . sizeof($this->allTariffs));
        show_success('Зібрано нетхостів: ' . sizeof($this->nethosts) . ' у ось скількох підмережах: ' . sizeof($this->networks));
        //show_warning('Криві вхідні бабки (502): ' . $failCashCount);
        show_error('Пройобано юзерів через криві вхідні дані: ' . $failCount);


        debarr($result);
        file_put_contents('content/backups/sql/convertador.sql', $result);
    }

}

$raw = file_get_contents('exports/all2.csv');
$conv = new ConverTador();
$conv->setCsv($raw);
$conv->processing();
?>
