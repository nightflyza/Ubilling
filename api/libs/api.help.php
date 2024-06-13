<?php

/**
 *  Returns help chapter content in current locale
 * 
 *  @param   $chapter Help chapter name
 * 
 *  @return  string
 */
function web_HelpChapterGet($chapter) {
    $lang = curlang();
    $chapter = vf($chapter);
    $wikiChapterMark = 'wiki]';
    $wikiBaseUrl = 'https://wiki.ubilling.net.ua/doku.php?id=';
    $result = '';
    if (file_exists(DATA_PATH . "help/" . $lang . "/" . $chapter)) {
        $result .= file_get_contents(DATA_PATH . "help/" . $lang . "/" . $chapter);
        if (ispos($result, $wikiChapterMark)) {
            $searchRegex = "#\[wiki\](.*?)\[/wiki\]#is";
            $wikiIcon = '<img src="skins/icon_wiki_small.png">';
            $replace = '<a href="' . $wikiBaseUrl . '\\1" target="_blank" class="ubButton">' . $wikiIcon . ' ' . __('Wiki article') . '</a>';
            $result = preg_replace($searchRegex, $replace, $result);
        }
        $result = nl2br($result);
    }
    return ($result);
}

/**
 *  Shows help icon if context chapter available for current language
 *  
 *  @return  string
 */
function web_HelpIconShow() {
    global $ubillingConfig;
    $normalMode = ($ubillingConfig->getAlterParam('IM_GREEDY_PIDAR')) ? false : true;
    $result = '';
    if (cfr('HELP')) {
        $lang = curlang();
        $currentModuleName = (ubRouting::checkGet('module')) ? ubRouting::get('module') : 'taskbar';
        $donationUrl = 'https://ubilling.net.ua/rds/donate/';
        if (file_exists(DATA_PATH . "help/" . $lang . "/" . $currentModuleName)) {
            $helpChapterContent = web_HelpChapterGet($currentModuleName);
            $helpChapterContent .= wf_delimiter(1);
            if (cfr('PROCRAST')) {
                $helpChapterContent .= wf_Link('?module=procrast', wf_img('skins/gamepad.png', __('Procrastination helper'))) . ' ';
            }

            if (!$normalMode) {
                $helpChapterContent .= wf_Link($donationUrl, wf_img('skins/heart16.png', __('Support project'))) . ' ';
            }

            $containerStyle = 'style="min-width:400px; max-width:800px; min-height:200px; max-height:500px;"';
            $helpChapterContent = wf_AjaxContainer('contexthelpchapter', $containerStyle, $helpChapterContent);

            $result .= wf_modalAuto(wf_img_sized("skins/help.gif", __('Context help'), 20), __('Context help'), $helpChapterContent, '');
        }

        if ($normalMode) {
            $result .= ' ' . wf_Link($donationUrl, wf_img_sized('skins/heart32.png', __('Support project'), '20'), false, '', 'target="_blank"') . '';
        }
    } else {
        if (!$normalMode) {
            $result .= '<!-- pidar detected -->';
        }
    }
    return ($result);
}

/**
 * Returns Ubilling release info
 *
 * @param bool $raw
 * 
 * @return string
 */
function web_ReleaseInfo($raw = false) {
    $result = '';
    $releaseInfoBaseUrl = 'https://ubilling.net.ua/rds/release/';
    @$releaseDataRaw = file_get_contents('RELEASE');
    if (!empty($releaseDataRaw)) {
        if ($raw) {
            $result .= $releaseDataRaw;
        } else {
            $infoParts = explode(' ', $releaseDataRaw);
            if (sizeof($infoParts) >= 3) {
                $codenameLink = $releaseInfoBaseUrl . vf($infoParts[0], 3);
                $result .= wf_Link($codenameLink, $releaseDataRaw, false, '', 'target="_BLANK"');
            }
        }
    }
    return ($result);
}
