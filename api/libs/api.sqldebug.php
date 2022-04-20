<?php

/**
 *  Shows SQL debug icon if SQL queries log is available
 *  
 *  @return  string
 */
function web_SqlDebugIconShow() {
    $result = '';
    if (SQL_DEBUG) {
        if (cfr('ROOT')) {
            $queryLogData = web_SqlDebugLogParse();
            $result = wf_modal(wf_img_sized('skins/sqldebug.png', __('SQL queries debug'), 20), __('SQL queries debug'), $queryLogData, '', '900', '500');
        }
    }
    return ($result);
}

/**
 * Renders SQL queries log
 * 
 * @return string
 */
function web_SqlDebugLogParse() {
    $result = '';
    $messages = new UbillingMessageHelper();
    if (file_exists(SQL_DEBUG_LOG)) {
        $rawData = file_get_contents(SQL_DEBUG_LOG);
        if (!empty($rawData)) {
            $logLines = explodeRows($rawData);
            if (!empty($logLines)) {
                $logLines= array_reverse($logLines);
                foreach ($logLines as $io => $eachLine) {
                    if (!empty($eachLine)) {
                    $result .= $messages->getStyledMessage($eachLine, 'info');
                    }
                }
            }
        } else {
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'alert');
        }
    } else {
        $result .= $messages->getStyledMessage(__('SQL queries log not exists'), 'error');
    }
    return($result);
}
