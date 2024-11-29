<?php

if (cfr('TASKBAR')) {
    $taskbar = new UbillingTaskbar();
    show_window(__('Taskbar') . $taskbar->renderQuickSearchModal(), $taskbar->renderTaskbar());
} else {
    show_error(__('Access denied'));
}
