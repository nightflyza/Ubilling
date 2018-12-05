<?php

if (cfr('PLPINGER')) {

    function wf_PlPingerOptionsForm() {
        //previous setting
        if (wf_CheckPost(array('packet'))) {
            $currentpack = vf($_POST['packet'], 3);
        } else {
            $currentpack = '';
        }

        if (wf_CheckPost(array('count'))) {
            $getCount = vf($_POST['count'], 3);
            if ($getCount <= 10000) {
                $currentcount = $getCount;
            } else {
                $currentcount = '';
            }
        } else {
            $currentcount = '';
        }

        $inputs = wf_TextInput('packet', __('Packet size'), $currentpack, false, 5);
        $inputs.= wf_TextInput('count', __('Count'), $currentcount, false, 5);
        $inputs.= wf_Submit(__('Save'));
        $result = wf_Form('', 'POST', $inputs, 'glamour');
        return ($result);
    }

    if (isset($_GET['username'])) {
        $login = $_GET['username'];
        $config = rcms_parse_ini_file(CONFIG_PATH . 'billing.ini');
        $ping_path = $config['PING'];
        $sudo_path = $config['SUDO'];
        $userdata = zb_UserGetStargazerData($login);
        $user_ip = $userdata['IP'];
        $pingCount = 10;
        //setting ping parameters
        $addParams = '';
        if (wf_CheckGet(array('packsize'))) {
            $addParams.=' -s ' . vf($_GET['packsize'], 3);
        }

        if (wf_CheckGet(array('packcount'))) {
            $pingCount = vf($_GET['packcount'], 3);
            $addParams.=' -c ' . $pingCount;
        }

        //setting ajax background params
        $addAjax = '';
        if (wf_CheckPost(array('packet'))) {
            $addAjax.="&packsize=" . vf($_POST['packet'], 3);
            $addParams.=' -s ' . vf($_POST['packet'], 3);
        }

        if (wf_CheckPost(array('count'))) {
            $pingCount = vf($_POST['count'], 3);
            $addAjax.="&packcount=" . $pingCount;
            $addParams.=' -c ' . $pingCount;
        }

        if (wf_CheckGet(array('charts'))) {
            $addAjax.='&charts=true';
        }

        $command = $sudo_path . ' ' . $ping_path . ' -i 0.01 -c 10 ' . $addParams . ' ' . $user_ip;
        $ping_result = wf_AjaxLoader();
        if (!wf_CheckGet(array('charts'))) {
            $ping_result.=wf_Link('?module=pl_pinger&charts=true&username=' . $_GET['username'], wf_img_sized('skins/icon_stats.gif', '', '16') . ' ' . __('Graphs'), false, 'ubButton');
        } else {
            $ping_result.=wf_Link('?module=pl_pinger&username=' . $_GET['username'], wf_img('skins/ping_icon.png') . ' ' . __('Normal'), false, 'ubButton');
        }
        $ping_result.= wf_AjaxLink('?module=pl_pinger&username=' . $login . '&ajax=true' . $addAjax, wf_img('skins/refresh.gif') . ' ' . __('Renew'), 'ajaxping', true, 'ubButton');
        $rawResult = shell_exec($command);
        //some charts
        if (wf_CheckGet(array('charts'))) {
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
                        if (ispos($eachLine, 'ttl')) {
                            $latency = explode('=', $eachLine);
                            $seq = $latency[1];
                            $seq = vf($seq, 3);
                            if (isset($latency[3])) {
                                $latency = explode(' ', $latency[3]);
                                $latency = $latency[0];
                                $succArray[$seq] = $latency;
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

            $rawResult = wf_gchartsLineZeroIsBad($params, '', '100%', '300px', $chartsOptions);
            $lossPercent = (100 - zb_PercentValue($pingCount, sizeof($succArray)));
            $messages = new UbillingMessageHelper();
            if ($lossPercent > 0) {
                $noticeStyle = 'error';
            } else {
                $noticeStyle = 'success';
            }
            $rawResult.=$messages->getStyledMessage(__('Packets lost') . ': ' . $lossPercent . '%', $noticeStyle);
        }

        if (wf_CheckGet(array('ajax'))) {
            die($rawResult);
        }
        $ping_result.=wf_tag('pre', false, '', 'id="ajaxping"') . $rawResult . wf_tag('pre', true);
        show_window(__('Settings'), wf_PlPingerOptionsForm());
        show_window(__('User pinger'), $ping_result);

        show_window('', web_UserControls($login));
    }
} else {
    show_error(__('You cant control this module'));
}
?>
