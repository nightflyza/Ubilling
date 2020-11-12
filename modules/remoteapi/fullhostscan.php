<?php

/*
 * networks fast scan with nmap
 */
if (ubRouting::get('action') == 'fullhostscan') {
    $fullScanResult = '';

    if (!ubRouting::checkGet('nn')) {
        $nmapPath = $alterconf['NMAP_PATH'];
        $allMultinetNetworks_q = "select * from `networks`";
        $allMultinetNetworks = simple_queryall($allMultinetNetworks_q);
        if (!empty($allMultinetNetworks)) {
            foreach ($allMultinetNetworks as $ig => $eachsubnet) {
                $nmapCommand = $nmapPath . ' -sP -n ' . $eachsubnet['desc'];
                $fullScanResult .= shell_exec($nmapCommand);
                print($eachsubnet['desc'] . ' :' . date("Y-m-d H:i:s") . ':SCANNED' . "\n");
            }
        }
    }


    //additional parameters
    if (ubRouting::checkGet('param')) {
        if (ubRouting::get('param') == 'traffdiff') {
            $fullScanResult .= '== Traffic analysis diff here ==' . "\n";
            $traff_q = "SELECT `login`,`IP`, (`U0`+`U1`+`U2`+`U3`+`U4`+`U5`+`U6`+`U7`+`U8`+`U9`) as `traff`  from `users`";
            $curTraff = simple_queryall($traff_q);
            $prevTraff = array();
            $diffCurr = array();
            $diffPrev = array();

            //mixing ishimura aggregated traffic
            if (@$alterconf['ISHIMURA_ENABLED']) {
                $ishimuraOption = MultiGen::OPTION_ISHIMURA;
                $ishimuraTable = MultiGen::NAS_ISHIMURA;
                $additionalTraffic = array();
                if ($alterconf[$ishimuraOption]) {
                    $query_hideki = "SELECT `login`,`D0`,`U0` from `" . $ishimuraTable . "` WHERE `month`='" . date("n") . "' AND `year`='" . curyear() . "'";
                    $dataHideki = simple_queryall($query_hideki);
                    if (!empty($dataHideki)) {
                        foreach ($dataHideki as $io => $each) {
                            $additionalTraffic[$each['login']] = $each['D0'] + $each['U0'];
                        }
                    }

                    if (!empty($curTraff) AND ! empty($additionalTraffic)) {
                        foreach ($curTraff as $io => $each) {
                            if (isset($additionalTraffic[$each['login']])) {
                                $curTraff[$io]['traff'] += $additionalTraffic[$each['login']];
                            }
                        }
                    }
                }
            }
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
                            $fullScanResult .= 'login ' . $diffLogin . ' ' . $diffData['IP'] . ' looks like alive' . "\n";
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

        //renew DN data
        if (file_exists(DATA_PATH . "dn")) {
            //directory clanup
            $oldDnData = rcms_scandir(DATA_PATH . "dn/");
            if (!empty($oldDnData)) {
                foreach ($oldDnData as $deleteFile) {
                    unlink(DATA_PATH . "dn/" . $deleteFile);
                }
            }

            //store new DN data
            if (!empty($activeIps)) {
                $allUserIps = zb_UserGetAllIPs();
                $allUserIps = array_flip($allUserIps);
                foreach ($activeIps as $ix => $aip) {
                    if (isset($allUserIps[$aip])) {
                        file_put_contents(DATA_PATH . "dn/" . $allUserIps[$aip], 'alive');
                    }
                }
            }
        } else {
            die('FAIL:NO_CONTENT_DN_EXISTS');
        }
    }

    //updating build users state cache
    if ($alterconf['SWYMAP_ENABLED']) {
        $updateBuilCache = um_MapDrawBuilds();
        print('OK:USERBUILDCACHE');
    }

    die('OK:FULLHOSTSCAN');
}
