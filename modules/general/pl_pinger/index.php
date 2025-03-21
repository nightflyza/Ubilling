<?php

if (cfr('PLPINGER')) {

    if (ubRouting::checkGet('username')) {
        $login = ubRouting::get('username');
        $config = $ubillingConfig->getBilling();
        $ping_path = $config['PING'];
        $sudo_path = $config['SUDO'];
        $userdata = zb_UserGetStargazerData($login);
        $user_ip = $userdata['IP'];
        $pingCount = 10;
        $pingSize = 64;
        $rttTmp = '';
        //setting ping parameters
        $addParams = '';
        if (ubRouting::checkGet('packsize')) {
            $pingSize = ubRouting::get('packsize', 'int');
            $addParams .= ' -s ' . $pingSize;
        }

        if (ubRouting::checkGet('packcount')) {
            $pingCount = ubRouting::get('packcount', 'int');
            $addParams .= ' -c ' . $pingCount;
        }

        //setting ajax background params
        $addAjax = '';
        if (ubRouting::checkPost('packet')) {
            $pingSize = ubRouting::post('packet', 'int');
            $addAjax .= "&packsize=" . $pingSize;
            $addParams .= ' -s ' . $pingSize;
        }

        if (ubRouting::checkPost('count')) {
            $pingCount = ubRouting::post('count', 'int');
            $addAjax .= "&packcount=" . $pingCount;
            $addParams .= ' -c ' . $pingCount;
        }

        if (ubRouting::checkGet('charts')) {
            $addAjax .= '&charts=true';
        }

        $command = $sudo_path . ' ' . $ping_path . ' -i 0.01 -c 10 ' . $addParams . ' ' . $user_ip;
        $ping_result = wf_AjaxLoader();
        if (!ubRouting::checkGet('charts')) {
            $ping_result .= wf_Link('?module=pl_pinger&charts=true&username=' . $login, wf_img_sized('skins/icon_stats.gif', '', '16') . ' ' . __('Graphs'), false, 'ubButton');
        } else {
            $ping_result .= wf_Link('?module=pl_pinger&username=' . $login, wf_img('skins/ping_icon.png') . ' ' . __('Normal'), false, 'ubButton');
        }
        $ping_result .= wf_AjaxLink('?module=pl_pinger&username=' . $login . '&ajax=true' . $addAjax, wf_img('skins/refresh.gif') . ' ' . __('Renew'), 'ajaxping', true, 'ubButton');
        $rawResult = shell_exec($command);
        //some charts
        if (ubRouting::checkGet('charts')) {
            /**
             * 心の声で散弾銃のように
             * 唄い続けた
             */
            if (!empty($rawResult)) {
                $pingLines = explodeRows($rawResult);
                $tmpArr = array();
                for ($packCount = 0; $packCount <= $pingCount; $packCount++) {
                    $tmpArr[$packCount] = -1;
                }

                $succArray = array();
                $firstSec = -2;
                $secOffset = 0;
                $params[0] = array(__('Packets'), __('Time'));

                if (!empty($pingLines)) {
                    foreach ($pingLines as $io => $eachLine) {
                        //each packet result
                        if (ispos($eachLine, 'ttl')) {
                            $latency = explode('=', $eachLine);
                            $seq = $latency[1];
                            $seq = ubRouting::filters($seq, 'int');
                            //start couting packets
                            if ($firstSec == -2) {
                                $firstSec = $seq;
                                //Linux system?
                                if ($firstSec == 1) {
                                    $secOffset = 1; //decrement value
                                }
                            }

                            if (isset($latency[3])) {
                                $latency = explode(' ', $latency[3]);
                                $latency = $latency[0];
                                $sequenceNorm = $seq - $secOffset;
                                $succArray[$sequenceNorm] = $latency;
                            }
                        } else {
                            //RTT here
                            if (ispos($eachLine, 'min/avg/max')) {
                                $rttTmp = explode('=', $eachLine);
                                $rttTmp = 'RTT: ' . __('min/avg/max/dev') . ' ' . $rttTmp[1];
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

            $messages = new UbillingMessageHelper();
            //overriding result output
            $pingParams = __('IP') . ': ' . $user_ip . ' ' . __('Packets count') . ': ' . $pingCount . ' ' . __('Packet size') . ': ' . $pingSize;
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

        $ping_result .= wf_tag('pre', false, '', 'id="ajaxping"') . $rawResult . wf_tag('pre', true);
        show_window(__('Settings'), wf_PlPingerOptionsForm());
        show_window(__('User pinger'), $ping_result);

        show_window('', web_UserControls($login));
    }
} else {
    show_error(__('You cant control this module'));
}

