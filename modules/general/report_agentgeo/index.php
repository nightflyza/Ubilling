<?php
if (cfr('AGENTGEOREPORT')) {
    $report = new AgentGeoReport();
    show_window(__('Geography report'), $report->render());
} else {
    show_error(__('Access denied'));
}
