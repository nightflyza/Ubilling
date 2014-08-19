<?php
if ((cfr('REPORTFINANCE')) AND (cfr('PAYFIND'))) {
    
    $assignReport=new agentAssignReport();
    
    //show search form
    show_window(__('Payment search'),$assignReport->paymentSearchForm());
    show_window('',  wf_Link('?module=report_finance', __('Back'), true, 'ubButton'));
    
    //do the search and display results
    if (wf_CheckPost(array('datefrom','dateto','dosearch'))) {
        show_window(__('Search results'),$assignReport->paymentSearch($_POST['datefrom'], $_POST['dateto']));
    }
    
} else {
    show_window(__('Error'), __('Access denied'));
}
    


?>
