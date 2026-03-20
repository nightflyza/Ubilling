<?php

$result = '';

if ($darkVoidContext['altCfg']['TB_TASKMANNOTIFY']) {
    if ($darkVoidContext['altCfg']['TB_TASKMANNOTIFY'] == 1) {
        $undoneTasksCount = ts_GetUndoneCountersMy();
        if ($undoneTasksCount > 0) {
            $undoneAlert = $undoneTasksCount . ' ' . __('Undone tasks') . ' ' . __('for me');
            $result .= wf_Link("?module=taskman&show=undone", wf_img("skins/jobnotify.png", $undoneAlert), false, '');
        }
    }

    if ($darkVoidContext['altCfg']['TB_TASKMANNOTIFY'] == 2) {
        $undoneTasksCount = ts_GetUndoneCountersAll();
        if ($undoneTasksCount > 0) {
            $undoneAlert = $undoneTasksCount . ' ' . __('Undone tasks') . ' ' . __('for all');
            $result .= wf_Link("?module=taskman&show=undone", wf_img("skins/jobnotify.png", $undoneAlert), false, '');
        }
    }

    if ($darkVoidContext['altCfg']['TB_TASKMANNOTIFY'] == 3) {
        $undoneTasksCount = ts_GetUndoneCountersAll();
        if ($undoneTasksCount > 0) {
            $undoneTasksCountMy = ts_GetUndoneCountersMy();
            $undoneAlert = $undoneTasksCount . ' ' . __('Undone tasks') . ': ' . __('for all') . ' ' . ($undoneTasksCount - $undoneTasksCountMy) . ' / ' . __('for me') . ' ' . $undoneTasksCountMy;
            $result .= wf_Link("?module=taskman&show=undone", wf_img("skins/jobnotify.png", $undoneAlert), false, '');
        }
    }
}

return ($result);
