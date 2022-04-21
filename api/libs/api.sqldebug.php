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
            $backUrl = '';
            if (!empty($_SERVER['REQUEST_URI'])) {
                $backUrl = '&back=' . base64_encode($_SERVER['REQUEST_URI']);
            }
            $controls = wf_Link('?module=sqldebug' . $backUrl, wf_img('skins/log_icon_small.png') . ' ' . __('All SQL queries log'), true, 'ubButton');
            $queryLogData = web_SqlDebugBufferRender(zb_GetSqlDebugBuffer());
            $dataToRender = $controls . $queryLogData;
            $result = wf_modal(wf_img_sized('skins/sqldebug.png', __('SQL queries debug'), 20), __('SQL queries debug'), $dataToRender, '', '900', '500');
        }
    }
    return ($result);
}

/**
 * Returns content of current run SQL debug buffer
 * 
 * @global array $mysqlDebugBuffer
 * 
 * @return array
 */
function zb_GetSqlDebugBuffer() {
    global $mysqlDebugBuffer;
    return($mysqlDebugBuffer);
}

/**
 * Renders current run SQL debug buffer 
 * 
 * @param array $bufferData
 * 
 * @return string
 */
function web_SqlDebugBufferRender($bufferData) {
    $result = '';
    $messages = new UbillingMessageHelper();
    $result .= $messages->getStyledMessage(__('Current run SQL queries'), 'success');
    if (!empty($bufferData)) {
        foreach ($bufferData as $io => $each) {
            $result .= $messages->getStyledMessage(nl2br($each), 'info');
        }
        $result .= $messages->getStyledMessage(__('SQL queries executed') . ': ' . sizeof($bufferData), 'success');
    } else {
        $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
    }
    return($result);
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
            $logLines = explode(SQL_DEBUG_QUERY_EOL, $rawData);
            if (!empty($logLines)) {
                $result .= $messages->getStyledMessage(__('All SQL queries log'), 'success');
                $logLines = array_reverse($logLines);
                foreach ($logLines as $io => $eachLine) {
                    $lineToRender = str_replace(SQL_DEBUG_QUERY_EOL, '', $eachLine);
                    $lineToRender = trim($lineToRender);
                    if (!empty($lineToRender)) {

                        $lineToRender = nl2br($lineToRender);
                        $result .= $messages->getStyledMessage($lineToRender, 'info');
                    }
                }
            }
        } else {
            $result .= $messages->getStyledMessage(__('Nothing to show'), 'warning');
        }
    } else {
        $result .= $messages->getStyledMessage(__('SQL queries log not exists'), 'error');
    }
    return($result);
}

/**
 * Flushes SQL debug log file
 * 
 * @return void
 */
function zb_SqlDebugLogFlush() {
    if (file_exists(SQL_DEBUG_LOG)) {
        if (is_writable(SQL_DEBUG_LOG)) {
            file_put_contents(SQL_DEBUG_LOG, '');
        }
    }
}
