<?php
if (cfr('PLDOCS')) {
    $altcfg= $ubillingConfig->getAlter();
    if ($altcfg['DOCX_SUPPORT']) {
        
        $documents=new ProfileDocuments();
        $date=(wf_CheckPost(array('showdate'))) ? $_POST['showdate'] : '' ;
        $documents->loadAllUsersDocuments($date);
        
        //existing document downloading
        if (wf_CheckGet(array('documentdownload'))) {
            zb_DownloadFile($documents::DOCUMENTS_PATH.$_GET['documentdownload']);
        }
        
        //document deletion from database
        if (wf_CheckGet(array('deletedocument'))) {
            $documents->unregisterDocument($_GET['deletedocument']);
            rcms_redirect('?module=report_documents');
        }
        
        
        //show calendar control
        show_window(__('By date'), $documents->dateControl());
        
        //list available documents
        show_window(__('Previously generated documents'),$documents->renderAllUserDocuments());
        
        
        
    } else {
        show_error(__('This module is disabled'));
    }
    
    
} else {
        show_error(__('You cant control this module'));
}

?>