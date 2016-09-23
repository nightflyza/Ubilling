<?php

$altcfg = rcms_parse_ini_file(CONFIG_PATH . "alter.ini");
if ($altcfg['ASKOZIA_ENABLED']) {



    function zb_AskoziaGetNumAliases() {
        $result = array();
        $rawAliases = zb_StorageGet('ASKOZIAPBX_NUMALIAS');
        if (empty($rawAliases)) {
            $newAliasses = serialize($result);
            $newAliasses = base64_encode($newAliasses);
            zb_StorageSet('ASKOZIAPBX_NUMALIAS', $newAliasses);
        } else {
            $readAlias = base64_decode($rawAliases);
            $readAlias = unserialize($readAlias);
            $result = $readAlias;
        }
        return ($result);
    }

    function zb_AskoziaGetConf() {
        $result = array();
        $emptyArray = array();
        //getting url
        $url = zb_StorageGet('ASKOZIAPBX_URL');
        if (empty($url)) {
            $url = 'http://sip.isp/';
            zb_StorageSet('ASKOZIAPBX_URL', $url);
        }
        //getting login
        $login = zb_StorageGet('ASKOZIAPBX_LOGIN');
        if (empty($login)) {
            $login = 'admin';
            zb_StorageSet('ASKOZIAPBX_LOGIN', $login);
        }
        //getting password
        $password = zb_StorageGet('ASKOZIAPBX_PASSWORD');
        if (empty($password)) {
            $password = 'askozia';
            zb_StorageSet('ASKOZIAPBX_PASSWORD', $password);
        }
        //getting caching time
        $cache = zb_StorageGet('ASKOZIAPBX_CACHETIME');
        if (empty($cache)) {
            $cache = '1';
            zb_StorageSet('ASKOZIAPBX_CACHETIME', $cache);
        }

        $result['url'] = $url;
        $result['login'] = $login;
        $result['password'] = $password;
        $result['cachetime'] = $cache;
        return ($result);
    }

    function zb_AskoziaFormatTime($seconds) {
        $init = $seconds;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds / 60) % 60);
        $seconds = $seconds % 60;

        if ($init < 3600) {
            //less than 1 hour
            if ($init < 60) {
                //less than minute
                $result = $seconds . ' ' . __('sec.');
            } else {
                //more than one minute
                $result = $minutes . ' ' . __('minutes') . ' ' . $seconds . ' ' . __('seconds');
            }
        } else {
            //more than hour
            $result = $hours . ' ' . __('hour') . ' ' . $minutes . ' ' . __('minutes') . ' ' . $seconds . ' ' . __('seconds');
        }
        return ($result);
    }

    function zb_AskoziaGetNumAlias($number) {
        global $numAliases;

        if (!empty($numAliases)) {
            if (isset($numAliases[$number])) {
                return($number . ' - ' . $numAliases[$number]);
            } else {
                return ($number);
            }
        } else {
            return ($number);
        }
    }

    function zb_AskoziaCheckPrefix($prefix, $callerid) {
        if (substr($callerid, 0, 1) == $prefix) {
            return (true);
        } else {
            return (false);
        }
    }

    function zb_AskoziaParseCallHistory($data) {
        global $altcfg;
        $normalData = array();
        $callersData = array();
        $data = explodeRows($data);
        if (!empty($data)) {
            foreach ($data as $eachline) {
                $explode = explode(';', $eachline); //in 2.2.8 delimiter changed from ," to ;
                if (!empty($eachline)) {
                    $normalData[] = str_replace('"', '', $explode);
                }
            }
        }

        //custom caller options
        if (isset($altcfg['ASKOZIA_CUSTOM'])) {
            if (!empty($altcfg['ASKOZIA_CUSTOM'])) {
                // 0 - internal peers
                // 1 - external gateways
                // 2 - group prefix
                // 3 - parking
                $customCfg = explode(',', $altcfg['ASKOZIA_CUSTOM']);
            } else {
                $customCfg = array();
            }
        } else {
            $customCfg = array();
        }

        if (!empty($normalData)) {
            $totalTime = 0;
            $callsCounter = 0;
            $answerCounter = 0;
            $noAnswerCounter = 0;
            $chartData = array();

            $cells = wf_TableCell('#');
            $cells.= wf_TableCell(__('Time'));
            $cells.= wf_TableCell(__('From'));
            $cells.= wf_TableCell(__('To'));
            $cells.= wf_TableCell(__('Picked up'));
            $cells.= wf_TableCell(__('Type'));
            $cells.= wf_TableCell(__('Status'));
            $cells.= wf_TableCell(__('Talk time'));

            $rows = wf_TableRow($cells, 'row1');

            foreach ($normalData as $io => $each) {
                //fix parsing for askozia 2.2.8
                if ($each[0] != 'accountcode') {
                    $callsCounter++;
                    $debugData = wf_tag('pre') . print_r($each, true) . wf_tag('pre', true);

                    $startTime = explode(' ', $each[9]);
                    @$startDate = $startTime[0];
                    @$startTime = $startTime[1];
                    @$startHour = date("H:00:00", strtotime($startTime));
                    $endTime = explode(' ', $each[11]);
                    @$endTime = $endTime[1];
                    $answerTime = explode(' ', $each[10]);
                    @$answerTime = $answerTime[1];
                    $tmpStats = __('Taken up the phone') . ': ' . $answerTime . "\n";
                    $tmpStats.=__('End of call') . ': ' . $endTime;
                    $sessionTimeStats = wf_tag('abbr', false, '', 'title="' . $tmpStats . '"');
                    $sessionTimeStats.=$startTime;
                    $sessionTimeStats.=wf_tag('abbr', true);
                    $callDirection = '';
                    if ($each[16] == 'outbound') {
                        $toNumber = $each[2];
                        $callDirection = wf_img('skins/calls/outgoing.png') . ' ';
                    } else {
                        $toNumber = $each[18];
                        $callDirection = wf_img('skins/calls/incoming.png') . ' ';
                    }

                    $cells = wf_TableCell(wf_modal($callsCounter, $callsCounter, $debugData, '', '500', '600'), '', '', 'sorttable_customkey="' . $callsCounter . '"');
                    $cells.= wf_TableCell($callDirection . $sessionTimeStats, '', '', 'sorttable_customkey="' . strtotime($each[9]) . '"');
                    $cells.= wf_TableCell(zb_AskoziaGetNumAlias($each[1]));
                    $cells.= wf_TableCell(zb_AskoziaGetNumAlias($toNumber));
                    $receiveCid = '';
                    if (!empty($each[6])) {
                        $tmpRcid = explode('-', $each[6]);
                        @$receiveCid = vf($tmpRcid[0], 3);
                    }
                    $cells.= wf_TableCell(zb_AskoziaGetNumAlias($receiveCid));

                    $CallType = __('Dial');
                    if (ispos($each[3], 'internal-caller-transfer')) {
                        $CallType = __('Call transfer');
                    }


                    if (ispos($each[7], 'VoiceMail')) {
                        $CallType = __('Voice mail');
                    }

                    $cells.= wf_TableCell($CallType);

                    $callStatus = $each[14];
                    $statusIcon = '';

                    if (ispos($each[14], 'ANSWERED')) {
                        $callStatus = __('Answered');
                        $statusIcon = wf_img('skins/calls/phone_green.png');
                        $answerCounter++;
                        if (isset($chartData[$startDate . ' ' . $startHour]['answered'])) {
                            $chartData[$startDate . ' ' . $startHour]['answered'] ++;
                        } else {
                            $chartData[$startDate . ' ' . $startHour]['answered'] = 1;
                        }
                    }
                    if (ispos($each[14], 'NO ANSWER')) {
                        $callStatus = __('No answer');
                        $statusIcon = wf_img('skins/calls/phone_red.png');
                        //only incoming calls is unanswered
                        if ($each[16] != 'outbound') {
                            $noAnswerCounter++;
                        }
                        if (isset($chartData[$startDate . ' ' . $startHour]['noanswer'])) {
                            $chartData[$startDate . ' ' . $startHour]['noanswer'] ++;
                        } else {
                            $chartData[$startDate . ' ' . $startHour]['noanswer'] = 1;
                        }
                    }

                    if (ispos($each[14], 'BUSY')) {
                        $callStatus = __('Busy');
                        $statusIcon = wf_img('skins/calls/phone_yellow.png');
                    }

                    if (ispos($each[14], 'FAILED')) {
                        $callStatus = __('Failed');
                        $statusIcon = wf_img('skins/calls/phone_fail.png');
                    }

                    $cells.= wf_TableCell($statusIcon . ' ' . $callStatus);
                    $speekTimeRaw = $each[13];
                    $totalTime = $totalTime + $each[13];
                    $speekTime = zb_AskoziaFormatTime($speekTimeRaw);

                    //current caller stats

                    if (isset($callersData[$each[1]])) {
                        $callersData[$each[1]]['calls'] = $callersData[$each[1]]['calls'] + 1;
                        $callersData[$each[1]]['time'] = $callersData[$each[1]]['time'] + $speekTimeRaw;
                    } else {
                        $callersData[$each[1]]['calls'] = 1;
                        $callersData[$each[1]]['time'] = $speekTimeRaw;
                    }

                    if (isset($callersData[$each[2]])) {
                        $callersData[$each[2]]['calls'] = $callersData[$each[2]]['calls'] + 1;
                        $callersData[$each[2]]['time'] = $callersData[$each[2]]['time'] + $speekTimeRaw;
                    } else {
                        $callersData[$each[2]]['calls'] = 1;
                        $callersData[$each[2]]['time'] = $speekTimeRaw;
                    }

                    if (!empty($receiveCid)) {
                        if (isset($callersData[$receiveCid])) {
                            $callersData[$receiveCid]['calls'] = $callersData[$receiveCid]['calls'] + 1;
                            $callersData[$receiveCid]['time'] = $callersData[$receiveCid]['time'] + $speekTimeRaw;
                        } else {
                            $callersData[$receiveCid]['calls'] = 1;
                            $callersData[$receiveCid]['time'] = $speekTimeRaw;
                        }
                    }



                    $cells.= wf_TableCell($speekTime, '', '', 'sorttable_customkey="' . $each[13] . '"');


                    $rows.= wf_TableRow($cells, 'row3');
                }
            }

            if (!empty($callersData)) {
                if (!empty($customCfg)) {
                    $gcells = wf_TableCell(__('Phone'));
                    $gcells.= wf_TableCell(__('Total calls'));
                    $gcells.= wf_TableCell(__('Time'));
                    $grows = wf_TableRow($gcells, 'row1');
                }

                foreach ($callersData as $cix => $eachcdat) {
                    if (!empty($customCfg)) {
                        if ((zb_AskoziaCheckPrefix($customCfg[0], $cix)) AND ( strlen($cix) < 4)) {
                            $gcells = wf_TableCell(zb_AskoziaGetNumAlias($cix));
                            $gcells.= wf_TableCell($eachcdat['calls']);
                            $gcells.= wf_TableCell(zb_AskoziaFormatTime($eachcdat['time']), '', '', 'sorttable_customkey="' . $eachcdat['time'] . '"');
                            $grows.= wf_TableRow($gcells, 'row3');
                        }
                    }
                }
            }
            //total time stats
            $result = '';

            if (!empty($chartData)) {
                if (sizeof($chartData) >= 2) {
                    $gdata = __('Date') . ',' . __('Total') . ',' . __('Answered') . ',' . __('No answer') . "\n";
                    foreach ($chartData as $io => $each) {
                        @$gdata.=$io . ',' . ($each['answered'] + $each['noanswer']) . ',' . $each['answered'] . ',' . $each['noanswer'] . "\n";
                    }

                    $result.=wf_tag('div', false, '', '');
                    $result.=wf_tag('h2') . __('Stats') . wf_tag('h2', true) . wf_tag('br');
                    $result.= wf_Graph($gdata, '800', '200', false);
                    $result.=wf_tag('div', true);
                    $result.=wf_delimiter();
                }
            }

            $result.=__('Time spent on calls') . ': ' . zb_AskoziaFormatTime($totalTime) . wf_tag('br');
            $result.=__('Answered') . ' / ' . __('No answer') . ': ' . $answerCounter . ' / ' . $noAnswerCounter . wf_tag('br');
            $result.=__('Total calls') . ': ' . $callsCounter;

            if (!empty($customCfg)) {
                @$result.=wf_delimiter() . wf_TableBody($grows, '100%', '0', 'sortable') . wf_delimiter();
            }

            $result.=wf_TableBody($rows, '100%', '0', 'sortable');


            show_window('', $result);
        }
    }

    function zb_AskoziaGetCallHistory($from, $to) {
        global $askoziaUrl, $askoziaLogin, $askoziaPassword, $askoziaCacheTime;

        $cachePath = 'exports/';

        $fields = array(
            'extension_number' => 'all',
            'cdr_filter' => 'incomingoutgoing',
            'period_from' => $from,
            'period_to' => $to,
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
            'page_format' => 'A4',
            'SubmitCSVCDR' => 'Download CSV'
        );

//caching
        $cacheUpdate = true;
        $cacheName = serialize($fields);
        $cacheName = md5($cacheName);
        $cacheName = $cachePath . $cacheName . '.askozia';
        $cachetime = time() - ($askoziaCacheTime * 60);

        if (file_exists($cacheName)) {
            if ((filemtime($cacheName) > $cachetime)) {
                $rawResult = file_get_contents($cacheName);
                $cacheUpdate = false;
            } else {
                $cacheUpdate = true;
            }
        } else {
            $cacheUpdate = true;
        }


        if ($cacheUpdate) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $askoziaUrl . '/status_cdr.php');
            curl_setopt($ch, CURLOPT_USERPWD, $askoziaLogin . ":" . $askoziaPassword);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            $rawResult = curl_exec($ch);
            curl_close($ch);
            file_put_contents($cacheName, $rawResult);
        }

        if (!empty($rawResult)) {
            zb_AskoziaParseCallHistory($rawResult);
        } else {
            show_error(__('Empty reply received'));
        }
    }

    function web_AskoziaDateForm() {
        $inputs = wf_Link("?module=askozia&config=true", wf_img('skins/settings.png', __('Settings'))) . ' ';
        $inputs.= wf_DatePickerPreset('datefrom', curdate()) . ' ' . __('From');
        $inputs.= wf_DatePickerPreset('dateto', curdate()) . ' ' . __('To');
        $inputs.= wf_Submit(__('Show'));
        $result = wf_Form("", "POST", $inputs, 'glamour');
        return ($result);
    }

    function web_AskoziaConfigForm() {
        global $askoziaUrl, $askoziaLogin, $askoziaPassword, $askoziaCacheTime;
        $result = wf_Link('?module=askozia', __('Back'), true, 'ubButton') . wf_delimiter();
        $inputs = wf_TextInput('newurl', __('AskoziaPBX URL'), $askoziaUrl, true);
        $inputs.= wf_TextInput('newlogin', __('Administrator login'), $askoziaLogin, true);
        $inputs.= wf_TextInput('newpassword', __('Password'), $askoziaPassword, true);
        $inputs.= wf_TextInput('newcachetime', __('Cache time'), $askoziaCacheTime, true);
        $inputs.= wf_Submit(__('Save'));
        $result.= wf_Form("", "POST", $inputs, 'glamour');
        return ($result);
    }

    function web_AskoziaAliasesForm() {
        global $numAliases;
        $createinputs = wf_TextInput('newaliasnum', __('Phone'), '', true);
        $createinputs.=wf_TextInput('newaliasname', __('Alias'), '', true);
        $createinputs.=wf_Submit(__('Create'));
        $createform = wf_Form('', 'POST', $createinputs, 'glamour');
        $result = $createform;


        if (!empty($numAliases)) {
            $delArr = array();
            foreach ($numAliases as $num => $eachname) {
                $delArr[$num] = $num . ' - ' . $eachname;
            }
            $delinputs = wf_Selector('deletealias', $delArr, __('Delete alias'), '', false);
            $delinputs.= wf_Submit(__('Delete'));
            $delform = wf_Form('', 'POST', $delinputs, 'glamour');
            $result.= $delform;
        }

        return ($result);
    }

    function zb_AskoziaParseStatus($rawData) {
        $exploded = explodeRows($rawData);
        $data = array(
            'phones' => 0,
            'curcalls' => 0,
            'totalcalls' => 0,
            'ram' => 0,
            'disk' => 0,
            'uptime' => 0
        );


        if (!empty($exploded)) {
            foreach ($exploded as $each) {
                //detecting stats
                if (ispos($each, ';')) {
                    $parse = explode(';', $each);
                    //current calls
                    $data['curcalls'] = $parse[1];
                    //total calls
                    $data['totalcalls'] = $parse[2];
                    //registered phones
                    $data['phones'] = $parse[4];
                    //uptime in days or minutes
                    $data['uptime'] = $parse[5];
                    $data['uptime'] = str_replace('min', __('minutes'), $data['uptime']);
                    $data['uptime'] = str_replace('hours', __('hours'), $data['uptime']);
                    $data['uptime'] = str_replace('hour', __('hour'), $data['uptime']);
                    $data['uptime'] = str_replace('days', __('days'), $data['uptime']);
                    $data['uptime'] = str_replace('day', __('day'), $data['uptime']);



                    //system memory
                    $data['ram'] = $parse[6];
                    //external storage
                    $data['disk'] = $parse[7];
                }
            }
        }

        $cells = wf_TableCell(__('Phones'));
        $cells.= wf_TableCell(__('Current calls'));
        $cells.= wf_TableCell(__('Calls processed'));
        $cells.= wf_TableCell(__('Uptime'));
        $cells.= wf_TableCell(__('Memory usage'));
        $cells.= wf_TableCell(__('External storage'));
        $rows = wf_TableRow($cells, 'row2');
        $cells = wf_TableCell($data['phones']);
        $cells.= wf_TableCell($data['curcalls']);
        $cells.= wf_TableCell($data['totalcalls']);
        $cells.= wf_TableCell($data['uptime']);
        $cells.= wf_TableCell(web_bar($data['ram'], '100') . ' ' . $data['ram'] . '%');
        $cells.= wf_TableCell(web_bar($data['disk'], '100') . ' ' . $data['disk'] . '%');
        $rows.= wf_TableRow($cells, 'row3');
        $result = wf_TableBody($rows, '100%', 0, '');
        return ($result);
    }

    function zb_AskoziaGetCurrentStatus() {
        global $askoziaUrl, $askoziaLogin, $askoziaPassword, $askoziaCacheTime;

//caching
        $cachePath = 'exports/';
        $cacheUpdate = true;
        $cacheName = 'currentStatus';
        $cacheName = $cachePath . $cacheName . '.askozia';
        $cachetime = time() - ($askoziaCacheTime * 60);

        if (file_exists($cacheName)) {
            if ((filemtime($cacheName) > $cachetime)) {
                $rawResult = file_get_contents($cacheName);
                $cacheUpdate = false;
            } else {
                $cacheUpdate = true;
            }
        } else {
            $cacheUpdate = true;
        }


        if ($cacheUpdate) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_URL, $askoziaUrl . '/external_get_info.php?data=main');
            curl_setopt($ch, CURLOPT_USERPWD, $askoziaLogin . ":" . $askoziaPassword);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $rawResult = curl_exec($ch);
            curl_close($ch);

            file_put_contents($cacheName, $rawResult);
        }

        if (!empty($rawResult)) {
            show_window(__('Current PBX status'), zb_AskoziaParseStatus($rawResult));
        } else {
            show_error(__('Empty reply received'));
        }
    }

    if (cfr('ASKOZIA')) {

//loading askozia config
        $askoziaConf = zb_AskoziaGetConf();
        $numAliases = zb_AskoziaGetNumAliases();
        $askoziaUrl = $askoziaConf['url'];
        $askoziaLogin = $askoziaConf['login'];
        $askoziaPassword = $askoziaConf['password'];
        $askoziaCacheTime = $askoziaConf['cachetime'];




//showing configuration form
        if (wf_CheckGet(array('config'))) {
            //changing settings
            if (wf_CheckPost(array('newurl', 'newlogin', 'newpassword'))) {
                zb_StorageSet('ASKOZIAPBX_URL', $_POST['newurl']);
                zb_StorageSet('ASKOZIAPBX_LOGIN', $_POST['newlogin']);
                zb_StorageSet('ASKOZIAPBX_PASSWORD', $_POST['newpassword']);
                zb_StorageSet('ASKOZIAPBX_CACHETIME', $_POST['newcachetime']);
                log_register("ASKOZIAPBX settings changed");
                rcms_redirect("?module=askozia&config=true");
            }

            //aliases creation
            if (wf_CheckPost(array('newaliasnum', 'newaliasname'))) {
                $newStoreAliases = $numAliases;
                $newAliasNum = mysql_real_escape_string($_POST['newaliasnum']);
                $newAliasName = mysql_real_escape_string($_POST['newaliasname']);
                $newStoreAliases[$newAliasNum] = $newAliasName;
                $newStoreAliases = serialize($newStoreAliases);
                $newStoreAliases = base64_encode($newStoreAliases);
                zb_StorageSet('ASKOZIAPBX_NUMALIAS', $newStoreAliases);
                log_register("ASKOZIAPBX ALIAS ADD `" . $newAliasNum . "` NAME `" . $newAliasName . "`");
                rcms_redirect("?module=askozia&config=true");
            }

            //alias deletion
            if (wf_CheckPost(array('deletealias'))) {
                $newStoreAliases = $numAliases;
                $deleteAliasNum = mysql_real_escape_string($_POST['deletealias']);
                if (isset($newStoreAliases[$deleteAliasNum])) {
                    unset($newStoreAliases[$deleteAliasNum]);
                    $newStoreAliases = serialize($newStoreAliases);
                    $newStoreAliases = base64_encode($newStoreAliases);
                    zb_StorageSet('ASKOZIAPBX_NUMALIAS', $newStoreAliases);
                    log_register("ASKOZIAPBX ALIAS DELETE `" . $deleteAliasNum . "`");
                    rcms_redirect("?module=askozia&config=true");
                }
            }

            show_window(__('Settings'), web_AskoziaConfigForm());
            show_window(__('Phone book'), web_AskoziaAliasesForm());
        } else {
            //showing call history form
            show_window(__('Calls history'), web_AskoziaDateForm());
        }
    } else {
        show_error(__('Permission denied'));
    }

    if (wf_CheckPost(array('datefrom', 'dateto'))) {
        zb_AskoziaGetCallHistory($_POST['datefrom'], $_POST['dateto']);
    } else {
        if (!wf_CheckGet(array('config'))) {
            zb_AskoziaGetCurrentStatus();
        }
    }
} else {
    show_error(__('AskoziaPBX integration now disabled'));
}
?>
