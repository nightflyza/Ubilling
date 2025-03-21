<?php

if (cfr('PLARPING')) {

    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username', 'login');
        $config = $ubillingConfig->getBilling();
        $alterconfig = $ubillingConfig->getAlter();
        $parseMe = '';
        $cloneFlag = false;
        $wrongMacFlag = false;
        $messages = new UbillingMessageHelper();
        $arping_path = @$alterconfig['ARPING'];
        if (!empty($arping_path)) {
            $arping_iface = $alterconfig['ARPING_IFACE'];
            $arping_options = $alterconfig['ARPING_EXTRA_OPTIONS'];
            $sudo_path = $config['SUDO'];
            $userdata = zb_UserGetStargazerData($login);
            $user_ip = $userdata['IP'];
            $user_mac = zb_MultinetGetMAC($user_ip);
            $user_mac = strtolower($user_mac);
            $pingCount = 10;
            $rttTmp = '';
            $params = array();
            $succArray = array();
            //setting ping parameters
            $addParams = '';
            //setting ajax background params
            $addAjax = '';
            if (ubRouting::checkGet('packcount')) {
                $pingCount = ubRouting::get('packcount', 'int');
                $addParams .= ' -c ' . $pingCount;
            }
            if (ubRouting::checkPost('count')) {
                $pingCount = ubRouting::post('count', 'int');
                $addAjax .= "&packcount=" . $pingCount;
                $addParams .= ' -c ' . $pingCount;
            }
            if (ubRouting::checkGet('charts')) {
                $addAjax .= '&charts=true';
            }
            $command = $sudo_path . ' ' . $arping_path . ' ' . $arping_iface . ' -c ' . $pingCount . ' ' . $arping_options . ' ' . $addParams . ' ' . $user_ip;
            $raw_result = shell_exec($command);
            $ping_result = wf_AjaxLoader();
            if (!ubRouting::checkGet('charts')) {
                $ping_result .= wf_Link('?module=pl_arping&charts=true&username=' . $login, wf_img_sized('skins/icon_stats.gif', '', '16') . ' ' . __('Graphs'), false, 'ubButton');
            } else {
                $ping_result .= wf_Link('?module=pl_arping&username=' . $login, wf_img('skins/ping_icon.png') . ' ' . __('Normal'), false, 'ubButton');
            }
            $ping_result .= wf_AjaxLink('?module=pl_arping&username=' . $login . '&ajax=true' . $addAjax, wf_img('skins/refresh.gif') . ' ' . __('Renew'), 'ajaxarping', true, 'ubButton');
            $rawResult = shell_exec($command);
            //detecting duplicate MAC
            $rawArray = explodeRows($raw_result);
            if (!empty($rawArray)) {
                foreach ($rawArray as $io => $eachline) {
                    //mac reply extraction
                    if (!empty($user_mac)) {
                        if (ispos($eachline, 'time')) {
                            $macReplied = zb_ExtractMacAddress($eachline);
                            if (!empty($macReplied)) {
                                $macReplied = strtolower($macReplied);
                                if ($macReplied != $user_mac) {
                                    $wrongMacFlag = true;
                                }
                            }
                        }
                    }

                    //summmary
                    if (ispos($eachline, 'packets transmitted')) {
                        $parseMe = $eachline;
                    }
                }
            }

            if (!empty($parseMe)) {
                $parseMe = explode(',', $parseMe);
                if (sizeof($parseMe) == 3) {
                    $txCount = vf($parseMe[0], 3);
                    $rxCount = vf($parseMe[1], 3);
                    if ($rxCount > $txCount) {
                        $cloneFlag = true;
                    }
                }
            }

            if ($cloneFlag) {
                $ping_result .= $messages->getStyledMessage(__('It looks like this MAC addresses has duplicate on the network'), 'error');
            }

            if ($wrongMacFlag) {
                $ping_result .= $messages->getStyledMessage(__('It looks like another MAC which is not assigned to this user has replied to requests'), 'error');
            }

            //some charts
            if (ubRouting::checkGet('charts')) {

                if (!empty($rawResult)) {
                    $pingLines = explodeRows($rawResult);
                    $tmpArr = array();
                    for ($packCount = 0; $packCount <= $pingCount; $packCount++) {
                        $tmpArr[$packCount] = -1;
                    }
                    $succArray = array();

                    $params[0] = array(__('Packets'), __('Time'));

                    if (!empty($pingLines)) {
                        foreach ($pingLines as $io => $eachLine) {
                            if (ispos($eachLine, 'index')) {
                                $latency = explode('=', $eachLine);
                                $seq = $latency[1];
                                $seq = vf($seq, 3);
                                if (isset($latency[2])) {
                                    $latency = explode(' ', $latency[2]);
                                    $latency = $latency[0];
                                    $succArray[$seq] = $latency;
                                }
                            } else {
                                //RTT here
                                if (ispos($eachLine, 'min/avg/max/std-dev')) {
                                    $rttTmp = explode('=', $eachLine);
                                    $rttTmp = 'RTT: ' . __('min/avg/max/std-dev') . ' ' . $rttTmp[1];
                                }
                            }
                        }
                    }
                }

                if (!empty($tmpArr)) {

                    for ($packCount = 0; $packCount <= $pingCount - 1; $packCount++) {
                        if (isset($succArray[$packCount])) {
                            $params[] = array($packCount, $succArray[$packCount]);
                        } else {
                            $params[] = array($packCount, $tmpArr[$packCount]);
                        }
                    }
                }

                $chartsOptions = "
            'focusTarget': 'category',
                        'hAxis': {
                        'color': 'none',
                            'baselineColor': 'none',
                        },
                        'vAxis': {
                        'color': 'none',
                            'baselineColor': '#006d0f',
                    },
                        'curveType': 'function',
                        'pointSize': 5,
                        'crosshair': {
                        trigger: 'none'
                    },";


                //overriding result output
                $pingParams = __('IP') . ': ' . $user_ip . ' ' . __('Packets count') . ': ' . $pingCount;
                $rawResult = $messages->getStyledMessage($pingParams, 'info');

                $rawResult .= wf_gchartsLineZeroIsBad($params, '', '100%', '300px', $chartsOptions);
                $lossPercent = (100 - zb_PercentValue($pingCount, sizeof($succArray)));

                if ($lossPercent > 0) {
                    $noticeStyle = 'error';
                    $summaryStyle = 'warning';
                } else {
                    $noticeStyle = 'success';
                    $summaryStyle = 'info';
                }
                //loss stats
                $rawResult .= $messages->getStyledMessage(__('Packets lost') . ': ' . $lossPercent . '%', $noticeStyle);
                $succCount = sizeof($succArray);
                $pingSummary = __('Packets received') . ': ' . $succCount . ' ' . __('Packets lost') . ': ' . ($pingCount - $succCount) . ' ' . $rttTmp;
                $rawResult .= $messages->getStyledMessage($pingSummary, $summaryStyle);
            }

            if (ubRouting::checkGet('ajax')) {
                die($rawResult);
            }
            $ping_result .= wf_tag('pre', false, '', 'id="ajaxarping"') . $rawResult . wf_tag('pre', true);
            show_window(__('Settings'), wf_PlArpingOptionsForm());
            show_window(__('User ARP pinger'), $ping_result);
        } else {
            show_error(__('ARPING') . ' ' . __('Disabled'));
        }

        show_window('', web_UserControls($login));
    }
} else {
    show_error(__('You cant control this module'));
}
