<?php


/**
 * Returns hivemind AI trends reply for some payload
 * 
 * @param string $payload request payload data
 * @param string $string trending type helpdesk|taskman available now
 * 
 * @return string
 */
function zb_HivemindGetTrendsReply($payload, $type) {
    global $ubillingConfig;
    $result = '';
    $hiveUrl = $ubillingConfig->getAlterParam('HIVE_CUSTOM_URL');
    if (empty($hiveUrl)) {
        $hiveUrl = 'http://hivemind.ubilling.net.ua/';
    }

    $aiService = new OmaeUrl($hiveUrl);
    $aiService->dataPost('payload', $payload);
    $ubVer = file_get_contents('RELEASE');
    $agent = 'UbillingHiveClient/' . trim($ubVer);
    $aiService->setUserAgent($agent);
    $aiService->setTimeout(600);

    $request = array(
        'payload' => $payload,
        'type' => $type,
    );

    $request = json_encode($request);
    $aiService->dataPost('trends', $request);
    $rawReply = $aiService->response();

    if (json_validate($rawReply)) {
        $rawReply = json_decode($rawReply, true);
        if (isset($rawReply['error'])) {
            if ($rawReply['error'] == 0) {
                $result = $rawReply['reply'];
            } else {
                $result =  __('Error') . ': ' . $rawReply['error'] . ' - ' . __($rawReply['reply']);
            }
        } else {
            $result = __('Something went wrong') . ': ' . __('Unexpected error');
        }
    } else {
        $result = __('Something went wrong') . ': ' . __('AI service is not available');
    }
    return ($result);
}


/**
 * Returns AI hivemind reply for some helpdesk ticket
 * 
 * @param string $prompt
 * @param array $dialog
 * 
 * @return string
 */
function zb_TicketGetAiReply($prompt, $dialog) {
    global $ubillingConfig;
    set_time_limit(600);
    $result = '';
    $hiveUrl = $ubillingConfig->getAlterParam('HIVE_CUSTOM_URL');
    if (empty($hiveUrl)) {
        $hiveUrl = 'http://hivemind.ubilling.net.ua/';
    }

    $aiService = new OmaeUrl($hiveUrl);
    $ubVer = file_get_contents('RELEASE');
    $agent = 'UbillingHelpdesk/' . trim($ubVer);
    $aiService->setUserAgent($agent);
    $aiService->setTimeout(600);

    if (!empty($prompt)) {
        $request = array(
            'prompt' => $prompt,
            'dialog' => $dialog,
        );

        $request = json_encode($request);
        $aiService->dataPost('chat', $request);
        $rawReply = $aiService->response();
        if (json_validate($rawReply)) {
            $rawReply = json_decode($rawReply, true);
            if (isset($rawReply['error'])) {
                //success
                if ($rawReply['error'] == 0) {
                    $result = $rawReply['reply'];
                } else {
                    $result =  __('Error') . ': ' . $rawReply['error'] . ' - ' . __($rawReply['reply']);
                }
            } else {
                $result = __('Something went wrong') . ': ' . __('Unexpected error');
            }
        } else {
            $result = __('Something went wrong') . ': ' . __('AI service is not available');
        }
    }
    return ($result);
}


/**
 * Renders AI chat controls for helpdesk interface
 * 
 * @param string $aiDialogCallback
 * 
 * @return string
 */
