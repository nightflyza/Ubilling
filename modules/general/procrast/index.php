<?php

if (cfr('PROCRAST')) {

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
            //invoking remote app here
            $remoteInvokedApp = ubRouting::get('rinvoke', 'vf');
            if (isset($invocableApps[$remoteInvokedApp])) {
                $appData = $invocableApps[$remoteInvokedApp];
                if ($appData) {
                    $invokeCode = '';
                    $style = 'style="width: 70vw; height: 70vh;"';
                    if (ispos($appData['params'],'CUSTOMVIEW:')) {
                        $style=str_replace('CUSTOMVIEW:','',$appData['params']);
                    }
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
            
            if (!empty($invocableApps)) {
                foreach ($invocableApps as $io => $each) {
                    $applicationsList .= zb_buildGameIcon($routeInvoke . $io, $cdnUrl . $each['icon'], $each['name']);
                }
            }

            $applicationsList .= wf_CleanDiv();
            show_window(__('Procrastination helper'), $applicationsList);
        }
    
    zb_BillingStats(true);
} else {
    show_error(__('Access denied'));
}
