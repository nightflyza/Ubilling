<?php

set_time_limit(0);

if (cfr('ROOT')) {

    function web_UserGenForm() {
        $alltariffs_raw = zb_TariffsGetAll();
        $alltariffs = array();
        if (!empty($alltariffs_raw)) {
            foreach ($alltariffs_raw as $it => $eachtariff) {
                $alltariffs[$eachtariff['name']] = $eachtariff['name'];
            }
        }

        $inputs = wf_TextInput('gencount', __('Count of users to generate'), '', true);
        $inputs .= wf_Selector('gentariff', $alltariffs, __('Existing tariff for this users'), '', true);
        $inputs .= multinet_service_selector() . ' ' . __('Service for new users') . wf_tag('br');
        $inputs .= wf_CheckInput('fastsqlgen', __('Generate MySQL dump to restore via backups module'), true, false);
        $inputs .= wf_Submit(__('Go!'));
        $result = wf_Form("", "POST", $inputs, 'glamour');
        show_window(__('Sample user generator'), $result);
    }

    web_UserGenForm();


    if (ubRouting::checkPost('gencount')) {
        $altCfg = $ubillingConfig->getAlter();
        $neednum = ubRouting::post('gencount', 'int');
        $lastBuild = simple_query("SELECT * from `build` ORDER BY `id` DESC LIMIT 1");
        $lastBuildId = $lastBuild['id'];
        $serviceID = ubRouting::post('serviceselect', 'int');
        $netID = multinet_get_service_networkid($serviceID);
        $tariff = ubRouting::post('gentariff', 'mres');

        if (!ubRouting::checkPost('fastsqlgen')) {
            //normal user generation via standard stargazer API
            for ($i = 1; $i <= $neednum; $i++) {
                $randomLogin = 'gen_' . zb_rand_string(10);

                $randomPassword = zb_PasswordGenerate($altCfg['PASSWORD_GENERATION_LENGHT']);
                $randomName = zb_GenerateRandomName();
                $randomPhone = rand(111111, 999999);
                $randomMobile = '380' . rand(1111111, 9999999);
                $randomMac = '14:' . '88' . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99);
                $randomApt = $i;
                $randomIp = multinet_get_next_freeip('nethosts', 'ip', $netID);
                $randomFloor = rand(1, 9);
                $randomEntrance = rand(1, 4);

                //registering subroutine
                $billing->createuser($randomLogin);
                $billing->setpassword($randomLogin, $randomPassword);
                $billing->setip($randomLogin, $randomIp);
                zb_AddressCreateApartment($lastBuildId, $randomEntrance, $randomFloor, $randomApt);
                zb_AddressCreateAddress($randomLogin, zb_AddressGetLastid());
                multinet_add_host($netID, $randomIp);
                zb_UserCreateRealName($randomLogin, $randomName);
                zb_UserCreatePhone($randomLogin, $randomPhone, $randomMobile);
                zb_UserCreateContract($randomLogin, '');
                zb_UserCreateEmail($randomLogin, '');
                zb_UserCreateSpeedOverride($randomLogin, 0);
                multinet_change_mac($randomIp, $randomMac);
                multinet_rebuild_all_handlers();
                $billing->settariff($randomLogin, $tariff);
                $billing->setao($randomLogin, '1');
                $billing->setdstat($randomLogin, '1');

                zb_UserRegisterLog($randomLogin);
                log_register("SAMPLE GENERATION OF (" . $randomLogin . ") DONE");
            }
        } else {
            //generating MySQL dump
            $dumpName = 'content/backups/sql/generated_' . date("Y-m-d_H_i_s") . '.sql';
            $dumpData = '';
            $newAptId = zb_AddressGetLastid();
            $allFreeIps = multinet_get_all_free_ip('nethosts', 'ip', $netID);
            $admin = whoami();

            if (sizeof($allFreeIps) >= $neednum) {
                for ($i = 1; $i <= $neednum; $i++) {
                    $newAptId++;
                    $randomLogin = 'gen_' . zb_rand_string(16);
                    $randomPassword = zb_PasswordGenerate($altCfg['PASSWORD_GENERATION_LENGHT']);
                    $randomName = zb_GenerateRandomName();
                    $randomName = ubRouting::filters($randomName, 'mres');
                    $randomPhone = rand(111111, 999999);
                    $randomMobile = '380' . rand(1111111, 9999999);
                    $randomMac = '14:' . '88' . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99) . ':' . rand(10, 99);
                    $randomApt = $i;
                    $randomCash = rand(0, 500);
                    $randomFloor = rand(1, 9);
                    $randomEntrance = rand(1, 4);
                    $nextFreeIpIndex = array_keys($allFreeIps);
                    $randomIp = $allFreeIps[$nextFreeIpIndex[0]];
                    unset($allFreeIps[$nextFreeIpIndex[0]]);

                    //user profile creation
                    $dumpData .= "-- " . $randomLogin . PHP_EOL;
                    $dumpData .= "INSERT INTO `users` (`login`,`Password`,`Passive`,`Down`,`DisabledDetailStat`,`AlwaysOnline`,`Tariff`,`Address`,`Phone`,`Email`,`Note`,`RealName`,`StgGroup`,`Credit`,`TariffChange`,`Userdata0`,`Userdata1`,`Userdata2`,`Userdata3`,`Userdata4`,`Userdata5`,`Userdata6`,`Userdata7`,`Userdata8`,`Userdata9`,`CreditExpire`,`IP`,`D0`,`U0`,`D1`,`U1`,`D2`,`U2`,`D3`,`U3`,`D4`,`U4`,`D5`,`U5`, `D6`, `U6`,`D7`, `U7`, `D8`,`U8`,`D9`,`U9`,`Cash`,`FreeMb`,`LastCashAdd`,`LastCashAddTime`,`PassiveTime`,`LastActivityTime`,`NAS`)VALUES ('" . $randomLogin . "','" . $randomPassword . "','0','0','1','1','" . $tariff . "','','','','','','','0','', '','','','', '', '', '', '','', '', '0','" . $randomIp . "','0','0','0','0','0', '0','0','0', '0','0','0','0','0', '0','0','0', '0', '0', '0', '0', '" . $randomCash . "','0','0', '0','0', '0','');" . PHP_EOL;

                    //apartment creation
                    $dumpData .= "INSERT INTO `apt` (`id`,`buildid`,`entrance`,`floor`,`apt`) VALUES (NULL,'" . $lastBuildId . "','" . $randomEntrance . "','" . $randomFloor . "','" . $randomApt . "');" . PHP_EOL;
                    //apt=>address binding
                    $dumpData .= "INSERT INTO `address` (`id`,`login`,`aptid`) VALUES (NULL, '" . $randomLogin . "','" . $newAptId . "');" . PHP_EOL;
                    //new multinet host creation
                    $dumpData .= "INSERT INTO `nethosts` (`id` ,`ip` ,`mac` ,`netid` ,`option`) VALUES (NULL , '" . $randomIp . "', '" . $randomMac . "', '" . $netID . "', '');" . PHP_EOL;
                    //users real name creation
                    $dumpData .= "INSERT INTO `realname`  (`id`,`login`,`realname`) VALUES   (NULL, '" . $randomLogin . "','" . $randomName . "');" . PHP_EOL;
                    //phone data here
                    $dumpData .= "INSERT INTO `phones`  (`id`,`login`,`phone`,`mobile`)  VALUES  (NULL, '" . $randomLogin . "','" . $randomPhone . "','" . $randomMobile . "');" . PHP_EOL;
                    //empty contract, email, sped overrides here
                    $dumpData .= "INSERT INTO `contracts` (`id`,`login`,`contract`)  VALUES  (NULL, '" . $randomLogin . "','');" . PHP_EOL;
                    $dumpData .= "INSERT INTO `emails`  (`id`,`login`,`email`) VALUES  (NULL, '" . $randomLogin . "','');" . PHP_EOL;
                    $dumpData .= "INSERT INTO `userspeeds` (`id` ,`login` ,`speed`) VALUES (NULL , '" . $randomLogin . "', '0');" . PHP_EOL;
                    //user register log
                    $dumpData .= "INSERT INTO `userreg` (`id` ,`date` ,`admin` ,`login` ,`address`) VALUES (NULL , '" . curdatetime() . "', '" . $admin . "', '" . $randomLogin . "', 'someaddress');" . PHP_EOL;
                    $dumpData .= PHP_EOL;
                }

                //saving dump
                file_put_contents($dumpName, $dumpData);
                show_success($dumpName . ' ' . __('Saved'));
                //debarr($dumpData);
            } else {
                show_error(__('No free IPs enough in selected service'));
            }
        }
    }
} else {
    show_error(__('Permission denied'));
}
