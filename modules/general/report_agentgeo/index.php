<?php
if (cfr('AGENTGEOREPORT')) {
    $report = new AgentGeoReport();
    $report->catchExportRequest();
    if ($report->isPreviewRequest()) {
        show_window(__('Preview') . ' ' . __('Coverage'), $report->renderCoveragePreview());
    } else {
        show_window(__('Geography report'), $report->render());
        zb_BillingStats();
    }
    
    
} else {
    show_error(__('Access denied'));
}
