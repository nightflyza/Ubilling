<?php
if (cfr('BANKSTA')) {
    
    $alterconf=rcms_parse_ini_file(CONFIG_PATH."alter.ini");
    set_time_limit(0);
    
  if ($alterconf['BS_ENABLED']) {  
      
    $inputencoding=$alterconf['BS_INCHARSET'];
    $outputencoding=$alterconf['BS_OUTCHARSET'];
    


//upload subroutine
 if (isset($_POST['upload'])) {
     $upload_done=bs_UploadFile();
     if ($upload_done) {
         //if image sucefully uploaded convert and load in into raw table
       $filename=$upload_done;
       $filecontent=file_get_contents(DATA_PATH."banksta/".$filename);
       if ($inputencoding!=$outputencoding) {
           $filecontent=  iconv($inputencoding, $outputencoding, $filecontent);
       }
       $hash=md5($filecontent);
       if (bs_CheckHash($hash)) {
           $rawid=bs_FilePush($filename, $filecontent);
           if ($rawid) {
               //reparse file into normal processing format
               bs_ParseRaw($rawid);
               log_register("BANKSTA UPLOAD DONE");
           }
       } else {
           show_window(__('Error'),__('Duplicate file detected'));
           log_register("BANKSTA UPLOAD DUPLICATE");
       }
       
     } 
 }
   
 // showing banksta processing
 if (isset($_GET['showhash'])) {
     //correcting original banksta
     if (wf_CheckPost(array('editrowid','editrealname'))) {
         bs_NameEdit($_POST['editrowid'], $_POST['editrealname']);
         rcms_redirect("?module=bankstatements&showhash=".$_GET['showhash']);
     }
     
     if (wf_CheckPost(array('editrowid','editaddress'))) {
         bs_AddressEdit($_POST['editrowid'], $_POST['editaddress']);
         rcms_redirect("?module=bankstatements&showhash=".$_GET['showhash']);
     }
     
     //lock row
     if (wf_CheckGet(array('lockrow'))) {
         bs_LockRow($_GET['lockrow']);
         rcms_redirect("?module=bankstatements&showhash=".$_GET['showhash']);
     }
     
     //processing payments call
     if (wf_CheckPost(array('processingrequest'))) {
         bs_ProcessHash($_POST['processingrequest']);
     }
     
     // show needed hash
     bs_ShowHash($_GET['showhash']);
     // show processing form
     bs_ProcessingForm($_GET['showhash']);
     
 } else {
    if (wf_CheckGet(array('deletehash'))) {
        bs_DeleteBanksta($_GET['deletehash']);
        rcms_redirect("?module=bankstatements");
    } 
     
    // only show avail statements 
   show_window(__('Import bank statement'),bs_UploadFileForm());
   bs_ShowAllStatements();
     
 }
    
} else {
    show_error(__('Bank statements import not enabled'));
}

} else {
    show_error('Access denied');
}


?>
