<?php

$altcfg = $ubillingConfig->getAlter();
if ($altcfg['ASKOZIA_ENABLED']) {


    /**
     * Returns number aliases
     * 
     * @return array
     */
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

    /**
     * Returns or setups current Askozia configuration options
     * 
     * @return array
     */
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

    /**
     * Renders time duration in seconds into formatted human-readable view
     *      
     * @param int $seconds
     * 
     * @return string
     */
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

    /**
     * Returns number alias
     * 
     * @global array $numAliases
     * @param string $number
     * @param bool $brief
     * 
     * @return string
     */
    function zb_AskoziaGetNumAlias($number, $brief = false) {
        global $numAliases;

        if (!empty($numAliases)) {
            if (isset($numAliases[$number])) {
                if ($brief) {
                    return($numAliases[$number]);
                } else {
                    return($number . ' - ' . $numAliases[$number]);
                }
            } else {
                return ($number);
            }
        } else {
            return ($number);
        }
    }

    /**
     * Checks is callerid contains prefix
     * 
     * @param string $prefix
     * @param string $callerid
     * 
     * @return bool
     */
    function zb_AskoziaCheckPrefix($prefix, $callerid) {
        if (substr($callerid, 0, 1) == $prefix) {
            return (true);
        } else {
            return (false);
        }
    }

    /**
     * Renders parsed calls data
     * 
     * @global array $altcfg
     * @param string $data
     * 
     * @return void
     */
    function zb_AskoziaParseCallHistory($data) {
        /**
         *  D            A
         * Жизнь дерьмо
         * F#        G
         * Возненавидь любя
         * D           A
         * Всем смертям назло
         * F#        G
         * Убей себя сам
         */
        global $altcfg;
        $debugFlag = false;
        $answeredFlag = true;
        $prevTimeStart = '';
        $prevTimeEnd = '';
        $controlGroups = array();
        $controlStats = array();
        $providerStats = array();

        if (isset($altcfg['ASKOZIA_DEBUG'])) {
            if ($altcfg['ASKOZIA_DEBUG']) {
                $debugFlag = true;
            }
        }

        //control groups option handling
        if (isset($altcfg['ASKOZIA_CONTROLGROUPS'])) {
            if (!empty($altcfg['ASKOZIA_CONTROLGROUPS'])) {
                $controlGroups = explode(',', $altcfg['ASKOZIA_CONTROLGROUPS']);
                $controlGroups = array_flip($controlGroups);
            }
        }

        //working time setup
        $rawWorkTime = $altcfg['WORKING_HOURS'];
        $rawWorkTime = explode('-', $rawWorkTime);
        $workStartTime = $rawWorkTime[0];
        $workEndTime = $rawWorkTime[1];

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
            $WorkHoursAnswerCounter = 0;
            $WorkHoursNoAnswerCounter = 0;
            $busycount = 0;

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
                    //Askozia CFE fix
                    if (sizeof($each) > 25) {
                        array_splice($each, 3, 1);
                    }
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
                    if (ispos($each[4], 'SIP-PROVIDER')) {
                        $providerId = explode('-', $each[4]);
                        $providerId = $providerId[0] . $providerId[1] . $providerId[2];
                    } else {
                        $providerId = '';
                    }

                    //setting call direction icon
                    if (ispos($each['16'], 'out')) {
                        $toNumber = $each[2];
                        $callDirection = wf_img('skins/calls/outgoing.png') . ' ';
                        $directionFlag = 'out';
                    } else {
                        $toNumber = $each[18];
                        $callDirection = wf_img('skins/calls/incoming.png') . ' ';
                        $directionFlag = 'in';
                    }

                    //showing debug info
                    if ($debugFlag) {
                        $callIdData = wf_modal($callsCounter, $callsCounter, $debugData, '', '500', '600');
                    } else {
                        $callIdData = $callsCounter;
                    }



                    $cells = wf_TableCell($callIdData, '', '', 'sorttable_customkey="' . $callsCounter . '"');
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

                    if (ispos($each[14], 'ANSWERED') AND ( !ispos($each[7], 'VoiceMail'))) {
                        $answeredFlag = true;
                        $callStatus = __('Answered');
                        $statusIcon = wf_img('skins/calls/phone_green.png');
                        $answerCounter++;
                        //work time controls
                        if (zb_isTimeBetween($workStartTime, $workEndTime, $startTime)) {
                            $WorkHoursAnswerCounter++;
                            //control groups answered calls handling
                            if (isset($controlGroups[$toNumber])) {
                                if (isset($controlStats[$toNumber])) {
                                    if (isset($controlStats[$toNumber]['answered'])) {
                                        $controlStats[$toNumber]['answered'] ++;
                                    } else {
                                        $controlStats[$toNumber]['answered'] = 1;
                                    }
                                }
                            }
                        }

                        if (isset($chartData[$startDate . ' ' . $startHour]['answered'])) {
                            $chartData[$startDate . ' ' . $startHour]['answered'] ++;
                        } else {
                            $chartData[$startDate . ' ' . $startHour]['answered'] = 1;
                        }

                        //filling provider stats for answered calls
                        if (!empty($providerId)) {
                            if ($directionFlag == 'in') {
                                if (isset($providerStats[$providerId])) {
                                    $providerStats[$providerId]['answered'] ++;
                                    $providerStats[$providerId]['time'] +=$each[13];
                                } else {
                                    $providerStats[$providerId]['answered'] = 1;
                                    $providerStats[$providerId]['unanswered'] = 0;
                                    $providerStats[$providerId]['time'] = $each[13];
                                }
                            }
                        }
                    }

                    if ((ispos($each[14], 'NO ANSWER')) OR ( ispos($each[7], 'VoiceMail'))) {
                        $answeredFlag = false;
                        $callStatus = __('No answer');
                        $statusIcon = wf_img('skins/calls/phone_red.png');
                        //only incoming calls is unanswered
                        if ($each[16] != 'outbound') {
                            $noAnswerCounter++;
                            if (zb_isTimeBetween($workStartTime, $workEndTime, $startTime)) {
                                $WorkHoursNoAnswerCounter++;
                                if (isset($controlGroups[$toNumber])) {
                                    //control groups no answered calls count in work time
                                    if (isset($controlStats[$toNumber]['noanswer'])) {
                                        $controlStats[$toNumber]['noanswer'] ++;
                                    } else {
                                        $controlStats[$toNumber]['noanswer'] = 1;
                                    }
                                }
                            }

                            //filling provider stats for not answered calls
                            if (!empty($providerId)) {
                                if ($directionFlag == 'in') {
                                    if (isset($providerStats[$providerId])) {
                                        $providerStats[$providerId]['unanswered'] ++;
                                    } else {
                                        $providerStats[$providerId]['answered'] = 0;
                                        $providerStats[$providerId]['unanswered'] = 1;
                                        $providerStats[$providerId]['time'] = 0;
                                    }
                                }
                            }
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
                    //default rowclass
                    $rowClass = 'row3';

                    //non answered calls coloring
                    if ($answeredFlag == false) {
                        $rowClass = 'ukvbankstadup';
                    }

                    //time range processing
                    $curTimeStart = date("H:i:s", strtotime($each[9]));
                    $curTimeEnd = date("H:i:s", strtotime($each[11]));
                    if ((empty($prevTimeStart)) AND ( empty($prevTimeEnd))) {
                        $prevTimeStart = $curTimeStart;
                        $prevTimeEnd = $curTimeEnd;
                    } else {
                        if ($answeredFlag == false) {
                            if (zb_isTimeBetween($prevTimeStart, $prevTimeEnd, $curTimeStart, true)) {
                                $rowClass = 'undone';
                                if (zb_isTimeBetween($workStartTime, $workEndTime, $startTime)) {
                                    $busycount++;
                                }
                            }
                        }

                        $prevTimeStart = $curTimeStart;
                        if (strtotime($curTimeEnd) > strtotime($prevTimeEnd)) {
                            $prevTimeEnd = $curTimeEnd;
                        }
                    }

                    //control groups stats
                    if (isset($controlGroups[$toNumber])) {
                        if (isset($controlStats[$toNumber])) {
                            if (isset($controlStats[$toNumber]['time'])) {
                                $controlStats[$toNumber]['time'] += $speekTimeRaw;
                            } else {
                                $controlStats[$toNumber]['time'] = $speekTime;
                            }
                        }
                    }


                    $rows.= wf_TableRow($cells, $rowClass);
                }
            }



            if (!empty($controlStats)) {
                $ccells = wf_TableCell(__('Phone'));
                $ccells.= wf_TableCell(__('Total calls'));
                $ccells.= wf_TableCell(__('Time'));
                $ccells.= wf_TableCell(__('Answered'));
                $crows = wf_TableRow($ccells, 'row1');
                foreach ($controlStats as $io => $each) {
                    $ccells = wf_TableCell(zb_AskoziaGetNumAlias($io));
                    $ccells.= wf_TableCell(@$each['answered'] + $each['noanswer']);
                    $ccells.= wf_TableCell(zb_AskoziaFormatTime($each['time']));
                    $ccells.= @wf_TableCell($each['answered'] . ' (' . zb_PercentValue(($each['answered'] + $each['noanswer']), $each['answered']) . '%)');
                    $crows.= wf_TableRow($ccells, 'row3');
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
            $result.=__('Total') . ': ' . __('Answered') . ' / ' . __('No answer') . ': ' . $answerCounter . ' / ' . $noAnswerCounter . ' (' . zb_PercentValue($answerCounter + $noAnswerCounter, $answerCounter) . '%)' . wf_tag('br');
            $result.=wf_tag('b') . __('Working hours') . ': ' . __('Answered') . ' / ' . __('No answer') . ': ' . $WorkHoursAnswerCounter . ' / ' . $WorkHoursNoAnswerCounter . ' (' . zb_PercentValue($WorkHoursAnswerCounter + $WorkHoursNoAnswerCounter, $WorkHoursAnswerCounter) . '%)' . wf_tag('b', true) . wf_tag('br');
            $result.=__('Not working hours') . ': ' . __('Answered') . ' / ' . __('No answer') . ': ' . ($answerCounter - $WorkHoursAnswerCounter) . ' / ' . ($noAnswerCounter - $WorkHoursNoAnswerCounter) . ' (' . zb_PercentValue(($answerCounter - $WorkHoursAnswerCounter) + ($noAnswerCounter - $WorkHoursNoAnswerCounter), ($answerCounter - $WorkHoursAnswerCounter)) . '%)' . wf_tag('br');
            $result.= __('Missing calls because of overlap with the previous by time') . ' (' . __('Working hours') . '): ' . $busycount . wf_tag('br');
            $result.=__('Total calls') . ': ' . $callsCounter;
            //rendering provider stats
            if (!empty($providerStats)) {
                $cellsp = wf_TableCell(__('SIP trunk'));
                $cellsp.= wf_TableCell(__('Answered'));
                $cellsp.= wf_TableCell(__('No answer'));
                $cellsp.= wf_TableCell(__('Total calls'));
                $cellsp.= wf_TableCell(__('Talk time'));
                $rowsp = wf_TableRow($cellsp, 'row1');
                foreach ($providerStats as $ioz => $eachz) {
                    $cellsp = wf_TableCell(zb_AskoziaGetNumAlias($ioz, true));
                    $cellsp.= wf_TableCell($eachz['answered']);
                    $cellsp.= wf_TableCell($eachz['unanswered']);
                    $cellsp.= wf_TableCell($eachz['unanswered'] + $eachz['answered']);
                    $cellsp.= wf_TableCell(zb_AskoziaFormatTime($eachz['time']));
                    $rowsp.= wf_TableRow($cellsp, 'row3');
                }
                $result.=wf_delimiter();
                $result.=wf_TableBody($rowsp, '100%', 0, 'sortable');
                $result.=wf_delimiter();
            }

            if (!empty($controlStats)) {
                $result.=wf_tag('h3') . __('Contol groups stats') . wf_tag('h3', true);
                @$result.= wf_TableBody($crows, '100%', '0', 'sortable') . wf_delimiter();
            }
            if (!empty($customCfg)) {
                @$result.= wf_TableBody($grows, '100%', '0', 'sortable') . wf_delimiter();
            }



            $result.=wf_TableBody($rows, '100%', '0', 'sortable');


            show_window('', $result);
        }
    }

    /**
     * Fetches calls history from Askozia URL
     * 
     * @global string $askoziaUrl
     * @global string $askoziaLogin
     * @global string $askoziaPassword
     * @global int $askoziaCacheTime
     * @param string $from
     * @param string $to
     * 
     * @return void
     */
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

    /**
     * Renders date selection form
     * 
     * @return string
     */
    function web_AskoziaDateForm() {
        $inputs = wf_Link("?module=askozia&config=true", wf_img('skins/settings.png', __('Settings'))) . ' ';
        $inputs.= wf_DatePickerPreset('datefrom', curdate()) . ' ' . __('From');
        $inputs.= wf_DatePickerPreset('dateto', curdate()) . ' ' . __('To');
        $inputs.= wf_Submit(__('Show'));
        $result = wf_Form("", "POST", $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders askozia configuration form
     * 
     * @global string $askoziaUrl
     * @global string $askoziaLogin
     * @global string $askoziaPassword
     * @global int $askoziaCacheTime
     * 
     * @return string 
     */
    function web_AskoziaConfigForm() {
        global $askoziaUrl, $askoziaLogin, $askoziaPassword, $askoziaCacheTime;
        $result = wf_BackLink('?module=askozia') . wf_delimiter();
        $inputs = wf_TextInput('newurl', __('AskoziaPBX URL'), $askoziaUrl, true);
        $inputs.= wf_TextInput('newlogin', __('Administrator login'), $askoziaLogin, true);
        $inputs.= wf_TextInput('newpassword', __('Password'), $askoziaPassword, true);
        $inputs.= wf_TextInput('newcachetime', __('Cache time'), $askoziaCacheTime, true);
        $inputs.= wf_Submit(__('Save'));
        $result.= wf_Form("", "POST", $inputs, 'glamour');
        return ($result);
    }

    /**
     * Renders askozia aliases assigning form
     * 
     * @global array $numAliases
     * 
     * @return string
     */
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

    /**
     * Parses askozia system status data
     * 
     * @param string $rawData
     * 
     * @return string
     */
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

    /**
     * Fetches askozia system status from URL
     * 
     * @global string $askoziaUrl
     * @global string $askoziaLogin
     * @global string $askoziaPassword
     * @global int $askoziaCacheTime
     * 
     * @return void
     */
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

    /**
     * Renders numlog stats if it exists
     * 
     * @return void
     */
    function zb_AskoziaRenderNumLog() {
        global $ubillingConfig;
        $billCfg = $ubillingConfig->getBilling();
        $logPath = AskoziaNum::LOG_PATH;
        $catPath = $billCfg['CAT'];
        $grepPath = $billCfg['GREP'];
        $replyOffset = 5;
        $numberOffset = 2;
        $loginOffset = 7;
        $replyCount = 0;
        $replyStats = array();
        $replyNames = array(
            0 => __('Not found'),
            1 => __('Active'),
            2 => __('Debt'),
            3 => __('Frozen')
        );

        $result = '';
        if (file_exists($logPath)) {
            if (!wf_CheckPost(array('numyear', 'nummonth'))) {
                $curYear = curyear();
                $curMonth = date("m");
            } else {
                $curYear = vf($_POST['numyear'], 3);
                $curMonth = vf($_POST['nummonth'], 3);
            }
            $parseDate = $curYear . '-' . $curMonth;

            $dateInputs = wf_YearSelectorPreset('numyear', __('Year'), false, $curYear) . ' ';
            $dateInputs.= wf_MonthSelector('nummonth', __('Month'), $curMonth, false) . ' ';
            $dateInputs.= wf_Submit(__('Show'));
            $result.=wf_Form('', 'POST', $dateInputs, 'glamour');

            $rawLog = shell_exec($catPath . ' ' . $logPath . ' | ' . $grepPath . ' ' . $parseDate . '-');
            if (!empty($rawLog)) {
                $rawLog = explodeRows($rawLog);
                if (!empty($rawLog)) {
                    foreach ($rawLog as $io => $each) {
                        if (!empty($each)) {
                            $line = explode(' ', $each);
                            $callReply = $line[$replyOffset];
                            if (isset($replyStats[$callReply])) {
                                $replyStats[$callReply] ++;
                            } else {
                                $replyStats[$callReply] = 1;
                            }
                            $replyCount++;
                        }
                    }

                    if (!empty($replyStats)) {
                        $cells = wf_TableCell(__('Reply'));
                        $cells.=wf_TableCell(__('Count'));
                        $rows = wf_TableRow($cells, 'row1');
                        foreach ($replyStats as $replyCode => $callsCount) {
                            $cells = wf_TableCell($replyNames[$replyCode]);
                            $cells.=wf_TableCell($callsCount);
                            $rows.= wf_TableRow($cells, 'row3');
                        }
                        $result.=wf_TableBody($rows, '100%', 0, 'sortable');
                        $result.=__('Total') . ': ' . $replyCount;
                    }
                }
            }

            if (filesize($logPath) > 10) {
                show_window(__('Stats') . ' AskoziaNum ' . $curYear . '-' . $curMonth, $result);
            }
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
            zb_AskoziaRenderNumLog();
        }
    }
} else {
    show_error(__('AskoziaPBX integration now disabled'));
}
?>
