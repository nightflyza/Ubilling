<?php

set_time_limit(0);
/*
 * Ubilling remote API
 * -----------------------------
 * 
 * Format: /?module=remoteapi&key=[ubserial]&action=[action][&param=[parameter]]
 * 
 */



$alterconf = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
if ($alterconf['REMOTEAPI_ENABLED']) {
    if (isset($_GET['key'])) {
        $key = vf($_GET['key']);
        $hostid_q = "SELECT * from `ubstats` WHERE `key`='ubid'";
        $hostid = simple_query($hostid_q);
        if (!empty($hostid)) {
            $serial = $hostid['value'];
            if ($key == $serial) {
                //key ok
                if (isset($_GET['action'])) {

                    /*
                     * reset user action
                     */
                    if ($_GET['action'] == 'reset') {
                        if (isset($_GET['param'])) {
                            $billing->resetuser($_GET['param']);
                            log_register("REMOTEAPI RESET User (" . $_GET['param'] . ")");
                            die('OK:RESET');
                        } else {
                            die('ERROR:GET_NO_PARAM');
                        }
                    }


                    /*
                     * handlersrebuild action
                     */

                    if ($_GET['action'] == 'handlersrebuild') {
                        multinet_rebuild_all_handlers();
                        log_register("REMOTEAPI HANDLERSREBUILD");
                        die('OK:HANDLERSREBUILD');
                    }


                    /*
                     * CaTV fee processing 
                     */

                    if ($_GET['action'] == 'catvfeeprocessing') {
                        $currentYear = date("Y");
                        //previous month charge fee
                        if ($alterconf['CATV_BACK_FEE']) {
                            $currentMonth = date("m");
                            if ($currentMonth == 1) {
                                $currentMonth = 12;
                            } else {
                                $currentMonth = $currentMonth - 1;
                            }
                        } else {
                            $currentMonth = date("m");
                        }

                        if (catv_FeeChargeCheck($currentMonth, $currentYear)) {
                            catv_FeeChargeAllUsers($currentMonth, $currentYear);
                        } else {
                            die('ERROR:ALREADY_CHARGED');
                        }
                        log_register("REMOTEAPI CATVFEEPROCESSING " . $currentMonth . " " . $currentYear);
                        die('OK:CATVFEEPROCESSING');
                    }

                    /*
                     * Virtualservices charge fee
                     */

                    if ($_GET['action'] == 'vserviceschargefee') {
                        if (wf_CheckGet(array('param'))) {
                            if ($_GET['param'] == 'nofrozen') {
                                $vservicesChargeFrozen = false;
                            } else {
                                $vservicesChargeFrozen = true;
                            }
                        } else {
                            $vservicesChargeFrozen = true;
                        }
                        /* debug flags:
                         * 0 - silent
                         * 1 - with debug output
                         * 2 - don`t touch any cash, just testing run
                         */
                        zb_VservicesProcessAll(1, true, $vservicesChargeFrozen);
                        log_register("REMOTEAPI VSERVICE_CHARGE_FEE");
                        die('OK:SERVICE_CHARGE_FEE');
                    }

                    /*
                     * Discount processing
                     */
                    if ($_GET['action'] == 'discountprocessing') {
                        if ($alterconf['DISCOUNTS_ENABLED']) {
                            //default debug=true
                            zb_DiscountProcessPayments(true);
                            die('OK:DISCOUNTS_PROCESSING');
                        } else {
                            die('ERROR:DISCOUNTS_DISABLED');
                        }
                    }

                    /*
                     * Cumulatiove discounts processing
                     */
                    if ($_GET['action'] == 'cudiscounts') {
                        if ($alterconf['CUD_ENABLED']) {
                            $discounts = new CumulativeDiscounts();
                            $discounts->processDiscounts();
                            die('OK:CUDISCOUNTS');
                        } else {
                            die('ERROR:CUDISCOUNTS_DISABLED');
                        }
                    }

                    /*
                     * Crime And Punishment processing
                     */
                    if ($_GET['action'] == 'crimeandpunishment') {
                        if ($alterconf['CAP_ENABLED']) {
                            $dostoevsky = new CrimeAndPunishment();
                            $dostoevsky->processing();
                            die('OK:CRIMEANDPUNISHMENT');
                        } else {
                            die('ERROR:CRIMEANDPUNISHMENT_DISABLED');
                        }
                    }

                    /*
                     * database backup
                     */
                    if ($_GET['action'] == 'backupdb') {
                        if ($alterconf['MYSQLDUMP_PATH']) {
                            $backpath = zb_backup_database(true);
                        } else {
                            $backpath = zb_backup_tables('*', true);
                        }
                        die('OK:BACKUPDB ' . $backpath);
                    }

                    /*
                     * database cleanup
                     */
                    if ($_GET['action'] == 'autocleandb') {
                        $cleancount = zb_DBCleanupAutoClean();
                        die('OK:AUTOCLEANDB ' . $cleancount);
                    }

                    /*
                     * UHW brute attempts cleanup
                     */
                    if ($_GET['action'] == 'uhwbrutecleanup') {
                        $uhw = new UHW();
                        $uhw->flushAllBrute();
                        die('OK:UHWBRUTECLEANUP');
                    }

                    /*
                     * SNMP switch polling
                     */
                    if ($_GET['action'] == 'swpoll') {
                        $allDevices = sp_SnmpGetAllDevices();
                        $allTemplates = sp_SnmpGetAllModelTemplates();
                        $allTemplatesAssoc = sp_SnmpGetModelTemplatesAssoc();
                        $allusermacs = zb_UserGetAllMACs();
                        $alladdress = zb_AddressGetFullCityaddresslist();
                        $alldeadswitches = zb_SwitchesGetAllDead();
                        $swpollLogData = '';
                        $swpollLogPath = 'exports/swpolldata.log';
                        if (!empty($allDevices)) {
                            //start new polling
                            file_put_contents($swpollLogPath, date("Y-m-d H:i:s") . ' [SWPOLLSTART]' . "\n", FILE_APPEND);
                            foreach ($allDevices as $io => $eachDevice) {
                                $swpollLogData = '';
                                if (!empty($allTemplatesAssoc)) {
                                    if (isset($allTemplatesAssoc[$eachDevice['modelid']])) {
                                        //dont poll dead devices
                                        if (!isset($alldeadswitches[$eachDevice['ip']])) {
                                            //dont poll NP devices - commented due testing
                                            // if (!ispos($eachDevice['desc'], 'NP')) {
                                            $deviceTemplate = $allTemplatesAssoc[$eachDevice['modelid']];
                                            sp_SnmpPollDevice($eachDevice['ip'], $eachDevice['snmp'], $allTemplates, $deviceTemplate, $allusermacs, $alladdress, true);
                                            $swpollLogData = date("Y-m-d H:i:s") . ' ' . $eachDevice['ip'] . ' [OK]' . "\n";
                                            print($swpollLogData);
//                                            } else {
//                                                $swpollLogData = date("Y-m-d H:i:s") . ' ' . $eachDevice['ip'] . ' [FAIL] SWITCH NP' . "\n";
//                                                print($swpollLogData);
//                                            }
                                        } else {
                                            $swpollLogData = date("Y-m-d H:i:s") . ' ' . $eachDevice['ip'] . ' [FAIL] SWITCH DEAD' . "\n";
                                            print($swpollLogData);
                                        }
                                    } else {
                                        $swpollLogData = date("Y-m-d H:i:s") . ' ' . $eachDevice['ip'] . ' [FAIL] NO TEMPLATE' . "\n";
                                        print($swpollLogData);
                                    }
                                }

                                //put some log data about polling
                                file_put_contents($swpollLogPath, $swpollLogData, FILE_APPEND);
                            }
                            die('OK:SWPOLL');
                        } else {
                            die('ERROR:SWPOLL_NODEVICES');
                        }
                    }

                    if ($_GET['action'] == 'oltpoll') {
                        if ($alterconf['PON_ENABLED']) {
                            $pony = new PONizer();
                            $pony->oltDevicesPolling();
                            die('OK:OLTPOLL');
                        } else {
                            die('ERROR:PON_DISABLED');
                        }
                    }

                    /*
                     * Switch ICMP reping to fill dead cache
                     */
                    if ($_GET['action'] == 'swping') {
                        $currenttime = time();
                        $deadSwitches = zb_SwitchesRepingAll();
                        zb_StorageSet('SWPINGTIME', $currenttime);
                        //store dead switches log data
                        if (!empty($deadSwitches)) {
                            zb_SwitchesDeadLog($currenttime, $deadSwitches);
                        }
                        die('OK:SWPING');
                    }

                    /*
                     * networks fast scan with nmap
                     */
                    if ($_GET['action'] == 'fullhostscan') {
                        $fullScanResult = '';
                        $nmapPath = $alterconf['NMAP_PATH'];
                        $allMultinetNetworks_q = "select * from `networks`";
                        $allMultinetNetworks = simple_queryall($allMultinetNetworks_q);
                        if (!empty($allMultinetNetworks)) {
                            foreach ($allMultinetNetworks as $ig => $eachsubnet) {
                                $nmapCommand = $nmapPath . ' -sP -n ' . $eachsubnet['desc'];
                                $fullScanResult.=shell_exec($nmapCommand);
                                print($eachsubnet['desc'] . ' :' . date("Y-m-d H:i:s") . ':SCANNED' . "\n");
                            }
                        }
                        //additional parameters
                        if (isset($_GET['param'])) {
                            if ($_GET['param'] == 'traffdiff') {
                                $fullScanResult.='== Traffic analysis diff here ==' . "\n";
                                $traff_q = "SELECT `login`,`IP`, (`U0`+`U1`+`U2`+`U3`+`U4`+`U5`+`U6`+`U7`+`U8`+`U9`) as `traff`  from `users`";
                                $curTraff = simple_queryall($traff_q);
                                $prevTraff = array();
                                $diffCurr = array();
                                $diffPrev = array();
                                if (!file_exists('exports/prevtraff')) {
                                    $prevTraff = $curTraff;
                                    $savePrev = serialize($prevTraff);
                                    file_put_contents('exports/prevtraff', $savePrev);
                                } else {
                                    $prevTraff_raw = file_get_contents('exports/prevtraff');
                                    $prevTraff = unserialize($prevTraff_raw);
                                }


                                //filling diff arrays
                                if (!empty($curTraff)) {
                                    foreach ($curTraff as $itc => $eachdiff) {
                                        $diffCurr[$eachdiff['login']]['IP'] = $eachdiff['IP'];
                                        $diffCurr[$eachdiff['login']]['traff'] = $eachdiff['traff'];
                                    }
                                }

                                if (!empty($prevTraff)) {
                                    foreach ($prevTraff as $itp => $eachprev) {
                                        $diffPrev[$eachprev['login']]['IP'] = $eachprev['IP'];
                                        $diffPrev[$eachprev['login']]['traff'] = $eachprev['traff'];
                                    }
                                }
                                //comparing arrays
                                if (!empty($diffCurr)) {
                                    foreach ($diffCurr as $diffLogin => $diffData) {
                                        if (isset($diffPrev[$diffLogin])) {
                                            if ($diffData['traff'] != $diffPrev[$diffLogin]['traff']) {
                                                $fullScanResult.='login ' . $diffLogin . ' ' . $diffData['IP'] . ' looks like alive' . "\n";
                                            }
                                        }
                                    }
                                }

                                //writing to cache
                                $savePrev = serialize($curTraff);
                                file_put_contents('exports/prevtraff', $savePrev);
                            }
                        }
                        //saving scan data
                        file_put_contents('exports/nmaphostscan', $fullScanResult);

                        //postprocessing DN data
                        if ($alterconf['DN_FULLHOSTSCAN']) {
                            $activeIps = array();
                            if (file_exists("exports/nmaphostscan")) {
                                $nmapData = file_get_contents("exports/nmaphostscan");
                                $nmapData = explodeRows($nmapData);
                                if (!empty($nmapData)) {
                                    foreach ($nmapData as $ic => $eachnmaphost) {
                                        $zhost = zb_ExtractIpAddress($eachnmaphost);
                                        if ($zhost) {
                                            $activeIps[$zhost] = $zhost;
                                        }
                                    }
                                }
                            }

                            if (!empty($activeIps)) {
                                if (file_exists(DATA_PATH . "dn")) {
                                    //directory clanup
                                    $oldDnData = rcms_scandir(DATA_PATH . "dn/");
                                    if (!empty($oldDnData)) {
                                        foreach ($oldDnData as $deleteFile) {
                                            unlink(DATA_PATH . "dn/" . $deleteFile);
                                        }
                                    }
                                    //store new DN data
                                    $allUserIps = zb_UserGetAllIPs();
                                    $allUserIps = array_flip($allUserIps);
                                    foreach ($activeIps as $ix => $aip) {
                                        if (isset($allUserIps[$aip])) {
                                            file_put_contents(DATA_PATH . "dn/" . $allUserIps[$aip], 'alive');
                                        }
                                    }
                                } else {
                                    die('FAIL:NO_CONTENT_DN_EXISTS');
                                }
                            }
                        }

                        //updating build users state cache
                        if ($alterconf['SWYMAP_ENABLED']) {
                            $updateBuilCache = um_MapDrawBuilds();
                            print('OK:USERBUILDCACHE');
                        }

                        die('OK:FULLHOSTSCAN');
                    }

                    /*
                     * users data cache rebuild for external scripts
                     */
                    if ($_GET['action'] == 'rebuilduserdatacache') {
                        $cacheAddressArr = zb_AddressGetFulladdresslist();
                        $cacheAddressArr = serialize($cacheAddressArr);
                        $cacheIpsArr = zb_UserGetAllIPs();
                        $cacheIpsArr = serialize($cacheIpsArr);
                        $cacheMacArr = zb_UserGetAllIpMACs();
                        $cacheMacArr = serialize($cacheMacArr);
                        file_put_contents('exports/cache_address', $cacheAddressArr);
                        file_put_contents('exports/cache_ips', $cacheIpsArr);
                        file_put_contents('exports/cache_mac', $cacheMacArr);
                        die('OK:REBUILDUSERDATACACHE');
                    }

                    /*
                     * auto freezing call
                     */

                    if ($_GET['action'] == 'autofreeze') {
                        if (isset($alterconf['AUTOFREEZE_CASH_LIMIT'])) {
                            $afCashLimit = $alterconf['AUTOFREEZE_CASH_LIMIT'];
                            $autoFreezeQuery = "SELECT * from `users` WHERE `Passive`='0' AND `Cash`<='" . $afCashLimit . "' AND `Credit`='0';";
                            $allUsersToFreeze = simple_queryall($autoFreezeQuery);
                            $freezeCount = 0;
                            //optional zbs SC check
                            if (wf_CheckGet(array('param'))) {
                                if ($_GET['param'] == 'nocredit') {
                                    $creditZbsCheck = true;
                                    $creditZbsUsers = zb_CreditLogGetAll();
                                } else {
                                    $creditZbsCheck = false;
                                    $creditZbsUsers = array();
                                }
                            } else {
                                $creditZbsCheck = false;
                                $creditZbsUsers = array();
                            }
                            if (!empty($allUsersToFreeze)) {
                                foreach ($allUsersToFreeze as $efuidx => $eachfreezeuser) {


                                    $freezeLogin = $eachfreezeuser['login'];
                                    $freezeCash = $eachfreezeuser['Cash'];
                                    //zbs credit check
                                    if ($creditZbsCheck) {
                                        if (!isset($creditZbsUsers[$freezeLogin])) {
                                            $billing->setpassive($freezeLogin, '1');
                                            log_register('AUTOFREEZE (' . $freezeLogin . ') ON BALANCE ' . $freezeCash);
                                            $freezeCount++;
                                        } else {
                                            log_register('AUTOFREEZE (' . $freezeLogin . ') ON BALANCE ' . $freezeCash . ' SKIP BY ZBSSC');
                                        }
                                    } else {
                                        //normal freezing
                                        $billing->setpassive($freezeLogin, '1');
                                        log_register('AUTOFREEZE (' . $freezeLogin . ') ON BALANCE ' . $freezeCash);
                                        $freezeCount++;
                                    }
                                }
                                log_register('AUTOFREEZE DONE COUNT `' . $freezeCount . '`');
                                die('OK:AUTOFREEZE');
                            } else {
                                die('OK:NO_USERS_TO_AUTOFREEZE');
                            }
                        } else {
                            die('ERROR:NO_AUTOFREEZE_CASH_LIMIT');
                        }
                    }

                    /*
                     * auto freezing call which use AUTOFREEZE_CASH_LIMIT as month count
                     */
                    if ($_GET['action'] == 'autofreezemonth') {
                        if (isset($alterconf['AUTOFREEZE_CASH_LIMIT'])) {
                            $tariffPrices = zb_TariffGetPricesAll();
                            $tariffPriceMultiplier = $alterconf['AUTOFREEZE_CASH_LIMIT'];
                            $autoFreezeQuery = "SELECT * from `users` WHERE `Passive`='0' AND `Credit`='0';";
                            $allUsersToFreeze = simple_queryall($autoFreezeQuery);
                            $freezeCount = 0;
                            //optional zbs SC check
                            if (wf_CheckGet(array('param'))) {
                                if ($_GET['param'] == 'nocredit') {
                                    $creditZbsCheck = true;
                                    $creditZbsUsers = zb_CreditLogGetAll();
                                } else {
                                    $creditZbsCheck = false;
                                    $creditZbsUsers = array();
                                }
                            } else {
                                $creditZbsCheck = false;
                                $creditZbsUsers = array();
                            }
                            if (!empty($allUsersToFreeze)) {
                                foreach ($allUsersToFreeze as $efuidx => $eachfreezeuser) {
                                    $freezeLogin = $eachfreezeuser['login'];
                                    $freezeCash = $eachfreezeuser['Cash'];
                                    $freezeUserTariff = $eachfreezeuser['Tariff'];
                                    if (isset($tariffPrices[$freezeUserTariff])) {
                                        $freezeUserTariffPrice = $tariffPrices[$freezeUserTariff];
                                        $tariffFreezeLimit = '-' . ($freezeUserTariffPrice * $tariffPriceMultiplier);
                                        if (($freezeCash <= $tariffFreezeLimit) AND ( $freezeUserTariffPrice != 0)) {
                                            //zbs credit check  
                                            if ($creditZbsCheck) {
                                                if (!isset($creditZbsUsers[$freezeLogin])) {
                                                    $billing->setpassive($freezeLogin, '1');
                                                    log_register('AUTOFREEZE (' . $freezeLogin . ') ON BALANCE ' . $freezeCash);
                                                    $freezeCount++;
                                                } else {
                                                    log_register('AUTOFREEZE (' . $freezeLogin . ') ON BALANCE ' . $freezeCash . ' SKIP BY ZBSSC');
                                                }
                                            } else {
                                                //normal freezing     
                                                $billing->setpassive($freezeLogin, '1');
                                                log_register('AUTOFREEZE (' . $freezeLogin . ') ON BALANCE ' . $freezeCash);
                                                $freezeCount++;
                                            }
                                        }
                                    }
                                }
                                log_register('AUTOFREEZE DONE COUNT `' . $freezeCount . '`');
                                die('OK:AUTOFREEZE');
                            } else {
                                die('OK:NO_USERS_TO_AUTOFREEZE');
                            }
                        } else {
                            die('ERROR:NO_AUTOFREEZE_CASH_LIMIT');
                        }
                    }


                    /*
                     * Watchdog tasks processing
                     */
                    if ($_GET['action'] == 'watchdog') {
                        if ($alterconf['WATCHDOG_ENABLED']) {
                            $runWatchDog = new WatchDog();
                            $runWatchDog->processTask();
                            die('OK:WATCHDOG');
                        } else {
                            die('ERROR:NO_WATCHDOG_ENABLED');
                        }
                    }

                    /*
                     * UKV charge fee processing
                     */
                    if ($_GET['action'] == 'ukvfeeprocessing') {
                        if ($alterconf['UKV_ENABLED']) {
                            $ukvApiRun = new UkvSystem();
                            $ukvFee = $ukvApiRun->feeChargeAll();
                            die('OK:UKVFEEPROCESSING:' . $ukvFee);
                        } else {
                            die('ERROR:NO_UKV_ENABLED');
                        }
                    }

                    /**
                     * Registry of banned sites processing
                     */
                    if ($_GET['action'] == 'rbs') {
                        $object = new RosKomNadzor();
                        $object->run();
                        die(0);
                    }

                    /*
                     * Switches coverage map
                     */
                    if ($_GET['action'] == 'switchescoverage') {
                        $ymconf = rcms_parse_ini_file(CONFIG_PATH . "ymaps.ini");
                        $ym_center = $ymconf['CENTER'];
                        $ym_zoom = $ymconf['ZOOM'];
                        $ym_type = $ymconf['TYPE'];
                        $ym_lang = $ymconf['LANG'];
                        $area = '';
                        if (wf_CheckGet(array('param'))) {
                            $mapDimensions = explode('x', $_GET['param']);
                        } else {
                            $mapDimensions[0] = '1000';
                            $mapDimensions[1] = '800';
                        }
                        $switchesCoverage = sm_MapDrawSwitchesCoverage();
                        $coverageSwMap = wf_tag('div', false, '', 'id="swmap" style="width: ' . $mapDimensions[0] . 'px; height:' . $mapDimensions[1] . 'px;"');
                        $coverageSwMap.=wf_tag('div', true);
                        $coverageSwMap.= sm_MapInitBasic($ym_center, $ym_zoom, $ym_type, $area . $switchesCoverage, '', $ym_lang);
                        die($coverageSwMap);
                    }

                    /*
                     * GlobalSearch cache rebuild
                     */
                    if ($_GET['action'] == 'rebuildglscache') {
                        $globalSearch = new GlobalSearch();
                        $globalSearch->ajaxCallback(true);
                        die('OK:REBUILDGLSCACHE');
                    }

                    /*
                     * send sms queue to remind users about payments
                     */
                    if ($_GET['action'] == 'reminder') {
                        if ($alterconf['REMINDER_ENABLED']) {
                            $sms = new Reminder();
                            $sms->RemindUser();
                            die('OK:SEND REMIND SMS');
                        } else {
                            die('ERROR:REMINDER DISABLED');
                        }
                    }

                    /*
                     * friendship processing
                     */
                    if ($_GET['action'] == 'friendshipdaily') {
                        if ($alterconf['FRIENDSHIP_ENABLED']) {
                            $friends = new FriendshipIsMagic();
                            $friends->friendsDailyProcessing();
                            die('OK:FRIENDSHIP');
                        } else {
                            die('ERROR:FRIENDSHIP DISABLED');
                        }
                    }

                    /*
                     * Per month freezing fees
                     */
                    if ($_GET['action'] == 'freezemonth') {
                        $money = new FundsFlow();
                        $money->runDataLoders();
                        $money->makeFreezeMonthFee();
                        die('OK:FREEZEMONTH');
                    }

                    /**
                     * UserSide get API handling
                     */
                    if ($_GET['action'] == 'userside') {
                        if ($alterconf['USERSIDE_API']) {
                            $usersideapi = new UserSideApi();
                            $usersideapi->catchRequest();
                        } else {
                            die('ERROR:NO_USERSIDE_API_ENABLED');
                        }
                    }

                    if ($_GET['action'] == 'writevlanmachistory') {
                        if ($alterconf['VLANMACHISTORY']) {
                            $history = new VlanMacHistory;
                            $history->WriteVlanMacData();
                            die('OK:WRITING NEW MACS');
                        } else {
                            die('ERROR:NO_VLAN_MAC_HISTORY ENABLED');
                        }
                    }

                    //deal with it delayed tasks processing
                    if ($_GET['action'] == 'dealwithit') {
                        if ($alterconf['DEALWITHIT_ENABLED']) {
                            $dealWithIt = new DealWithIt();
                            $dealWithIt->tasksProcessing();
                            die('OK:DEALWITHIT');
                        } else {
                            die('ERROR:DEALWITHIT DISABLED');
                        }
                    }

                    //Megogo userstats control options
                    if ($_GET['action'] == 'mgcontrol') {
                        if ($alterconf['MG_ENABLED']) {
                            if (wf_CheckGet(array('param', 'tariffid', 'userlogin'))) {

                                if ($_GET['param'] == 'subscribe') {
                                    $mgIface = new MegogoInterface();
                                    $mgSubResult = $mgIface->createSubscribtion($_GET['userlogin'], $_GET['tariffid']);
                                    die($mgSubResult);
                                }

                                if ($_GET['param'] == 'unsubscribe') {
                                    $mgIface = new MegogoInterface();
                                    $mgUnsubResult = $mgIface->scheduleUnsubscribe($_GET['userlogin'], $_GET['tariffid']);
                                    die($mgUnsubResult);
                                }
                            }

                            if (wf_CheckGet(array('param', 'userlogin'))) {
                                if ($_GET['param'] == 'auth') {
                                    $mgApi = new MegogoApi();
                                    $authUrlData = $mgApi->authCode($_GET['userlogin']);
                                    die($authUrlData);
                                }
                            }
                        } else {
                            die('ERROR: MEGOGO DISABLED');
                        }
                    }


                    //Megogo schedule processing
                    if ($_GET['action'] == 'mgqueue') {
                        if ($alterconf['MG_ENABLED']) {
                            $mgIface = new MegogoInterface();
                            $mgQueueProcessingResult = $mgIface->scheduleProcessing();
                            die($mgQueueProcessingResult);
                        } else {
                            die('ERROR: MEGOGO DISABLED');
                        }
                    }

                    //Megogo fee processing (monthly)
                    if ($_GET['action'] == 'mgprocessing') {
                        if ($alterconf['MG_ENABLED']) {
                            $mgIface = new MegogoInterface();
                            $mgFeeProcessingResult = $mgIface->subscriptionFeeProcessing();
                            die($mgFeeProcessingResult);
                        } else {
                            die('ERROR: MEGOGO DISABLED');
                        }
                    }

                    //existential horse
                    if ($_GET['action'] == 'exhorse') {
                        if ($alterconf['EXHORSE_ENABLED']) {
                            if (date("d")==date("t")) {
                                $exhorse = new ExistentialHorse();
                                $exhorse->runHorse();
                            }
                            die('OK: EXHORSE');
                        } else {
                            die('ERROR: EXHORSE DISABLED');
                        }
                    }



                    ////
                    //// End of actions
                    ////

                    /*
                     * Exeptions handling
                     */
                } else {
                    die('ERROR:GET_NO_ACTION');
                }
            } else {
                die('ERROR:GET_WRONG_KEY');
            }
        } else {
            die('ERROR:NO_UBSERIAL_EXISTS');
        }
    } else {
        /*
         * Ubilling instance identify handler
         */
        if (isset($_GET['action'])) {
            if ($_GET['action'] == 'identify') {
                $idhostid_q = "SELECT * from `ubstats` WHERE `key`='ubid'";
                $idhostid = simple_query($idhostid_q);
                if (!empty($idhostid)) {
                    $idserial = $idhostid['value'];
                    die(substr($idserial, -4));
                } else {
                    die('ERROR:NO_UB_SERIAL_GENERATED');
                }
            }
        } else {
            die('ERROR:GET_NO_KEY');
        }
    }
} else {
    die('ERROR:API_DISABLED');
}
?>