function web_TicketAIChatControls($aiDialogCallback) {
    global $ubillingConfig;
    $disableOptionState = $ubillingConfig->getAlterParam('HIVE_DISABLED', 0);
    $enableFlag = ($disableOptionState) ? false : true;
    $result = '';
    if ($enableFlag) {
        $result .= wf_tag('script', false, '', 'type="text/javascript"');
        $result .= '
        function getAiReply() {
            var callbackData = ' . $aiDialogCallback . ';
            var aiLink = $("#hivemindstatus").html();
            var seconds = 0;
            var timer = setInterval(function() {
                seconds++;
                $("#hivemindstatus").html("<img src=\'skins/ajaxloader.gif\'> " + seconds + " ' . __('sec.') . '");
            }, 1000);
            $("#hivemindstatus").html("<img src=\'skins/ajaxloader.gif\'> 0 ' . __('sec.') . '");
            $.ajax({
                type: "POST",
                url: "?module=ticketing&hivemind=true",
                data: {aichatcallback: JSON.stringify(callbackData)},
                success: function(response) {
                    clearInterval(timer);
                    if (response) {
                        $("#ticketreplyarea").val(response);
                    }
                    $("#hivemindstatus").html(aiLink);
                },
                error: function() {
                    clearInterval(timer);
                    $("#hivemindstatus").html(aiLink);
                }
            });
        }
    ';
        $result .= wf_tag('script', true);

        $result .= wf_AjaxContainerSpan('hivemindstatus', '', wf_Link('#', wf_img('skins/icon_ai.png') . ' ' . __('Come up with an answer with the help of AI'), false, 'ubButton', 'onClick="getAiReply(); return false;"'));
        $result .= wf_CleanDiv();
        $result .= wf_delimiter(0);
    }
    return ($result);
}


/**
 * Collects short payload for available tasks trends
 *
 * @param int $depth
 * 
 * @return string
 */
function zb_HivemindGetTaskmanPayload($depth = 1) {
    $payLoad = '';
    $depth = ubRouting::filters($depth, 'int');
    $tasksDb = new NyanORM('taskman');
    $toDate = date('Y-m-d');
    $fromDate = date('Y-m-d', strtotime('-' . $depth . ' days'));
    $tasksDb->where('startdate', 'BETWEEN', $fromDate . "' AND '" . $toDate);
    $tasksDb->orderBy('startdate', 'ASC');
    $allTasks = $tasksDb->getAll('id');
    $allJobTypes = ts_GetAllJobtypes();
    $allEmployee = ts_GetAllEmployee();

    if (!empty($allTasks)) {
        if (sizeof($allTasks) >= 3) {
            foreach ($allTasks as $io => $each) {
                $startDate = $each['startdate'];
                $doneDate = ($each['enddate']) ?  __('Done') . ': ' . $each['enddate'] : __('Undone');
                $taskAddress = $each['address'];
                $jobType = trim($allJobTypes[$each['jobtype']]);
                $jobEmployee = $allEmployee[$each['employee']];
                $payLoad .= '[' . $startDate . ' | ' . $taskAddress . ' | ' . $jobType . ' | ' . $jobEmployee . ' | ' . $doneDate . ']' . PHP_EOL;
            }
        }
    }
    return ($payLoad);
}


/**
 * Collects short payload for available helpdesk tickets trends
 *
 * @param int $depth
 * 
 * @return string
 */
function zb_HivemindGetHelpdeskPayload($depth = 30) {
    $payLoad = '';
    $depth = ubRouting::filters($depth, 'int');
    $tasksDb = new NyanORM('ticketing');
    $toDate = date('Y-m-d');
    $fromDate = date('Y-m-d', strtotime('-' . $depth . ' days'));
    $tasksDb->where('date', 'BETWEEN', $fromDate . "' AND '" . $toDate);
    $tasksDb->where('to', 'IS', 'NULL');
    $tasksDb->where('admin', 'IS', 'NULL');
    $tasksDb->orderBy('id', 'ASC');
    $allTickets = $tasksDb->getAll('id');
    $allUserData = zb_UserGetAllDataCache();

    if (!empty($allTickets)) {

        if (sizeof($allTickets) >= 3) {
            foreach ($allTickets as $io => $each) {
                $ticketDate = $each['date'];
                $ticketAddress = @$allUserData[$each['from']]['fulladress'];
                $payLoad .= '[' . $ticketDate . ' , ' . $ticketAddress . ']' . PHP_EOL;
                $payLoad .= ubRouting::filters($each['text'], 'safe') . PHP_EOL;
            }
        }
    }
    return ($payLoad);
}
