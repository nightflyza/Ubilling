<?php

if (cfr('TASKMAN')) {
    show_window(__('Typical problems'), ts_TaskProblemsEditForm());
} else {
    show_error(__('Access denied'));
}