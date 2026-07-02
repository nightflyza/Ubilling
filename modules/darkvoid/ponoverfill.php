<?php

$result = '';

if ($darkVoidContext['ubConfig']->getAlterParam('PON_ENABLED')) {
    if ($darkVoidContext['ubConfig']->getAlterParam('TB_PON_OVERFILL')) {
        $ponizer = new PONizer();
        $ponOverfillData = $ponizer->getOLTOverfilledInterfaces();

        if (!empty($ponOverfillData)) {
            $overFilledInterfacesCount = sizeof($ponOverfillData);
            $content = wf_Link(PONizer::URL_ME . '&oltstats=true', wf_img('skins/icon_stats_16.gif', __('Stats') . ' ' . __('OLT'))) . wf_delimiter();

            foreach ($ponOverfillData as $eachOverfill) {
                $statsUrl = PONizer::URL_ME . '&oltstats=true#go' . $eachOverfill['oltid'];
                $oltLocator = wf_Link($statsUrl, wf_img_sized('skins/pon_icon.gif', __('Go to OLT'), '16', '16'));
                $content .= $oltLocator . ' ' . $eachOverfill['name'].': ' . $eachOverfill['interface'] .  wf_tag('br');
            }

            $modalLink = wf_img('skins/bucket32.png', __('Overfilled PON interfaces') . ': ' . $overFilledInterfacesCount);
            $result .= wf_modal($modalLink, __('Overfilled PON interfaces'), $content, '', '500', '400');
            
        }
    }
}

return ($result);