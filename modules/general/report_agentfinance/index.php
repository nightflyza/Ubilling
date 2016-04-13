<?php
if ((cfr('REPORTFINANCE')) AND (cfr('PAYFIND'))) {
    
$altcfg=$ubillingConfig->getAlter();
if ($altcfg['AGENTS_ASSIGN']==2) {
    $assignReport=new agentAssignReport();
    
    //html print export
    if (wf_CheckGet(array('exporthtmlagentid'))) {
     $assignReport->exportHtml($_GET['exporthtmlagentid']);
    }
    
     //CSV data export
    if (wf_CheckGet(array('exportcsvagentid'))) {
     $assignReport->exportCSV($_GET['exportcsvagentid']);
    }
    
     //CSV data export short printable
    if (wf_CheckGet(array('exportcsvagentidshort'))) {
     $assignReport->exportCSV2($_GET['exportcsvagentidshort']);
    }
    
    
    //show search form
    show_window(__('Payment search'),$assignReport->paymentSearchForm());
    show_window('',  wf_Link('?module=report_finance', __('Back'), true, 'ubButton'));
    
    //do the search and display results
    if (wf_CheckPost(array('datefrom','dateto','dosearch','cashtypeid'))) {
        show_window(__('Search results'),$assignReport->paymentSearch($_POST['datefrom'], $_POST['dateto'],$_POST['cashtypeid']));
    }
    
} else {
    show_error(__('This module is disabled'));
}
    
} else {
    show_error(__('Access denied'));
}
    


?>
