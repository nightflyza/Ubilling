<?php
if (cfr('CATVBS')) {
    
    catv_GlobalControlsShow();
    
    $alterconf=rcms_parse_ini_file(CONFIG_PATH."catv.ini");
    set_time_limit(0);
    
    $inputencoding=$alterconf['BS_INCHARSET'];
    $outputencoding=$alterconf['BS_OUTCHARSET'];
    


//upload subroutine
 if (isset($_POST['upload'])) {
     $upload_done=catvbs_UploadFile();
     if ($upload_done) {
         //if image sucefully uploaded convert and load in into raw table
       $filename=$upload_done;
       $filecontent=file_get_contents(DATA_PATH."banksta/".$filename);
       if ($inputencoding!=$outputencoding) {
           $filecontent=  iconv($inputencoding, $outputencoding, $filecontent);
       }
       $hash=md5($filecontent);
       if (catvbs_CheckHash($hash)) {
           $rawid=catvbs_FilePush($filename, $filecontent);
           if ($rawid) {
               //reparse file into normal processing format
               catvbs_ParseRaw($rawid);
               log_register("CATV_BANKSTA UPLOAD DONE");
           }
       } else {
           show_window(__('Error'),__('Duplicate file detected'));
           log_register("CATV_BANKSTA UPLOAD DUPLICATE");
       }
       
     } 
 }
   
 // showing banksta processing
 if (isset($_GET['showhash'])) {
     //correcting original banksta
     if (wf_CheckPost(array('editrowid','editrealname'))) {
         catvbs_NameEdit($_POST['editrowid'], $_POST['editrealname']);
         rcms_redirect("?module=catv_banksta&showhash=".$_GET['showhash']);
     }
     
     if (wf_CheckPost(array('editrowid','editaddress'))) {
         catvbs_AddressEdit($_POST['editrowid'], $_POST['editaddress']);
         rcms_redirect("?module=catv_banksta&showhash=".$_GET['showhash']);
     }
     
     //lock row
     if (wf_CheckGet(array('lockrow'))) {
         catvbs_LockRow($_GET['lockrow']);
         rcms_redirect("?module=catv_banksta&showhash=".$_GET['showhash']);
     }
     
     //processing payments call
     if (wf_CheckPost(array('processingrequest'))) {
         catvbs_ProcessHash($_POST['processingrequest']);
     }
     
     // show needed hash
     catvbs_ShowHash($_GET['showhash']);
     // show processing form
     catvbs_ProcessingForm($_GET['showhash']);
     
 } else {
    if (wf_CheckGet(array('deletehash'))) {
        catvbs_DeleteBanksta($_GET['deletehash']);
        rcms_redirect("?module=catv_banksta");
    } 
     
    // only show avail statements 
   show_window(__('Import bank statement'),catvbs_UploadFileForm());
   catvbs_ShowAllStatements();
     
 }
    


} else {
    show_error('Access denied');
}


?>
