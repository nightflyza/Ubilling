<?php

if (cfr('PROCRAST')) {

    if (ubRouting::checkGet('run')) {
        $application = ubRouting::get('run', 'vf');
        switch ($application) {
            case 'tetris':
                $jsTetris = file_get_contents('modules/jsc/procrastdata/jstetris/tetris.html');
                $jsTetris = str_replace('START_LABEL', __('Press space to play'), $jsTetris);
                $jsTetris = str_replace('SCORE_LABEL', __('score'), $jsTetris);
                $jsTetris = str_replace('ROWS_LABEL', __('rows'), $jsTetris);
                show_window(__('Tetris'), $jsTetris);
                break;
            case '2048':
                $jsCode = file_get_contents('modules/jsc/procrastdata/2048/2048.html');
                show_window(__('2048'), $jsCode);
                break;
            case 'motox3m':
                $jsCode = file_get_contents('modules/jsc/procrastdata/motox3m.html');
                show_window(__('Moto X3M'), $jsCode, 'center');
                break;
            case 'astray':
                $jsCode = file_get_contents('modules/jsc/procrastdata/astray/run.html');
                show_window(__('Astray'), $jsCode, 'center');
                break;
            case 'hextris':
                $jsCode = file_get_contents('modules/jsc/procrastdata/hextris/run.html');
                show_window(__('Hextris'), $jsCode, 'center');
                break;
            case 'duckhunt':
                $jsCode = file_get_contents('modules/jsc/procrastdata/duckhunt.html');
                show_window(__('Duck hunt'), $jsCode, 'center');
                break;
            case 'circus':
                $jsCode = file_get_contents('modules/jsc/procrastdata/circus.html');
                show_window(__('Circus Charlie'), $jsCode, 'center');
                break;
            case 'pixeldefense':
                $jsCode = file_get_contents('modules/jsc/procrastdata/pixeldefense.html');
                show_window(__('PixelDefense'), $jsCode, 'center');
                break;
            case 'doom':
                $jsCode = file_get_contents('modules/jsc/procrastdata/doom.html');
                show_window(__('Doom'), $jsCode, 'center');
                break;
        }
        show_window('', wf_BackLink('?module=procrast'));
    } else {
        //CDN invokable apps here
        $cachingTimeout = 86400;
        $cdnUrl = 'https://cdn.ubilling.net.ua/';
        $routeList = '?list=true';
        $routeInvoke = '?module=procrast&rinvoke=';
        $cache = new UbillingCache();
        $invocableApps = $cache->get('INVPROCR', $cachingTimeout);
        if (empty($invocableApps)) {
            $invocableApps = array();
            $cdn = new OmaeUrl($cdnUrl . $routeList);
            $ubVer = file_get_contents('RELEASE');
            $agent = 'ProcrastUbilling/' . trim($ubVer);
            $cdn->setUserAgent($agent);
            $rawInvokeData = $cdn->response();
            if (!empty($rawInvokeData)) {
                @$jsonValidity = json_decode($rawInvokeData, true);
                if (is_array($jsonValidity)) {
                    $invocableApps = $jsonValidity;
                }
            }
            $cache->set('INVPROCR', $invocableApps, $cachingTimeout);
        }


        if (ubRouting::checkGet('rinvoke')) {
            //invocing remote app here
            $remoteInvokedApp = ubRouting::get('rinvoke', 'vf');
            if (isset($invocableApps[$remoteInvokedApp])) {
                $appData = $invocableApps[$remoteInvokedApp];
                if ($appData) {
                    $invokeCode = '';
                    $style = 'style="width: 70vw; height: 70vh;"';
                    $options = 'id="dos" src="' . $cdnUrl . $appData['url'] . '" frameborder="0" ' . $style . ' allowfullscreen';
                    $invokeCode .= wf_tag('iframe', false, '', $options);
                    $invokeCode .= wf_tag('iframe', true);
                    $invokeCode .= wf_tag('script', false) . ' document.getElementById("dos").focus(); ' . wf_tag('script', true);
                    show_window(__($appData['name']), $invokeCode);
                }
            } else {
                show_error(__('Strange exception'));
            }
            show_window('', wf_BackLink('?module=procrast'));
        } else {
            //rendering apps list
            $applicationsList = '';
            $applicationArr = array(
                'tetris' => __('Tetris'),
                '2048' => __('2048'),
                'astray' => __('Astray'),
                'hextris' => __('Hextris'),
                'duckhunt' => __('Duck hunt'),
                'motox3m' => __('Moto X3M'),
                'circus' => __('Circus Charlie'),
                'pixeldefense' => __('PixelDefense'),
            );


            if (!empty($applicationArr)) {
                foreach ($applicationArr as $io => $each) {
                    $applicationsList .= zb_buildGameIcon('?module=procrast&run=' . $io, $io . '.png', $each);
                }
            }


            if (!empty($invocableApps)) {
                foreach ($invocableApps as $io => $each) {
                    $applicationsList .= zb_buildGameIcon($routeInvoke . $io, $cdnUrl . $each['icon'], $each['name']);
                }
            }

            $applicationsList .= wf_CleanDiv();
            show_window(__('Procrastination helper'), $applicationsList);
        }
    }
    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}
